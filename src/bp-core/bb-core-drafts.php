<?php
/**
 * BuddyBoss Draft Guardrails.
 *
 * Shared helpers for the activity and forum draft systems, which store
 * member drafts in usermeta. WordPress caches ALL of a user's meta as one
 * object-cache entry, and persistent caches such as Memcached enforce a
 * per-item size limit (1MB on WordPress VIP) — one oversized draft row
 * makes every read of that user's meta fall back to the database on every
 * request. These helpers bound the size of what the draft handlers may
 * store and centralize how a stored draft is disposed of.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Whether a usermeta key belongs to the BuddyBoss draft systems.
 *
 * Deliberately strict: only the exact keys the activity and forum draft
 * handlers write. Third-party plugins may legitimately store their own
 * `draft_*` usermeta, so a bare prefix match must never be used to count,
 * evict, or delete rows.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $meta_key Usermeta key.
 * @return bool True when the key is a BuddyBoss draft key.
 */
function bb_draft_is_draft_meta_key( $meta_key ) {
	if ( 'draft_user' === $meta_key || 'bb_user_topic_reply_draft' === $meta_key ) {
		return true;
	}

	return (bool) preg_match( '/^draft_(user|group)_\d+$/', (string) $meta_key );
}

/**
 * Maximum stored size of a single draft, in bytes.
 *
 * Applies to one activity draft row and to one inner topic/reply draft
 * inside the aggregated forum draft row.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return int Size limit in bytes.
 */
function bb_draft_max_size() {

	/**
	 * Filters the maximum stored size of a single draft.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $max_size Size limit in bytes. Default 100 KB.
	 */
	return (int) apply_filters( 'bb_draft_max_size', 100 * KB_IN_BYTES );
}

/**
 * Maximum combined stored size of all of a user's drafts, in bytes.
 *
 * All draft keys share the user's single meta cache entry, so per-draft
 * caps alone cannot bound it — a member can hold one draft per group.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return int Size limit in bytes.
 */
function bb_draft_user_total_max_size() {

	/**
	 * Filters the maximum combined stored size of a user's drafts.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $max_size Size limit in bytes. Default 300 KB.
	 */
	return (int) apply_filters( 'bb_draft_user_total_max_size', 300 * KB_IN_BYTES );
}

/**
 * Total user-meta budget a draft save must not push the user past, in bytes.
 *
 * The ~1MB cache-item limit covers the user's ENTIRE meta, not only
 * drafts. Draft saves are refused when the user's total meta would exceed
 * this budget, whatever the draft share of it is.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return int Budget in bytes.
 */
function bb_draft_user_meta_budget() {

	/**
	 * Filters the total user-meta budget draft saves must respect.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $budget Budget in bytes. Default 800 KB.
	 */
	return (int) apply_filters( 'bb_draft_user_meta_budget', 800 * KB_IN_BYTES );
}

/**
 * Strip inline data-URL images from draft content.
 *
 * A pasted screenshot is inserted by the browser as a base64 `data:` image
 * of 1MB+; nothing that large may enter usermeta. The publish path already
 * drops the `data:` protocol via kses — stripping the whole tag here keeps
 * megabytes out of the request pipeline before kses runs.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $content Draft content.
 * @return string Content without data-URL images.
 */
function bb_draft_strip_data_urls( $content ) {
	if ( ! is_string( $content ) || false === stripos( $content, 'data:' ) ) {
		return $content;
	}

	// Remove the base64 payload itself first. This linear character-class
	// pattern cannot hit PCRE backtracking limits even on multi-megabyte
	// pasted images, unlike a tag-level match over the full subject.
	$stripped = preg_replace( '/data:[a-z0-9.+-]+\/[a-z0-9.+-]+;base64,[A-Za-z0-9+\/=]*/i', '', $content );

	if ( is_string( $stripped ) ) {
		$content = $stripped;
	}

	// Then drop the now-empty inline images entirely.
	$stripped = preg_replace( '/<img\b[^>]*\bsrc\s*=\s*(["\']?)\s*\1[^>]*>/i', '', $content );

	if ( is_string( $stripped ) ) {
		$content = $stripped;
	}

	// On any PCRE failure the original content is returned unchanged - the
	// per-draft size cap then rejects an oversized draft instead of this
	// function silently discarding the member's text.
	return $content;
}

/**
 * Whether a user may manage (stamp or delete) an attachment from a draft.
 *
 * Draft payloads are client JSON; without this check any logged-in member
 * could delete arbitrary attachments site-wide through the draft handlers.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $attachment_id Attachment post ID.
 * @param int $user_id       Acting user ID.
 * @return bool True when the attachment exists and belongs to the user.
 */
