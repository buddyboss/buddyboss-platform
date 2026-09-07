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

	// Then drop images whose src is now actually empty (src="" / src='' / bare).
	// The value must be provably empty - an optional-quote backreference would
	// backtrack into matching ANY img tag.
	$stripped = preg_replace( '/<img\b[^>]*\bsrc\s*=\s*(?:""|\'\'|(?=[\s>]))[^>]*>/i', '', $content );

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
 * The result is memoized per request because a single save asks for it more
 * than once; {@see bb_draft_flush_user_meta_sizes()} clears it after a write.
 *
 * @param int  $user_id User ID.
 * @param bool $flush   Optional. True to drop the memoized entry and return
 *                      empty sizes without re-measuring. Default false.
 * @return array {
 *     @type int   $total  Total bytes of all of the user's meta values.
 *     @type array $drafts Draft meta key => stored bytes.
 * }
 */
function bb_draft_get_user_meta_sizes( $user_id, $flush = false ) {
	$user_id = (int) $user_id;

	// Memoized per request: a single draft save asks for these sizes more than
	// once (row trim, then budget enforcement), and each rebuild walks EVERY
	// meta row for the user running strlen()/maybe_serialize(). On a community
	// where members carry hundreds of rows that is real CPU on the autosave
	// path. Invalidated by bb_draft_flush_user_meta_sizes() after any write.
	static $memo = array();

	if ( $flush ) {
		unset( $memo[ $user_id ] );

		return array(
			'total'  => 0,
			'drafts' => array(),
		);
	}

	if ( isset( $memo[ $user_id ] ) ) {
		return $memo[ $user_id ];
	}

	$sizes = array(
		'total'  => 0,
		'drafts' => array(),
	);

	$all_meta = get_user_meta( $user_id );

	if ( empty( $all_meta ) || ! is_array( $all_meta ) ) {
		$memo[ $user_id ] = $sizes;

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

	$memo[ $user_id ] = $sizes;

	return $sizes;
}

/**
 * Drop the memoized meta sizes for a user after a draft write.
 *
 * Kept explicit rather than clearing inside the writers: the sizes are only
 * ever re-read within the same request by the save pipeline, so a stale
 * memo would make a second cap decision on pre-write numbers.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $user_id User ID.
 * @return void
 */
function bb_draft_flush_user_meta_sizes( $user_id ) {
	bb_draft_get_user_meta_sizes( (int) $user_id, true );
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
 * The single shared removal path used by budget eviction, the cleanup
 * cron, and the upgrade healing routine, so every deletion route
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

	// A corrupt row (serialized value truncated mid-write, wrong type) or a
	// legacy-empty array() left behind by the old forum publish paths carries
	// nothing to unstamp, but the row itself is exactly the shape the healing
	// and cleanup passes must be able to remove - a truncated multi-MB value
	// is the very cache-poisoning row this ticket is about. A missing row
	// reads as '' and has no meta to delete, so it still reports failure.
	if ( ! is_array( $stored ) || empty( $stored ) ) {
		if ( '' === $inner_key && metadata_exists( 'user', $user_id, bp_get_user_meta_key( $meta_key ) ) ) {
			bp_delete_user_meta( $user_id, $meta_key );

			return true;
		}

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
	bb_draft_flush_user_meta_sizes( $user_id );

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
 * @param string $context     Optional. 'save' (default) refuses over the total-meta budget;
 *                            'heal' bypasses that refusal and always evicts down to the draft cap.
 * @return array {
 *     @type bool     $allowed Whether the save may proceed.
 *     @type string[] $evicted Draft keys evicted to make room ("meta_key" or "meta_key:inner_key").
 * }
 */
function bb_draft_enforce_user_budget( $user_id, $current_key, $new_size, $context = 'save' ) {
	$user_id  = (int) $user_id;
	$new_size = (int) $new_size;
	$result   = array(
		'allowed' => true,
		'evicted' => array(),
	);

	$sizes        = bb_draft_get_user_meta_sizes( $user_id );
	$current_size = isset( $sizes['drafts'][ $current_key ] ) ? $sizes['drafts'][ $current_key ] : 0;

	// Refuse outright when total user meta would exceed the platform budget -
	// but only for live saves. The healing context exists precisely for users
	// already OVER that budget; refusing them would make the one-shot a no-op
	// for the users it targets, so healing always proceeds to eviction.
	if ( 'heal' !== $context && ( $sizes['total'] - $current_size + $new_size ) > bb_draft_user_meta_budget() ) {
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
 * string is only accepted when it matches the shape for its object and
 * passes that object's gate, mirroring the publish path exactly:
 *
 * - `group` — a real per-user check (membership, group admin/mod, or the
 *   `bp_moderate` capability).
 * - `user`  — the SITE-WIDE {@see bb_user_can_create_activity()} switch,
 *   which is a filter defaulting to true and is not a per-user capability.
 *   This mirrors the publish path; do not read it as a per-member gate.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $data_key     Client-supplied draft key.
 * @param string $draft_object Draft object ('user' or 'group').
 * @param int    $item_id      Group ID for group drafts.
 * @param int    $user_id      Acting user ID.
 * @param string $context      Optional. 'save' (default) enforces the object's posting
 *                             rules; 'manage' (delete/fetch of the member's own meta)
 *                             validates the key shape only.
 * @return bool True when the key is valid for this user.
 */
function bb_draft_validate_activity_data_key( $data_key, $draft_object, $item_id, $user_id, $context = 'save' ) {
	$user_id = (int) $user_id;

	// Deleting or fetching addresses the member's OWN stored meta - no posting
	// capability applies, and the group ID is derived from the key itself so a
	// slim delete payload (which carries no data member) still validates. A
	// member who lost posting rights must still be able to discard or restore
	// their existing draft.
	if ( 'manage' === $context ) {
		return ( 'draft_user' === $data_key && 'user' === $draft_object ) || (bool) preg_match( '/^draft_(user|group)_\d+$/', $data_key );
	}

	if ( 'group' === $draft_object ) {
		$item_id = (int) $item_id;

		if ( $item_id <= 0 || 'draft_group_' . $item_id !== $data_key || ! bp_is_active( 'groups' ) ) {
			return false;
		}

		// bp_current_user_can() rather than bp_user_can(): both call sites pass
		// bp_loggedin_user_id(), and the two helpers fire DIFFERENT public
		// filters - switching would silently drop sites that grant bp_moderate
		// through the long-standing `bp_current_user_can` filter.
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

/**
 * Draft retention window in days.
 *
 * A value below 1 means "never expire": age-based cleanup is switched off
 * entirely. Without that floor, filtering the window to 0 - the obvious way
 * to disable expiry - would put the cutoff at the current time and make the
 * next cleanup run delete every draft on the site.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return int Retention window in days; 0 when expiry is disabled.
 */
function bb_draft_retention_days() {

	/**
	 * Filters how many days an untouched draft is kept before expiry.
	 *
	 * Return 0 (or a negative value) to disable age-based draft expiry.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $days Retention window in days. Default 30.
	 */
	$days = (int) apply_filters( 'bb_draft_retention_days', 30 );

	return ( 1 > $days ) ? 0 : $days;
}

/**
 * Draft retention window in seconds.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return int Retention window in seconds; 0 when expiry is disabled.
 */
function bb_draft_retention_seconds() {
	return bb_draft_retention_days() * DAY_IN_SECONDS;
}

/**
 * Unserialize a stored draft value without instantiating objects.
 *
 * Draft rows originate from client JSON; a crafted serialized object must
 * decode as an inert incomplete class, never a live instance.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value Possibly serialized value.
 * @return mixed Unserialized value, or the input when not serialized.
 */
function bb_draft_safe_unserialize( $value ) {
	if ( ! is_string( $value ) || ! is_serialized( $value ) ) {
		return $value;
	}

	return unserialize( trim( $value ), array( 'allowed_classes' => false ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize,PHPCompatibility.FunctionUse.NewFunctionParameters.unserialize_optionsFound -- objects disallowed; the platform minimum is PHP 7.4 where the options parameter exists.
}

/**
 * Fetch one cursor-advanced batch of BuddyBoss draft usermeta rows.
 *
 * The cursor makes the sweep RESUMABLE, not cheap: MySQL satisfies the
 * meta_key predicate from the meta_key index and applies `umeta_id > cursor`
 * as a post-filter with a filesort (verified with EXPLAIN: type=range,
 * key=meta_key, Extra="Using index condition; Using where; Using filesort"),
 * so each batch re-scans and re-sorts the whole draft-key range rather than
 * seeking. Sizing the cron must assume that cost; a (meta_key, umeta_id)
 * composite index is what would make this a true keyset seek.
 *
 * The SQL LIKE patterns only narrow the scan; every returned key must still
 * pass {@see bb_draft_is_draft_meta_key()} before it is acted on, because
 * LIKE cannot express "numeric suffix" and third-party plugins may store
 * their own draft_* keys.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int  $last_umeta_id Resume after this row ID.
 * @param int  $limit         Maximum rows to fetch.
 * @param bool $with_values   Whether to select meta_value too.
 * @return array {
 *     @type array[] $rows     Validated draft rows (umeta_id, user_id, meta_key, bytes[, meta_value]).
 *     @type int     $last_id  Raw scan cursor - resume after this row ID.
 *     @type bool    $has_more Whether the raw window was full (more rows may exist).
 * }
 */
function bb_draft_get_rows_batch( $last_umeta_id = 0, $limit = 200, $with_values = true ) {
	global $wpdb;

	$value_column = $with_values ? ', meta_value' : '';
	$like_user    = $wpdb->esc_like( 'draft_user_' ) . '%';
	$like_group   = $wpdb->esc_like( 'draft_group_' ) . '%';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- maintenance scan; $value_column is a fixed literal.
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT umeta_id, user_id, meta_key, LENGTH(meta_value) AS bytes{$value_column}
			FROM {$wpdb->usermeta}
			WHERE ( meta_key = 'draft_user' OR meta_key = 'bb_user_topic_reply_draft' OR meta_key LIKE %s OR meta_key LIKE %s )
			AND umeta_id > %d
			ORDER BY umeta_id ASC
			LIMIT %d",
			$like_user,
			$like_group,
			(int) $last_umeta_id,
			(int) $limit
		),
		ARRAY_A
	);

	if ( empty( $rows ) ) {
		return array(
			'rows'     => array(),
			'last_id'  => (int) $last_umeta_id,
			'has_more' => false,
		);
	}

	$raw_count = count( $rows );
	$max_id    = (int) $rows[ $raw_count - 1 ]['umeta_id'];

	return array(
		// The cursor advances over the RAW window: a window consisting entirely
		// of filtered-out third-party keys must not end the scan early.
		'rows'     => array_values(
			array_filter(
				$rows,
				function ( $row ) {
					return bb_draft_is_draft_meta_key( $row['meta_key'] );
				}
			)
		),
		'last_id'  => $max_id,
		'has_more' => ( $raw_count === (int) $limit ),
	);
}

/**
 * Delete drafts whose last save is older than the retention window.
 *
 * Runs as the daily `bb_draft_cleanup` cron and drains INLINE in the cron
 * request: the shared background-process classes dispatch loopback POSTs
 * only (their cron healthcheck included), which never execute on
 * loopback-hostile hosts - the class that reported this bug. When the time
 * budget runs out a single continuation event is self-scheduled.
 *
 * Legacy rows saved before stamping existed age from the
 * `bb_draft_cleanup_epoch` option recorded at upgrade, so pre-existing
 * drafts still expire. Deletion goes row-at-a-time through
 * {@see bb_draft_dispose()} (meta API - replication-safe, cache-coherent,
 * attachment stamps released).
 *
 * The scan cursor is persisted in the `bb_draft_cleanup_cursor` option
 * between budget-interrupted slices: without it every continuation slice
 * would restart from row zero, and on a site whose draft rows cannot be
 * scanned inside one budget the rows past the time horizon would never be
 * reached at all.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $time_budget Seconds to spend this run; 0 for unlimited.
 * @return array { @type int $deleted @type bool $complete }
 */
function bb_drafts_delete_expired( $time_budget = 10 ) {
	$time_budget       = (int) $time_budget;
	$started_at        = time();
	$retention_seconds = bb_draft_retention_seconds();
	$deleted           = 0;
	$complete          = true;
	$cursor            = (int) get_option( 'bb_draft_cleanup_cursor', 0 );

	// Expiry switched off - delete nothing. Guarded here rather than relying on
	// the cutoff arithmetic, where a zero window would expire every draft.
	if ( 1 > $retention_seconds ) {
		delete_option( 'bb_draft_cleanup_cursor' );

		return array(
			'deleted'  => 0,
			'complete' => true,
		);
	}

	$cutoff = time() - $retention_seconds;

	// Record the epoch lazily when the upgrade routine never did (fresh
	// installs, removed option) so timestamp-less legacy rows still age out
	// eventually instead of never.
	$epoch = (int) get_option( 'bb_draft_cleanup_epoch' );
	if ( ! $epoch ) {
		$epoch = time();
		add_option( 'bb_draft_cleanup_epoch', $epoch, '', false );
	}

	do {
		$batch = bb_draft_get_rows_batch( $cursor, 200, true );

		foreach ( $batch['rows'] as $row ) {
			$user_id = (int) $row['user_id'];
			$value   = bb_draft_safe_unserialize( $row['meta_value'] );

			if ( 'bb_user_topic_reply_draft' === $row['meta_key'] ) {
				if ( ! is_array( $value ) || empty( $value ) ) {
					// A legacy-empty array() row (old publish paths) holds no member
					// content and is collected immediately; a corrupt row ages from
					// the epoch like any unstamped legacy row.
					if ( ( is_array( $value ) || $epoch < $cutoff ) && bb_draft_dispose( $user_id, $row['meta_key'] ) ) {
						++$deleted;
					}
				} else {
					foreach ( $value as $inner_key => $inner_draft ) {
						$saved_at = isset( $inner_draft['_draft_saved_at'] ) ? (int) $inner_draft['_draft_saved_at'] : $epoch;
						if ( $saved_at < $cutoff && bb_draft_dispose( $user_id, $row['meta_key'], (string) $inner_key ) ) {
							++$deleted;
						}
					}
				}
			} else {
				$saved_at = ( is_array( $value ) && isset( $value['_draft_saved_at'] ) ) ? (int) $value['_draft_saved_at'] : $epoch;
				if ( $saved_at < $cutoff && bb_draft_dispose( $user_id, $row['meta_key'] ) ) {
					++$deleted;
				}
			}

			// Advance row-by-row: a budget break must resume AFTER the row just
			// processed, not at the raw window's end (rows behind it in the same
			// window would otherwise be skipped forever).
			$cursor = (int) $row['umeta_id'];

			if ( 0 < $time_budget && ( time() - $started_at ) >= $time_budget ) {
				$complete = false;
				break 2;
			}
		}

		// The whole window (draft rows AND filtered third-party keys) is done.
		$cursor = (int) $batch['last_id'];

		// Window-level budget check: a window whose rows are ALL filtered-out
		// third-party draft_* keys never reaches the per-row check above, so
		// consecutive such windows would otherwise run past the budget.
		if ( 0 < $time_budget && $batch['has_more'] && ( time() - $started_at ) >= $time_budget ) {
			$complete = false;
			break;
		}
	} while ( $batch['has_more'] );

	if ( $complete ) {
		delete_option( 'bb_draft_cleanup_cursor' );
	} else {
		update_option( 'bb_draft_cleanup_cursor', $cursor, false );

		if ( ! wp_next_scheduled( 'bb_draft_cleanup' ) ) {
			wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'bb_draft_cleanup' );
		}
	}

	return array(
		'deleted'  => $deleted,
		'complete' => $complete,
	);
}

/**
 * Heal one aggregated forum draft row without destroying legal drafts.
 *
 * The per-draft cap applies to INNER topic/reply drafts, not the aggregate
 * row - a member with several modest drafts may legally hold a row larger
 * than one draft's cap. Inner drafts over the per-draft cap are disposed
 * individually; the row is then trimmed oldest-first to the per-user draft
 * budget. Only a corrupt (non-array) row is disposed wholesale.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $user_id Owning user ID.
 * @return int Number of inner drafts disposed.
 */
function bb_draft_heal_forum_row( $user_id ) {
	$user_id  = (int) $user_id;
	$disposed = 0;
	$row      = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

	if ( empty( $row ) ) {
		return 0;
	}

	if ( ! is_array( $row ) ) {
		return bb_draft_dispose( $user_id, 'bb_user_topic_reply_draft' ) ? 1 : 0;
	}

	$max_size = bb_draft_max_size();

	// Inner drafts individually over the per-draft cap.
	foreach ( $row as $inner_key => $inner_draft ) {
		if ( strlen( maybe_serialize( $inner_draft ) ) > $max_size && bb_draft_dispose( $user_id, 'bb_user_topic_reply_draft', (string) $inner_key ) ) {
			++$disposed;
		}
	}

	// Oldest-first down to the per-user draft budget.
	$row = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

	$total_cap = bb_draft_user_total_max_size();

	while ( is_array( $row ) && ! empty( $row ) ) {
		$row_bytes = strlen( maybe_serialize( $row ) );

		if ( $row_bytes <= $total_cap ) {
			break;
		}

		$oldest_key = '';
		$oldest_ts  = PHP_INT_MAX;

		foreach ( $row as $inner_key => $inner_draft ) {
			$saved_at = isset( $inner_draft['_draft_saved_at'] ) ? (int) $inner_draft['_draft_saved_at'] : 0;
			if ( $saved_at < $oldest_ts ) {
				$oldest_ts  = $saved_at;
				$oldest_key = (string) $inner_key;
			}
		}

		if ( '' === $oldest_key || ! bb_draft_dispose( $user_id, 'bb_user_topic_reply_draft', $oldest_key ) ) {
			break;
		}

		++$disposed;
		$row = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );
	}

	return $disposed;
}

/**
 * One-shot healing pass for sites already carrying oversized draft rows.
 *
 * Two stages, each resumable across budget-interrupted runs via the
 * persisted `bb_draft_oneshot_state` option (without it every continuation
 * slice would restart the scan from row zero and never finish on exactly
 * the large-community sites this pass exists for):
 *
 * 1. A cursor-advanced scan over the draft rows disposes rows individually larger
 *    than the per-draft cap - the Memcached-poisoning rows this ticket is
 *    about. The aggregated forum row is healed at inner-draft granularity
 *    instead of being disposed wholesale ({@see bb_draft_heal_forum_row()}).
 * 2. One indexed SQL aggregate then finds users whose COMBINED drafts
 *    exceed the per-user budget (ten 90KB legacy drafts poison the cache
 *    as surely as one 1MB row); each is healed by oldest-first eviction in
 *    the 'heal' budget context, which deliberately bypasses the live save
 *    path's total-meta refusal - the users this pass exists for are
 *    exactly the ones over that refusal threshold. A third-party draft_*
 *    key inflating a SUM only causes a no-op heal call, because the healer
 *    re-measures with {@see bb_draft_is_draft_meta_key()} before evicting.
 *
 * Self-reschedules on the `bb_draft_oneshot` single event until both
 * stages complete, then records the durable `bb_draft_oneshot_done` option
 * (an option, not a transient - transients live in the very object cache
 * this heals) so support can tell affected customers when their mu-plugin
 * workarounds are safe to remove.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $time_budget Seconds to spend this run; 0 for unlimited.
 * @return array { @type int $healed @type bool $complete }
 */
function bb_drafts_oneshot_batch( $time_budget = 10 ) {
	global $wpdb;

	$time_budget = (int) $time_budget;
	$started_at  = time();
	$healed      = 0;
	$complete    = true;
	$max_size    = bb_draft_max_size();

	$state = get_option( 'bb_draft_oneshot_state' );
	$state = wp_parse_args(
		is_array( $state ) ? $state : array(),
		array(
			'cursor'      => 0,
			'heavy_users' => null,
		)
	);

	// Stage 1 - per-row healing scan. Skipped when a previous run already
	// finished it (heavy_users is then an array, possibly empty).
	if ( ! is_array( $state['heavy_users'] ) ) {
		$cursor = (int) $state['cursor'];

		do {
			$batch = bb_draft_get_rows_batch( $cursor, 200, false );

			foreach ( $batch['rows'] as $row ) {
				$row_user  = (int) $row['user_id'];
				$row_bytes = (int) $row['bytes'];

				if ( $row_bytes > $max_size ) {
					if ( 'bb_user_topic_reply_draft' === $row['meta_key'] ) {
						$healed += bb_draft_heal_forum_row( $row_user );
					} elseif ( bb_draft_dispose( $row_user, $row['meta_key'] ) ) {
						++$healed;
					}
				}

				// Advance row-by-row: a budget break must resume AFTER the row
				// just processed, not at the raw window's end.
				$cursor = (int) $row['umeta_id'];

				if ( 0 < $time_budget && ( time() - $started_at ) >= $time_budget ) {
					$complete = false;
					break 2;
				}
			}

			// The whole window (draft rows AND filtered third-party keys) is done.
			$cursor = (int) $batch['last_id'];

			// Window-level budget check - see bb_drafts_delete_expired(): a
			// window of only filtered-out keys never reaches the per-row check.
			if ( 0 < $time_budget && $batch['has_more'] && ( time() - $started_at ) >= $time_budget ) {
				$complete = false;
				break;
			}
		} while ( $batch['has_more'] );

		if ( ! $complete ) {
			$state['cursor'] = $cursor;
			update_option( 'bb_draft_oneshot_state', $state, false );
		} elseif ( 0 < $time_budget && ( time() - $started_at ) >= $time_budget ) {
			// Stage 1 finished, but this slice's budget is already spent. The
			// stage-2 aggregate below is a GROUP BY/HAVING over the draft-key
			// range - bounded in ROWS but not in TIME - and the upgrade routine
			// runs the first slice synchronously on an admin request, so
			// starting it here can blow max_execution_time mid-upgrade. Defer
			// it to the next slice; re-scanning the exhausted tail is a cheap
			// no-op that leaves the state shape unchanged.
			$complete        = false;
			$state['cursor'] = $cursor;
			update_option( 'bb_draft_oneshot_state', $state, false );
		} else {
			// Stage 1 finished - one indexed aggregate over the draft keys only
			// finds the aggregate-oversized users for stage 2. Persisting the
			// (small) user list instead of per-user byte totals keeps the state
			// option bounded on sites with many draft holders.
			$like_user  = $wpdb->esc_like( 'draft_user_' ) . '%';
			$like_group = $wpdb->esc_like( 'draft_group_' ) . '%';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- one-time healing aggregate over the draft-key index range.
			$heavy_users = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT user_id
					FROM {$wpdb->usermeta}
					WHERE ( meta_key = 'draft_user' OR meta_key = 'bb_user_topic_reply_draft' OR meta_key LIKE %s OR meta_key LIKE %s )
					GROUP BY user_id
					HAVING SUM(LENGTH(meta_value)) > %d",
					$like_user,
					$like_group,
					bb_draft_user_total_max_size()
				)
			);

			$state['heavy_users'] = array_map( 'intval', is_array( $heavy_users ) ? $heavy_users : array() );
			update_option( 'bb_draft_oneshot_state', $state, false );
		}
	}

	// Stage 2 - aggregate healing, draining the persisted user list.
	if ( $complete && is_array( $state['heavy_users'] ) ) {
		while ( ! empty( $state['heavy_users'] ) ) {
			$heavy_user_id = (int) array_shift( $state['heavy_users'] );

			$budget_result = bb_draft_enforce_user_budget( $heavy_user_id, '', 0, 'heal' );
			$healed       += count( $budget_result['evicted'] );

			if ( 0 < $time_budget && ( time() - $started_at ) >= $time_budget && ! empty( $state['heavy_users'] ) ) {
				$complete = false;
				update_option( 'bb_draft_oneshot_state', $state, false );
				break;
			}
		}
	}

	if ( $complete ) {
		update_option( 'bb_draft_oneshot_done', 1, false );
		delete_option( 'bb_draft_oneshot_state' );
	} elseif ( ! wp_next_scheduled( 'bb_draft_oneshot' ) ) {
		wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'bb_draft_oneshot' );
	}

	return array(
		'healed'   => $healed,
		'complete' => $complete,
	);
}

// The cleanup runs inline in cron requests (see bb_drafts_delete_expired
// for why the background-process classes are not used here).
add_action( 'bb_draft_cleanup', 'bb_drafts_delete_expired' );
add_action( 'bb_draft_oneshot', 'bb_drafts_oneshot_batch' );

add_action( 'bb_draft_cleanup_hook', 'bb_drafts_delete_expired' );

/**
 * Schedule the daily draft cleanup event.
 *
 * Scheduled directly (the polls add-on precedent): bp_core_schedule_cron()
 * queues its wp_schedule_event() on the same bp_init priority that is
 * already running, which can silently skip scheduling. On multisite only
 * the root blog schedules - usermeta is network-global, so per-subsite
 * events would duplicate the same sweep.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_drafts_schedule_cleanup() {
	if ( is_multisite() && ! bp_is_root_blog() ) {
		return;
	}

	if ( ! wp_next_scheduled( 'bb_draft_cleanup_hook' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'bb_schedule_24hours', 'bb_draft_cleanup_hook' );
	}
}
add_action( 'bp_init', 'bb_drafts_schedule_cleanup' );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Run or inspect the PROD-9621 draft cleanup from the command line.
	 *
	 * Doubles as the support/VIP verification tool: `--status` reports
	 * whether the upgrade one-shot finished (the signal that mu-plugin
	 * workarounds can be removed), and a plain run drains both the healing
	 * and expiry passes to completion without depending on cron.
	 *
	 * ## OPTIONS
	 *
	 * [--status]
	 * : Report completion state and row counts without changing anything.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	WP_CLI::add_command(
		'bb drafts cleanup',
		function ( $args, $assoc_args ) {
			if ( isset( $assoc_args['status'] ) ) {
				$oneshot_done = (int) get_option( 'bb_draft_oneshot_done', 0 );
				$total_rows   = 0;
				$oversized    = 0;
				$last_id      = 0;

				do {
					$batch   = bb_draft_get_rows_batch( $last_id, 500, false );
					$last_id = $batch['last_id'];

					foreach ( $batch['rows'] as $row ) {
						$total_rows++;
						if ( (int) $row['bytes'] > bb_draft_max_size() ) {
							$oversized++;
						}
					}
				} while ( $batch['has_more'] );

				WP_CLI::log( 'One-shot complete: ' . ( $oneshot_done ? 'yes' : 'no' ) );
				WP_CLI::log( 'Draft rows: ' . $total_rows );
				WP_CLI::log( 'Oversized rows among them: ' . $oversized );

				return;
			}

			// Drain fully regardless of size - the docblock promises completion.
			do {
				$oneshot = bb_drafts_oneshot_batch( 0 );
				WP_CLI::log( 'Healing pass: ' . $oneshot['healed'] . ' drafts removed/evicted.' );
			} while ( empty( $oneshot['complete'] ) );

			do {
				$expired = bb_drafts_delete_expired( 0 );
				WP_CLI::log( 'Expiry pass: ' . $expired['deleted'] . ' expired drafts removed.' );
			} while ( empty( $expired['complete'] ) );

			WP_CLI::success( 'Draft cleanup complete.' );
		}
	);
}