function bb_draft_user_can_manage_attachment( $attachment_id, $user_id ) {
	$attachment_id = (int) $attachment_id;
	$user_id       = (int) $user_id;
	$can_manage    = false;

	if ( $attachment_id > 0 && $user_id > 0 ) {
		$attachment = get_post( $attachment_id );
		$can_manage = ( ! empty( $attachment ) && 'attachment' === $attachment->post_type && (int) $attachment->post_author === $user_id );
	}

	/**
	 * Filters whether a user may manage an attachment referenced by a draft.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param bool $can_manage    Whether the user owns the attachment.
	 * @param int  $attachment_id Attachment post ID.
	 * @param int  $user_id       Acting user ID.
	 */
	return (bool) apply_filters( 'bb_draft_user_can_manage_attachment', $can_manage, $attachment_id, $user_id );
}

/**
 * Get the stored byte sizes of a user's meta, split into drafts and total.
 *
 * Reads the raw (still serialized) values from the primed meta cache, so
 * the measured bytes equal the database/cache size and no extra query runs.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $user_id User ID.
 * @return array {
 *     @type int   $total  Total bytes of all of the user's meta values.
 *     @type array $drafts Draft meta key => stored bytes.
 * }
 */
function bb_draft_get_user_meta_sizes( $user_id ) {
	$sizes = array(
		'total'  => 0,
		'drafts' => array(),
	);

	$all_meta = get_user_meta( (int) $user_id );

	if ( empty( $all_meta ) || ! is_array( $all_meta ) ) {
		return $sizes;
	}

	foreach ( $all_meta as $meta_key => $values ) {
		if ( ! is_array( $values ) ) {
			continue;
		}

		foreach ( $values as $raw_value ) {
			$bytes           = is_string( $raw_value ) ? strlen( $raw_value ) : strlen( maybe_serialize( $raw_value ) );
			$sizes['total'] += $bytes;

			if ( bb_draft_is_draft_meta_key( $meta_key ) ) {
				$sizes['drafts'][ $meta_key ] = isset( $sizes['drafts'][ $meta_key ] ) ? $sizes['drafts'][ $meta_key ] + $bytes : $bytes;
			}
		}
	}

	return $sizes;
}

/**
 * Collect the attachment IDs referenced by one draft's data array.
 *
 * Understands both the activity shape (`media`/`document`/`video` arrays of
 * `['id' => N]` plus the feature image) and the forum shape (`bbp_media`/
 * `bbp_document`/`bbp_video` JSON strings of the same).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $draft Draft array containing a `data` member.
 * @return int[] Attachment IDs.
 */
function bb_draft_collect_attachment_ids( $draft ) {
	$ids = array();

	if ( empty( $draft['data'] ) || ! is_array( $draft['data'] ) ) {
		return $ids;
	}

	$data = $draft['data'];

	foreach ( array( 'media', 'document', 'video' ) as $type ) {
		if ( ! empty( $data[ $type ] ) && is_array( $data[ $type ] ) ) {
			foreach ( $data[ $type ] as $entry ) {
				if ( ! empty( $entry['id'] ) ) {
					$ids[] = (int) $entry['id'];
				}
			}
		}
	}

	foreach ( array( 'bbp_media', 'bbp_document', 'bbp_video' ) as $type ) {
		if ( ! empty( $data[ $type ] ) && is_string( $data[ $type ] ) ) {
			$entries = json_decode( $data[ $type ], true );
			if ( ! empty( $entries ) && is_array( $entries ) ) {
				foreach ( $entries as $entry ) {
					if ( ! empty( $entry['id'] ) ) {
						$ids[] = (int) $entry['id'];
					}
				}
			}
		}
	}

	if ( ! empty( $data['bb_activity_post_feature_image']['id'] ) ) {
		$ids[] = (int) $data['bb_activity_post_feature_image']['id'];
	}

	return array_values( array_unique( array_filter( $ids ) ) );
}

/**
 * Release the draft protection stamps from a draft's attachments.
 *
 * Removes the `bb_media_draft` / `bb_activity_post_feature_image_draft`
 * post meta so the existing orphaned-attachment crons become able to
 * collect the files again. Only attachments the user owns are touched.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $draft   Draft array.
 * @param int   $user_id Owning user ID.
 * @return void
 */
function bb_draft_unstamp_attachments( $draft, $user_id ) {
	$attachment_ids = bb_draft_collect_attachment_ids( $draft );

	foreach ( $attachment_ids as $attachment_id ) {
		if ( ! bb_draft_user_can_manage_attachment( $attachment_id, $user_id ) ) {
			continue;
		}

		delete_post_meta( $attachment_id, 'bb_media_draft' );
		delete_post_meta( $attachment_id, 'bb_activity_post_feature_image_draft' );
	}
}

/**
 * Dispose of one stored draft: release its attachment stamps and remove it.
 *
 * The single shared removal path used by budget eviction and (in later
 * releases) the cleanup cron and upgrade routine, so every deletion route
 * treats attachments and row shape identically. For the aggregated forum
 * row, pass `$inner_key` to remove one inner draft; an emptied aggregate
 * row is deleted rather than stored as an empty array.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int    $user_id   Owning user ID.
 * @param string $meta_key  Draft usermeta key.
 * @param string $inner_key Optional. Inner draft key inside the forum aggregate row.
 * @return bool Whether a stored draft was removed.
 */
function bb_draft_dispose( $user_id, $meta_key, $inner_key = '' ) {
	$user_id = (int) $user_id;

	if ( $user_id <= 0 || ! bb_draft_is_draft_meta_key( $meta_key ) ) {
		return false;
	}

	$stored = bp_get_user_meta( $user_id, $meta_key, true );

	if ( empty( $stored ) || ! is_array( $stored ) ) {
		return false;
	}

	if ( '' !== $inner_key ) {
		if ( ! isset( $stored[ $inner_key ] ) ) {
			return false;
		}

		bb_draft_unstamp_attachments( $stored[ $inner_key ], $user_id );
		unset( $stored[ $inner_key ] );

		if ( empty( $stored ) ) {
			bp_delete_user_meta( $user_id, $meta_key );
		} else {
			bp_update_user_meta( $user_id, $meta_key, $stored );
		}

		return true;
	}

	if ( 'bb_user_topic_reply_draft' === $meta_key ) {
		foreach ( $stored as $inner_draft ) {
			bb_draft_unstamp_attachments( $inner_draft, $user_id );
		}
	} else {
		bb_draft_unstamp_attachments( $stored, $user_id );
	}

	bp_delete_user_meta( $user_id, $meta_key );

	return true;
}

/**
 * Enforce the per-user draft and meta budgets before storing a draft.
 *
 * When the combined size of the user's drafts would exceed
 * {@see bb_draft_user_total_max_size()}, the oldest drafts (activity rows
 * and inner forum drafts alike, by their `_draft_saved_at` stamp; unstamped
 * legacy drafts count as oldest) are evicted through
 * {@see bb_draft_dispose()} until the new draft fits. When the user's
 * TOTAL meta would exceed {@see bb_draft_user_meta_budget()}, the save is
 * refused instead — the draft system must never be what pushes a user's
 * meta cache entry past the platform item limit.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int    $user_id     User ID.
 * @param string $current_key Meta key about to be written (protected from eviction).
 * @param int    $new_size    Serialized size of the value about to be written.
 * @return array {
 *     @type bool     $allowed Whether the save may proceed.
 *     @type string[] $evicted Draft keys evicted to make room ("meta_key" or "meta_key:inner_key").
 * }
 */
function bb_draft_enforce_user_budget( $user_id, $current_key, $new_size ) {
	$user_id  = (int) $user_id;
	$new_size = (int) $new_size;
	$result   = array(
		'allowed' => true,
		'evicted' => array(),
	);

	$sizes        = bb_draft_get_user_meta_sizes( $user_id );
	$current_size = isset( $sizes['drafts'][ $current_key ] ) ? $sizes['drafts'][ $current_key ] : 0;

	// Refuse outright when total user meta would exceed the platform budget.
	if ( ( $sizes['total'] - $current_size + $new_size ) > bb_draft_user_meta_budget() ) {
		$result['allowed'] = false;

		return $result;
	}

	$draft_total = array_sum( $sizes['drafts'] ) - $current_size + $new_size;
	$total_cap   = bb_draft_user_total_max_size();

	if ( $draft_total <= $total_cap ) {
		return $result;
	}

	// Build eviction candidates: activity rows and inner forum drafts, oldest first.
	$candidates = array();

	foreach ( $sizes['drafts'] as $meta_key => $bytes ) {
		if ( $meta_key === $current_key ) {
			continue;
		}

		$stored = bp_get_user_meta( $user_id, $meta_key, true );

		if ( empty( $stored ) || ! is_array( $stored ) ) {
			continue;
		}

		if ( 'bb_user_topic_reply_draft' === $meta_key ) {
			foreach ( $stored as $inner_key => $inner_draft ) {
				$candidates[] = array(
					'meta_key'  => $meta_key,
					'inner_key' => (string) $inner_key,
					'saved_at'  => isset( $inner_draft['_draft_saved_at'] ) ? (int) $inner_draft['_draft_saved_at'] : 0,
					'bytes'     => strlen( maybe_serialize( $inner_draft ) ),
				);
			}
		} else {
			$candidates[] = array(
				'meta_key'  => $meta_key,
				'inner_key' => '',
				'saved_at'  => isset( $stored['_draft_saved_at'] ) ? (int) $stored['_draft_saved_at'] : 0,
				'bytes'     => $bytes,
			);
		}
	}

	usort(
		$candidates,
		function ( $a, $b ) {
			if ( $a['saved_at'] === $b['saved_at'] ) {
				return 0;
			}

			return ( $a['saved_at'] < $b['saved_at'] ) ? -1 : 1;
		}
	);

	foreach ( $candidates as $candidate ) {
		if ( $draft_total <= $total_cap ) {
			break;
		}

		if ( ! bb_draft_dispose( $user_id, $candidate['meta_key'], $candidate['inner_key'] ) ) {
			continue;
		}

		$draft_total -= $candidate['bytes'];
		$evicted_key  = '' !== $candidate['inner_key'] ? $candidate['meta_key'] . ':' . $candidate['inner_key'] : $candidate['meta_key'];

		$result['evicted'][] = $evicted_key;

		/**
		 * Fires when a stored draft is evicted to keep a user under the draft size budget.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int    $user_id     User whose draft was evicted.
		 * @param string $evicted_key Evicted draft key ("meta_key" or "meta_key:inner_key").
		 * @param string $reason      Eviction reason. Currently always 'aggregate_cap'.
		 */
		do_action( 'bb_draft_evicted', $user_id, $evicted_key, 'aggregate_cap' );
	}

	return $result;
}

/**
 * Validate an activity draft data key against server-derived rules.
 *
 * The composer builds `draft_user` (own feed), `draft_user_{N}` (composing
 * on member N's profile), or `draft_group_{N}` (group feed). The client
 * string is only accepted when it matches the shape for its object and the
 * user passes that object's posting rules — mirroring the publish path,
 * which gates profile posts with {@see bb_user_can_create_activity()} and
 * group posts with group membership.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $data_key     Client-supplied draft key.
 * @param string $draft_object Draft object ('user' or 'group').
 * @param int    $item_id      Group ID for group drafts.
 * @param int    $user_id      Acting user ID.
 * @return bool True when the key is valid for this user.
 */
function bb_draft_validate_activity_data_key( $data_key, $draft_object, $item_id, $user_id ) {
	$user_id = (int) $user_id;

	if ( 'group' === $draft_object ) {
		$item_id = (int) $item_id;

		if ( $item_id <= 0 || 'draft_group_' . $item_id !== $data_key || ! bp_is_active( 'groups' ) ) {
			return false;
		}

		if ( bp_current_user_can( 'bp_moderate' ) ) {
			return true;
		}

		return (bool) ( groups_is_user_member( $user_id, $item_id ) || groups_is_user_admin( $user_id, $item_id ) || groups_is_user_mod( $user_id, $item_id ) );
	}

	if ( 'user' === $draft_object ) {
		if ( ! bb_user_can_create_activity() ) {
			return false;
		}

		if ( 'draft_user' === $data_key ) {
			return true;
		}

		if ( preg_match( '/^draft_user_(\d+)$/', $data_key, $matches ) ) {
			// The key lives under the SAVING user's own meta, so the suffix only
			// needs to be a real member — there is no cross-user write to prevent.
			return (bool) get_userdata( (int) $matches[1] );
		}
	}

	return false;
}

/**
 * Validate a forum draft inner data key against the shapes the forum JS builds.
 *
 * Accepted shapes: `draft_topic`, `draft_discussion_{forum_id}`,
 * `draft_reply`, `draft_reply_{topic_id}` and
 * `draft_reply_{topic_id}_{reply_to}` — the bare shapes occur when no
 * forum/topic ID is resolvable on the page.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $data_key Client-supplied inner draft key.
 * @return bool True when the key matches a real forum/topic/reply.
 */
function bb_draft_validate_topic_reply_data_key( $data_key ) {
	if ( 'draft_topic' === $data_key || 'draft_reply' === $data_key ) {
		return true;
	}

	if ( preg_match( '/^draft_discussion_(\d+)$/', $data_key, $matches ) ) {
		$forum = get_post( (int) $matches[1] );

		return ( ! empty( $forum ) && bbp_get_forum_post_type() === $forum->post_type );
	}

	if ( preg_match( '/^draft_reply_(\d+)(?:_(\d+))?$/', $data_key, $matches ) ) {
		$topic = get_post( (int) $matches[1] );

		if ( empty( $topic ) || bbp_get_topic_post_type() !== $topic->post_type ) {
			return false;
		}

		if ( isset( $matches[2] ) ) {
			$reply = get_post( (int) $matches[2] );

			return ( ! empty( $reply ) && bbp_get_reply_post_type() === $reply->post_type );
		}

		return true;
	}

	return false;
}
