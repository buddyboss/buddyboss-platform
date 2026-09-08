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
 * How `bp_get_user_meta_key` wraps the draft meta keys on this install.
 *
 * Every draft writer stores through `bp_update_user_meta()`, which passes
 * the key through {@see bp_get_user_meta_key()}. The maintenance layer, by
 * contrast, has to recognise keys coming back OUT of the database, so it
 * needs the inverse of that filter. The filter is arbitrary, but its
 * documented purpose (and every real use) is to wrap the key, so the wrap
 * is derived from a probe and then VERIFIED against all four canonical
 * draft shapes. A filter that is not a pure wrap (a hash, or one that
 * rewrites only some keys) is reported as non-invertible, and the callers
 * then decline to act rather than guess — see
 * {@see bb_draft_get_rows_batch()}.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array {
 *     @type string $prefix     Text the filter prepends.
 *     @type string $suffix     Text the filter appends.
 *     @type bool   $invertible Whether a stored key can be mapped back.
 * }
 */
function bb_draft_meta_key_wrap() {
	// Keyed on the probe result rather than a bare flag: a test (or a plugin
	// on a late hook) may add or remove the filter mid-request, and the memo
	// must follow it instead of pinning the first answer seen.
	static $memo = array();

	$probe    = 'bb_draft_meta_key_probe';
	$filtered = (string) bp_get_user_meta_key( $probe );

	if ( isset( $memo[ $filtered ] ) ) {
		return $memo[ $filtered ];
	}

	$identity = array(
		'prefix'     => '',
		'suffix'     => '',
		'invertible' => true,
	);

	if ( $probe === $filtered ) {
		$memo[ $filtered ] = $identity;

		return $identity;
	}

	$position = strpos( $filtered, $probe );
	$wrap     = $identity;

	if ( false === $position ) {
		$wrap['invertible'] = false;
	} else {
		$wrap['prefix'] = substr( $filtered, 0, $position );
		$wrap['suffix'] = substr( $filtered, $position + strlen( $probe ) );

		// Verify the derived wrap actually describes the real keys. A filter
		// that rewrites only some keys would otherwise hand us a wrap that
		// silently mismatches the rows we are about to delete.
		foreach ( array( 'draft_user', 'bb_user_topic_reply_draft', 'draft_user_1', 'draft_group_1' ) as $canonical ) {
			if ( (string) bp_get_user_meta_key( $canonical ) !== $wrap['prefix'] . $canonical . $wrap['suffix'] ) {
				$wrap = $identity;

				$wrap['invertible'] = false;
				break;
			}
		}
	}

	$memo[ $filtered ] = $wrap;

	return $wrap;
}

/**
 * Map a stored usermeta key back to the draft key the writers asked for.
 *
 * The maintenance passes and the size measurement both read raw keys out of
 * the database, while every writer stores the {@see bp_get_user_meta_key()}
 * form. Comparing the two directly is what made the caps, the expiry cron
 * and the healing pass inert on installs that filter that key — and worse,
 * it let a stray unfiltered row hand {@see bb_draft_dispose()} a key that
 * re-filtered onto a DIFFERENT, live row (PROD-9621 N2).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $stored_key Key as stored in the usermeta table.
 * @return string Logical draft key, or '' when the key is not a BuddyBoss draft.
 */
function bb_draft_logical_meta_key( $stored_key ) {
	$stored_key = (string) $stored_key;
	$wrap       = bb_draft_meta_key_wrap();

	if ( empty( $wrap['invertible'] ) ) {
		return '';
	}

	$logical = $stored_key;

	if ( '' !== $wrap['prefix'] ) {
		if ( 0 !== strpos( $logical, $wrap['prefix'] ) ) {
			return '';
		}

		$logical = substr( $logical, strlen( $wrap['prefix'] ) );
	}

	if ( '' !== $wrap['suffix'] ) {
		if ( substr( $logical, - strlen( $wrap['suffix'] ) ) !== $wrap['suffix'] ) {
			return '';
		}

		$logical = substr( $logical, 0, - strlen( $wrap['suffix'] ) );
	}

	return bb_draft_is_draft_meta_key( $logical ) ? $logical : '';
}

/**
 * SQL fragments matching the draft keys as they are actually stored.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array {
 *     @type bool     $invertible Whether the stored keys can be recognised at all.
 *     @type string   $where      Prepared-placeholder WHERE fragment.
 *     @type string[] $values     Values for the fragment's placeholders.
 * }
 */
function bb_draft_meta_key_sql() {
	global $wpdb;

	$wrap = bb_draft_meta_key_wrap();

	if ( empty( $wrap['invertible'] ) ) {
		return array(
			'invertible' => false,
			'where'      => '',
			'values'     => array(),
		);
	}

	$prefix = $wrap['prefix'];
	$suffix = $wrap['suffix'];

	return array(
		'invertible' => true,
		'where'      => '( meta_key = %s OR meta_key = %s OR meta_key LIKE %s OR meta_key LIKE %s )',
		'values'     => array(
			$prefix . 'draft_user' . $suffix,
			$prefix . 'bb_user_topic_reply_draft' . $suffix,
			$wpdb->esc_like( $prefix . 'draft_user_' ) . '%' . $wpdb->esc_like( $suffix ),
			$wpdb->esc_like( $prefix . 'draft_group_' ) . '%' . $wpdb->esc_like( $suffix ),
		),
	);
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
 * this budget, whatever the draft share of it is. The default leaves ~200KB
 * of headroom below the platform limit, which also covers what the measured
 * total does not count ({@see bb_draft_get_user_meta_sizes()}).
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

	// Then any data: URI that is NOT base64 - `data:image/svg+xml,<svg …>`,
	// `;utf8,`, percent-encoded, and so on. The rule above requires `;base64,`
	// and let every other form through at full size, so a member could still
	// park megabytes in a draft and only ever be refused for size, never
	// cleaned. Not an injection path - kses escapes the surviving tag to inert
	// text and leaves no live `data:` src - purely a size-coverage gap.
	//
	// ANCHORED TO ATTRIBUTE CONTEXT. Unlike the base64 payload above, this
	// rule's payload class has to allow ordinary prose characters, so matching
	// it anywhere in the content deleted everything from a member's plain-text
	// mention of `data:image/svg+xml,` to the next quote or `>` - in prose with
	// neither, to the end of the draft. Requiring `="` (or `='`, or a bare
	// unquoted attribute) in front confines it to the shape a pasted image
	// actually arrives in, and lets the payload run to its real delimiter
	// instead of stopping early at a `>` inside an SVG (PROD-9621).
	//
	// One pass per delimiter rather than one pattern with a backreference: a
	// backreferenced quote would backtrack, and every payload class here has to
	// stay a plain negated class to keep the whole thing linear - measured at
	// 9ms on a 4MB payload with no PCRE failure, same as the rule above.
	//
	// The trade is coverage of data: URIs that are not attribute values at all
	// (`url(data:…)` inside a style attribute, for instance). Those are left to
	// the per-draft size cap, which refuses the save - the outcome this rule was
	// only ever a cleanup for. Eating the member's text is the worse failure.
	$stripped = preg_replace(
		array(
			'/(=\s*")data:[a-z0-9.+-]+\/[a-z0-9.+-]+[a-z0-9;=.+-]*,[^"]*/i',
			'/(=\s*\')data:[a-z0-9.+-]+\/[a-z0-9.+-]+[a-z0-9;=.+-]*,[^\']*/i',
			'/(=\s*)data:[a-z0-9.+-]+\/[a-z0-9.+-]+[a-z0-9;=.+-]*,[^\s"\'>]*/i',
		),
		'$1',
		$content
	);

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
 * Stamp every attachment a draft payload claims, before any size cap can refuse it.
 *
 * `bb_media_draft` is what keeps `bp_media_delete_orphaned_attachments()` from
 * hard-deleting a freshly uploaded file six hours later. Whether that stamp is
 * applied must NOT depend on whether the draft's TEXT fits the size caps: the
 * member has already uploaded the file and it is sitting in their composer
 * either way, so a draft refused for being too large would otherwise leave the
 * upload unprotected and the cron would delete it out from under them.
 *
 * Collecting the stamps and applying them only once every cap accepted was
 * meant to stop a rejected save leaving orphan-protected attachments behind.
 * It traded a bounded leak for member data loss, which is the worse of the two
 * - a stamped attachment nothing references wastes disk until the draft
 * cleanup reaches it, a deleted one is gone (PROD-9621 BLOCKER-1).
 *
 * Ownership is still enforced per ID, and each list is bounded before any
 * per-ID lookup runs, so a crafted payload cannot stamp other members'
 * attachments or force thousands of uncached queries.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $lists   Attachment lists, each an array of entries carrying an `id`.
 * @param int   $user_id Acting user ID.
 * @return int[] Attachment IDs stamped.
 */
function bb_draft_protect_payload_attachments( $lists, $user_id ) {
	$user_id = (int) $user_id;
	$stamped = array();

	if ( empty( $lists ) || ! is_array( $lists ) || $user_id < 1 ) {
		return $stamped;
	}

	foreach ( $lists as $list ) {
		if ( empty( $list ) || ! is_array( $list ) ) {
			continue;
		}

		// Same bound the normalisation loops apply, enforced here too because
		// this pass runs before them.
		if ( 50 < count( $list ) ) {
			$list = array_slice( $list, 0, 50 );
		}

		foreach ( $list as $entry ) {
			$attachment_id = 0;

			if ( is_array( $entry ) && ! empty( $entry['id'] ) ) {
				$attachment_id = (int) $entry['id'];
			}

			if ( $attachment_id < 1 || isset( $stamped[ $attachment_id ] ) ) {
				continue;
			}

			if ( ! bb_draft_user_can_manage_attachment( $attachment_id, $user_id ) ) {
				continue;
			}

			update_post_meta( $attachment_id, 'bb_media_draft', 1 );

			$stamped[ $attachment_id ] = true;
		}
	}

	return array_keys( $stamped );
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
 * Reads from the primed meta cache, so no extra query runs. get_user_meta()
 * hands back values already unserialized, so each one is re-serialized here
 * to recover its stored width — the per-draft numbers therefore match the
 * database exactly.
 *
 * `total` is a close LOWER BOUND on the real cache item rather than its
 * size: it sums value bytes only, so the meta_key strings and the outer
 * array's own serialization overhead are not counted. On a member carrying
 * hundreds of meta rows that is a few KB. The gap is deliberate headroom —
 * {@see bb_draft_user_meta_budget()} defaults to 800 KB against a ~1 MB
 * platform item limit, which is what absorbs it.
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

			// Keyed by the LOGICAL draft key, so the cap arithmetic and every
			// key handed on to bp_get_user_meta()/bb_draft_dispose() speak the
			// same language the writers used (PROD-9621 N2).
			$logical_key = bb_draft_logical_meta_key( $meta_key );

			if ( '' !== $logical_key ) {
				$sizes['drafts'][ $logical_key ] = isset( $sizes['drafts'][ $logical_key ] ) ? $sizes['drafts'][ $logical_key ] + $bytes : $bytes;
			}
		}
	}

	$memo[ $user_id ] = $sizes;

	return $sizes;
}

/**
 * Serialized width of one array key, as `serialize()` writes it.
 *
 * A row is wider than the sum of its entries: every element also carries its
 * key. Size bookkeeping that walks a row element by element has to account
 * for both, or it drifts above the real width and evicts too much.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $key Array key.
 * @return int Bytes the key occupies in a serialized array.
 */
function bb_draft_serialized_key_bytes( $key ) {
	$key = (string) $key;

	// A serialized string element is written as its length plus quotes and
	// terminators, which is what this reproduces.
	return strlen( 's:' . strlen( $key ) . ':"' . $key . '";' );
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
 * Strip the video poster frames a stored draft does not need to keep.
 *
 * `js_preview` is a `canvas.toDataURL()` PNG of the first video frame, up to
 * 1920x1080. It is a convenience for redrawing the composer thumbnail and
 * nothing depends on it surviving a round trip — the poster is regenerated
 * from the attachment on publish. On a legacy row written before the client
 * stopped sending it, it is routinely the overwhelming majority of the
 * stored bytes: measured at 99.8% of a 150 KB row, which drops to 260 bytes
 * once the frame is gone.
 *
 * That makes it the first thing to drop when a row is over the cap, and the
 * reason the healing paths must try shedding before they delete: without it
 * they destroy the member's unpublished text to reclaim space that the
 * poster alone was using (PROD-9621 Q6).
 *
 * Handles both stored shapes, the same pair
 * {@see bb_draft_collect_attachment_ids()} handles: the activity composer
 * stores `data['video']` as a real array, the forum composer stores
 * `data['bbp_video']` as a JSON string. Idempotent — shedding an
 * already-shed draft returns it unchanged, so callers may compare sizes
 * before and after to decide whether anything was reclaimed.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $draft Draft entry.
 * @return array The draft with any poster frames removed.
 */
function bb_draft_shed_preview_frames( $draft ) {
	if ( empty( $draft['data'] ) || ! is_array( $draft['data'] ) ) {
		return $draft;
	}

	if ( ! empty( $draft['data']['video'] ) && is_array( $draft['data']['video'] ) ) {
		foreach ( $draft['data']['video'] as $index => $entry ) {
			if ( is_array( $entry ) && isset( $entry['js_preview'] ) ) {
				unset( $draft['data']['video'][ $index ]['js_preview'] );
			}
		}
	}

	if ( ! empty( $draft['data']['bbp_video'] ) && is_string( $draft['data']['bbp_video'] ) ) {
		$entries = json_decode( $draft['data']['bbp_video'], true );

		if ( ! empty( $entries ) && is_array( $entries ) ) {
			$shed = false;

			foreach ( $entries as $index => $entry ) {
				if ( is_array( $entry ) && isset( $entry['js_preview'] ) ) {
					unset( $entries[ $index ]['js_preview'] );
					$shed = true;
				}
			}

			// Re-encoded only when something was actually removed: a
			// no-op re-encode would still change the stored string
			// (escaping, key order) and make the caller's before/after
			// size comparison report a reclaim that did not happen.
			if ( $shed ) {
				$draft['data']['bbp_video'] = wp_json_encode( $entries );
			}
		}
	}

	return $draft;
}

/**
 * Try to bring an oversized single-draft row under the cap by shedding.
 *
 * For the one-draft-per-row keys (`draft_user`, `draft_user_{id}`,
 * `draft_group_{id}`) there is nothing to evict inside the row, so the
 * healing pass had only one move: delete the row. That destroyed the
 * member's unpublished text whenever the oversize came from a poster frame
 * the draft did not need — which is the usual case on a legacy row
 * (PROD-9621 Q6).
 *
 * Writes back only when shedding actually brings the row under the cap, so
 * a row that is genuinely too large still falls through to disposal and the
 * healing guarantee is unchanged.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int    $user_id  Owning user ID.
 * @param string $meta_key Draft usermeta key.
 * @return bool Whether the row was salvaged and stored.
 */
function bb_draft_salvage_oversized_draft( $user_id, $meta_key ) {
	$user_id = (int) $user_id;

	if ( $user_id <= 0 || ! bb_draft_is_draft_meta_key( $meta_key ) ) {
		return false;
	}

	$stored = bp_get_user_meta( $user_id, $meta_key, true );

	if ( ! is_array( $stored ) || empty( $stored ) ) {
		return false;
	}

	$shed = bb_draft_shed_preview_frames( $stored );

	// Nothing was reclaimable, so this row is oversized on its own merits.
	if ( maybe_serialize( $shed ) === maybe_serialize( $stored ) ) {
		return false;
	}

	if ( strlen( maybe_serialize( $shed ) ) > bb_draft_max_size() ) {
		return false;
	}

	bp_update_user_meta( $user_id, $meta_key, $shed );
	bb_draft_flush_user_meta_sizes( $user_id );

	return true;
}

/**
 * Release the draft protection stamps from a draft's attachments.
 *
 * Removes the `bb_media_draft` / `bb_activity_post_feature_image_draft`
 * post meta so the existing orphaned-attachment crons become able to
 * collect the files again. Only attachments the user owns are touched.
 *
 * Pass `$retain_entries` whenever the draft being released is ONE inner
 * entry of a row that keeps others. The aggregated forum row holds every
 * inner topic/reply draft together and the shared reply modal carries its
 * content across reply targets, so one attachment is routinely referenced by
 * several inner drafts at once; releasing an inner draft without excluding
 * what its siblings still hold let the orphan crons delete files a stored
 * draft was still pointing at. {@see bb_draft_heal_forum_row()} and the
 * forum handler's eviction branch already apply this whole-row exclusion
 * (PROD-9621).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $draft          Draft array.
 * @param int   $user_id        Owning user ID.
 * @param array $retain_entries Optional. Draft entries that survive this
 *                              removal and whose attachments must keep
 *                              their stamps.
 * @return void
 */
function bb_draft_unstamp_attachments( $draft, $user_id, $retain_entries = array() ) {
	$attachment_ids = bb_draft_collect_attachment_ids( $draft );
	$retained       = array();

	if ( ! empty( $retain_entries ) && is_array( $retain_entries ) ) {
		foreach ( $retain_entries as $retain_entry ) {
			$retained = array_merge( $retained, bb_draft_collect_attachment_ids( $retain_entry ) );
		}
	}

	foreach ( $attachment_ids as $attachment_id ) {
		if ( in_array( (int) $attachment_id, $retained, true ) ) {
			continue;
		}

		if ( ! bb_draft_user_can_manage_attachment( $attachment_id, $user_id ) ) {
			continue;
		}

		delete_post_meta( $attachment_id, 'bb_media_draft' );
		delete_post_meta( $attachment_id, 'bb_activity_post_feature_image_draft' );
	}
}

/**
 * Release the draft stamps a replaced draft entry no longer needs.
 *
 * Replacing a stored draft must NOT unstamp the whole previous entry: a
 * restored draft re-sends its stored attachment list verbatim, so the
 * attachments the member is still drafting with appear in both entries and
 * would lose their orphan protection for good — the orphan-cleanup crons
 * then reap a file the stored draft still references. Only the set
 * difference (held before, not held now) may be released.
 *
 * "Not held now" means held by NOTHING that is still stored, not merely by
 * the entry that replaced this one. The aggregated forum row keeps every
 * inner topic/reply draft together, and the composer carries its content
 * across reply targets, so the same attachment is routinely referenced by
 * several inner drafts at once. Comparing against the replacing entry alone
 * released files a sibling inner draft still pointed at, and the
 * orphan-cleanup crons then hard-deleted them - the exact outcome the
 * paragraph above forbids. Pass every entry that survives the write in
 * `$retain_entries`; the eviction path in the forum handler already applies
 * this same whole-row exclusion (PROD-9621).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $previous_entry Draft entry being replaced.
 * @param array $current_entry  Draft entry replacing it.
 * @param int   $user_id        Owning user ID.
 * @param array $retain_entries Optional. Draft entries that survive the write
 *                              and whose attachments must keep their stamps.
 * @return int[] Attachment IDs whose stamps were released.
 */
function bb_draft_release_replaced_attachments( $previous_entry, $current_entry, $user_id, $retain_entries = array() ) {
	$retained = bb_draft_collect_attachment_ids( $current_entry );

	if ( ! empty( $retain_entries ) && is_array( $retain_entries ) ) {
		foreach ( $retain_entries as $retain_entry ) {
			$retained = array_merge( $retained, bb_draft_collect_attachment_ids( $retain_entry ) );
		}
	}

	$released = array_values( array_diff( bb_draft_collect_attachment_ids( $previous_entry ), $retained ) );
	$affected = array();

	foreach ( $released as $attachment_id ) {
		if ( ! bb_draft_user_can_manage_attachment( $attachment_id, $user_id ) ) {
			continue;
		}

		delete_post_meta( $attachment_id, 'bb_media_draft' );
		delete_post_meta( $attachment_id, 'bb_activity_post_feature_image_draft' );

		$affected[] = (int) $attachment_id;
	}

	return $affected;
}

/**
 * Dispose of one stored draft: release its attachment stamps and remove it.
 *
 * The shared removal path: budget eviction, the cleanup cron, the upgrade
 * healing routine and the activity composer's discard all route through it,
 * so those deletion routes treat attachments, row shape and the memoized
 * sizes identically. The forum draft handler is the one exception — it
 * rewrites the aggregated row in place around a merge of sibling drafts and
 * so writes directly, flushing the sizes itself. For the aggregated forum
 * row, pass `$inner_key` to remove one inner draft; an emptied aggregate row
 * is deleted rather than stored as an empty array.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * `$inner_key` is the WHOLE-ROW sentinel when empty, so a caller iterating a
 * stored row must never hand it an empty key: a legacy row carrying an
 * empty-string inner key would otherwise delete the member's entire row
 * instead of that one entry. The loops that walk stored rows guard for it
 * (PROD-9621 M5).
 *
 * @param int    $user_id   Owning user ID.
 * @param string $meta_key  Draft usermeta key.
 * @param string $inner_key Optional. Inner draft key inside the forum aggregate
 *                          row; empty means "remove the whole row".
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
			bb_draft_flush_user_meta_sizes( $user_id );

			return true;
		}

		return false;
	}

	if ( '' !== $inner_key ) {
		if ( ! isset( $stored[ $inner_key ] ) ) {
			return false;
		}

		// Only this inner draft goes; the rest of the row stays, so anything
		// a surviving sibling still references must keep its stamp. Without
		// this the member's own discard - and every budget eviction and
		// expiry sweep, which all route through here - released files another
		// stored inner draft was still using (PROD-9621).
		$retain_entries = $stored;
		unset( $retain_entries[ $inner_key ] );

		bb_draft_unstamp_attachments( $stored[ $inner_key ], $user_id, $retain_entries );
		unset( $stored[ $inner_key ] );

		if ( empty( $stored ) ) {
			bp_delete_user_meta( $user_id, $meta_key );
		} else {
			bp_update_user_meta( $user_id, $meta_key, $stored );
		}

		bb_draft_flush_user_meta_sizes( $user_id );

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
 * {@see bb_draft_user_total_max_size()}, the oldest drafts are evicted
 * through {@see bb_draft_dispose()} until the new draft fits, by their
 * `_draft_saved_at` stamp; unstamped legacy drafts count as oldest. When the
 * user's TOTAL meta would exceed {@see bb_draft_user_meta_budget()}, the
 * save is refused instead — the draft system must never be what pushes a
 * user's meta cache entry past the platform item limit.
 *
 * IMPORTANT — what `$current_key` excludes. The whole row named by
 * `$current_key` is protected from eviction, which is correct for an
 * activity draft (one row, one draft) but means that when `$current_key` is
 * `bb_user_topic_reply_draft` this function **cannot** evict the inner
 * topic/reply drafts sharing that row — and that row is the only place
 * inner forum drafts live. So for the aggregate call path it cannot free
 * space inside the very row it is being asked to make room in; it can only
 * evict the member's OTHER draft rows.
 *
 * That is why {@see bb_forums_trim_draft_row()} runs first in the forum
 * handler: it trims the aggregate row down, and this function then handles
 * the cross-row budget. A caller passing the aggregate key without trimming
 * first will not get inner-draft eviction from here.
 *
 * Do NOT add inner-row eviction here. It reads like a missing feature and a
 * protected-inner-key parameter looks like the fix, so it has been proposed
 * more than once; it was built and measured, and it does not work. This
 * function evicts through {@see bb_draft_dispose()}, which WRITES TO
 * STORAGE, and the forum handler then writes its own in-memory copy of the
 * row over that write. The eviction is therefore announced but never
 * performed: the response still reports the key as evicted, so the client
 * drops its local copy, while the row stays over budget on the server - a
 * manufactured data loss, strictly worse than the gap this paragraph
 * describes. Returning keys for the caller to apply instead of writing them
 * only duplicates {@see bb_forums_trim_draft_row()}, which already does
 * exactly that one call earlier. Trim-then-budget is required ordering, not
 * convenience. Moot for the activity caller either way, since an activity
 * draft is one draft per meta row (PROD-9621 M4).
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
				// See bb_draft_dispose(): an empty inner key means "whole row".
				if ( '' === (string) $inner_key ) {
					continue;
				}

				$candidates[] = array(
					'meta_key'  => $meta_key,
					'inner_key' => (string) $inner_key,
					'saved_at'  => isset( $inner_draft['_draft_saved_at'] ) ? (int) $inner_draft['_draft_saved_at'] : 0,
					// The row also carries every element's KEY, so evicting this
					// entry frees the entry plus its key. Counting only the entry
					// under-credits each eviction and the oldest-first loop keeps
					// going, destroying drafts it did not need to - the same
					// undercount f643e4d0c8 fixed in bb_forums_trim_draft_row(),
					// which is the sibling of this loop (PROD-9621).
					'bytes'     => strlen( maybe_serialize( $inner_draft ) ) + bb_draft_serialized_key_bytes( (string) $inner_key ),
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
 * Resolve a forum draft inner data key into the object and IDs it addresses.
 *
 * Accepted shapes: `draft_topic`, `draft_discussion_{forum_id}`,
 * `draft_reply`, `draft_reply_{topic_id}` and
 * `draft_reply_{topic_id}_{reply_to}` — the bare shapes occur when no
 * forum/topic ID is resolvable on the page.
 *
 * The key already encodes whether the member is drafting a topic or a reply,
 * and which forum it belongs to. Returning that instead of a bare boolean is
 * what lets the save handler apply the SAME per-object, per-forum rules the
 * publish path applies, rather than one loose site-wide check (PROD-9621).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $data_key Client-supplied inner draft key.
 * @return array|false {
 *     False when the key matches no shape or names a post that does not exist.
 *
 *     @type string $object   'topic' or 'reply'.
 *     @type int    $forum_id Forum the draft belongs to; 0 when not resolvable.
 *     @type int    $topic_id Topic being replied to; 0 for topic drafts.
 *     @type int    $reply_id Reply being replied to; 0 when absent.
 * }
 */
function bb_draft_topic_reply_key_context( $data_key ) {
	$context = array(
		'object'   => '',
		'forum_id' => 0,
		'topic_id' => 0,
		'reply_id' => 0,
	);

	if ( 'draft_topic' === $data_key ) {
		$context['object'] = 'topic';

		return $context;
	}

	if ( 'draft_reply' === $data_key ) {
		$context['object'] = 'reply';

		return $context;
	}

	if ( preg_match( '/^draft_discussion_(\d+)$/', $data_key, $matches ) ) {
		$forum = get_post( (int) $matches[1] );

		if ( empty( $forum ) || bbp_get_forum_post_type() !== $forum->post_type ) {
			return false;
		}

		$context['object']   = 'topic';
		$context['forum_id'] = (int) $matches[1];

		return $context;
	}

	if ( preg_match( '/^draft_reply_(\d+)(?:_(\d+))?$/', $data_key, $matches ) ) {
		$topic = get_post( (int) $matches[1] );

		if ( empty( $topic ) || bbp_get_topic_post_type() !== $topic->post_type ) {
			return false;
		}

		if ( isset( $matches[2] ) ) {
			$reply = get_post( (int) $matches[2] );

			if ( empty( $reply ) || bbp_get_reply_post_type() !== $reply->post_type ) {
				return false;
			}

			$context['reply_id'] = (int) $matches[2];
		}

		$context['object']   = 'reply';
		$context['topic_id'] = (int) $matches[1];
		// Derived, never client-supplied: the forum a reply draft belongs to is
		// whichever forum owns the topic named in the key.
		$context['forum_id'] = (int) bbp_get_topic_forum_id( (int) $matches[1] );

		return $context;
	}

	return false;
}

/**
 * Validate a forum draft inner data key against the shapes the forum JS builds.
 *
 * Thin wrapper over {@see bb_draft_topic_reply_key_context()}, kept because
 * the key shape alone is all the fetch and discard paths need.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $data_key Client-supplied inner draft key.
 * @return bool True when the key matches a real forum/topic/reply.
 */
function bb_draft_validate_topic_reply_data_key( $data_key ) {
	return false !== bb_draft_topic_reply_key_context( $data_key );
}

/**
 * Whether a member may SAVE a forum draft for one resolved draft key.
 *
 * Mirrors the publish path per object rather than accepting anyone who can
 * publish either kind of post anywhere: a reply-only community (announcement
 * forums) must not be able to store topic drafts, and a member who cannot
 * even see a forum must not be able to store drafts against it. The forum
 * gate is skipped when the key carries no resolvable forum, which is the
 * documented bare-shape case.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $key_context Resolved context from {@see bb_draft_topic_reply_key_context()}.
 * @param int   $user_id     Acting user ID.
 * @return bool True when the member may save this draft.
 */
function bb_draft_user_can_save_topic_reply_draft( $key_context, $user_id ) {
	if ( empty( $key_context['object'] ) ) {
		return false;
	}

	$can_save = ( 'topic' === $key_context['object'] )
		? bbp_current_user_can_publish_topics()
		: bbp_current_user_can_publish_replies();

	if ( $can_save && ! empty( $key_context['forum_id'] ) ) {
		$can_save = bbp_user_can_view_forum(
			array(
				'user_id'  => (int) $user_id,
				'forum_id' => (int) $key_context['forum_id'],
			)
		);
	}

	/**
	 * Filters whether a member may save a forum topic/reply draft.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param bool  $can_save    Whether the save is allowed.
	 * @param array $key_context Resolved draft key context.
	 * @param int   $user_id     Acting user ID.
	 */
	return (bool) apply_filters( 'bb_draft_user_can_save_topic_reply_draft', $can_save, $key_context, $user_id );
}

/**
 * Whether a stored forum draft entry actually holds something to restore.
 *
 * A draft that carries neither text nor an attachment is not a draft: there
 * is nothing to put back in the composer. Offering one anyway is what a
 * member sees as the "Draft" indicator sitting over an empty box.
 *
 * Rows in that state exist on live installs. The composer used to force its
 * validity flag true on the strength of the copy ALREADY STORED and then
 * write the freshly-serialized (empty) form over it, so emptying the editor
 * replaced saved text with nothing while keeping `is_content_valid` true
 * (PROD-9621 Q12). The client no longer does that — but it also no longer
 * overwrites those rows, so the ones already written would otherwise show a
 * phantom draft forever. This is the read-side predicate that makes them
 * inert; a real save from the member replaces the row and it becomes a
 * normal draft again.
 *
 * Only member-authored payload counts. Tags, the subscription checkbox, the
 * sticky flag and the topic/reply IDs travel with every serialized form and
 * say nothing about whether there is content to restore — the same line the
 * composer draws when it decides whether a draft is worth saving.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $entry Stored forum draft entry (`object`, `data`, ...).
 * @return bool True when the entry holds restorable content.
 */
function bb_draft_topic_reply_entry_has_payload( $entry ) {
	if ( empty( $entry['data'] ) || ! is_array( $entry['data'] ) ) {
		return false;
	}

	$data = $entry['data'];

	// Attachments of any kind make the draft worth restoring on their own —
	// a photo-only or document-only draft is legitimate. Resolved through the
	// shared collector so this agrees with every other attachment-aware path.
	$attachment_ids = bb_draft_collect_attachment_ids( $entry );

	if ( ! empty( $attachment_ids ) ) {
		return true;
	}

	// A GIF and a link preview are payload too, and neither is an attachment
	// post, so the collector above cannot see them. `bb_link_url` is the
	// composer's derived copy of the link preview and is checked because the
	// restore itself treats it as sufficient reason to rebuild the draft
	// (appendReplyDraftData(), both packs) - the two must agree on what counts
	// as content or a link-only draft would be offered and then refused, or
	// masked while the composer still wanted it.
	foreach ( array( 'bbp_media_gif', 'link_preview_data', 'bb_link_url' ) as $key ) {
		if ( ! empty( $data[ $key ] ) && '[]' !== $data[ $key ] ) {
			return true;
		}
	}

	// Text, judged after tag stripping so markup a member never sees - an
	// empty editor commonly serializes as "<p></p>" or "<br>" - does not
	// count as content.
	foreach ( array( 'bbp_topic_title', 'bbp_topic_content', 'bbp_reply_content' ) as $key ) {
		if ( empty( $data[ $key ] ) || ! is_string( $data[ $key ] ) ) {
			continue;
		}

		// Entities are decoded, and a non-breaking space counted as
		// whitespace, so this answers the same as the client's
		// `$( $.parseHTML( x ) ).text().trim()`: parseHTML decodes entities and
		// JavaScript's trim() treats U+00A0 as whitespace. An editor the member
		// left empty routinely still serializes as "<p>&nbsp;</p>", and the two
		// sides disagreeing about that would offer a draft the composer then
		// declines to save.
		$text     = html_entity_decode( wp_strip_all_tags( $data[ $key ] ), ENT_QUOTES, 'UTF-8' );
		$stripped = preg_replace( '/[\s\x{00A0}]+/u', '', $text );

		if ( null === $stripped ) {
			// Invalid UTF-8 broke the match. Fall back rather than conclude the
			// draft is empty - the safe direction is to keep offering it.
			$stripped = trim( $text );
		}

		if ( '' !== $stripped ) {
			return true;
		}
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
 * Byte budget for one maintenance window's `meta_value` payload.
 *
 * The draft rows this machinery exists to clean up are the oversized ones -
 * the reporting customer's are ~1.5MB each. A window bounded only by ROW
 * COUNT therefore has no bound on memory: 200 such rows is ~300MB of
 * `meta_value` before mysqli's buffered copy and before unserializing.
 * A fatal there is worse than a slow sweep, because the cursor is persisted
 * only on a clean budget break - so the next run restarts at the same window
 * and dies again, and the sweep never progresses.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return int Byte budget for one window's values. Default 10 MB.
 */
function bb_draft_batch_max_bytes() {

	/**
	 * Filters the byte budget for one draft maintenance window.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $max_bytes Byte budget. Default 10 MB.
	 */
	return (int) apply_filters( 'bb_draft_batch_max_bytes', 10 * MB_IN_BYTES );
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
 * resolve through {@see bb_draft_logical_meta_key()} before it is acted on,
 * because LIKE cannot express "numeric suffix" and third-party plugins may
 * store their own draft_* keys. The patterns are built from
 * {@see bb_draft_meta_key_wrap()} so they match the keys the writers really
 * stored; when that wrap cannot be inverted the batch returns nothing.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * Values are fetched in a SECOND query, for the trimmed window only, so the
 * windowing query never carries `meta_value` and the payload pulled into PHP
 * is bounded in BYTES as well as rows ({@see bb_draft_batch_max_bytes()}).
 * At least one row is always kept, so a row wider than the whole budget still
 * makes progress rather than stalling the cursor.
 *
 * @param int  $last_umeta_id Resume after this row ID.
 * @param int  $limit         Maximum rows to fetch.
 * @param bool $with_values   Whether to fetch meta_value for the window.
 * @param int  $max_bytes     Optional. Byte budget for the window's values;
 *                            0 uses {@see bb_draft_batch_max_bytes()}.
 * @return array {
 *     @type array[] $rows     Validated draft rows (umeta_id, user_id, meta_key, bytes[, meta_value]).
 *     @type int     $last_id  Raw scan cursor - resume after this row ID.
 *     @type bool    $has_more Whether the raw window was full (more rows may exist).
 * }
 */
function bb_draft_get_rows_batch( $last_umeta_id = 0, $limit = 200, $with_values = true, $max_bytes = 0 ) {
	global $wpdb;

	$key_sql   = bb_draft_meta_key_sql();
	$max_bytes = (int) $max_bytes;

	if ( $with_values && $max_bytes <= 0 ) {
		$max_bytes = bb_draft_batch_max_bytes();
	}

	// A `bp_get_user_meta_key` filter this code cannot invert means a stored
	// key can no longer be matched to the draft it belongs to. Deleting on a
	// guess is the one outcome worse than not sweeping, so the sweep declines.
	if ( empty( $key_sql['invertible'] ) ) {
		return array(
			'rows'     => array(),
			'last_id'  => (int) $last_umeta_id,
			'has_more' => false,
		);
	}

	$query_values   = $key_sql['values'];
	$query_values[] = (int) $last_umeta_id;
	$query_values[] = (int) $limit;

	// Metadata first, ALWAYS - never `meta_value` in the windowing query. The
	// widths come from LENGTH() so the window can be byte-bounded before any
	// payload is pulled into PHP (PROD-9621 B2).
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- maintenance scan; $key_sql['where'] carries only placeholders.
	$rows = $wpdb->get_results(
		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- the sniff cannot count placeholders it never saw interpolated; the WHERE fragment carries 4, plus the 2 appended here = the 6 values passed.
		$wpdb->prepare(
			"SELECT umeta_id, user_id, meta_key, LENGTH(meta_value) AS bytes
			FROM {$wpdb->usermeta}
			WHERE {$key_sql['where']}
			AND umeta_id > %d
			ORDER BY umeta_id ASC
			LIMIT %d",
			$query_values
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

	$raw_count   = count( $rows );
	$window_full = ( $raw_count === (int) $limit );

	// Trim the window to the byte budget. At least one row is always kept, so a
	// single row wider than the whole budget still makes progress instead of
	// stalling the cursor forever.
	if ( $with_values && $max_bytes > 0 ) {
		$kept  = array();
		$bytes = 0;

		foreach ( $rows as $row ) {
			$row_bytes = (int) $row['bytes'];

			if ( ! empty( $kept ) && ( $bytes + $row_bytes ) > $max_bytes ) {
				// Trimmed, so more rows certainly remain in this key range.
				$window_full = true;
				break;
			}

			$bytes += $row_bytes;
			$kept[] = $row;
		}

		$rows      = $kept;
		$raw_count = count( $rows );
	}

	$max_id = (int) $rows[ $raw_count - 1 ]['umeta_id'];

	// Only now pull the payloads, for the trimmed window only.
	if ( $with_values ) {
		$ids          = wp_list_pluck( $rows, 'umeta_id' );
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- maintenance fetch; $placeholders is one generated %d per id, which the sniff cannot see through the interpolation.
		$values = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT umeta_id, meta_value FROM {$wpdb->usermeta} WHERE umeta_id IN ({$placeholders})",
				array_map( 'intval', $ids )
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		$by_id = array();
		foreach ( (array) $values as $value_row ) {
			$by_id[ (int) $value_row['umeta_id'] ] = $value_row['meta_value'];
		}

		foreach ( $rows as $index => $row ) {
			// A row deleted between the two queries simply reads as empty and is
			// then skipped by the callers' own shape checks.
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- an array key on a row already fetched, not a query argument.
			$rows[ $index ]['meta_value'] = isset( $by_id[ (int) $row['umeta_id'] ] ) ? $by_id[ (int) $row['umeta_id'] ] : '';
		}
	}

	return array(
		// The cursor advances over the RAW window: a window consisting entirely
		// of filtered-out third-party keys must not end the scan early.
		// Every surviving row carries the LOGICAL key in `meta_key`, so callers
		// may pass it straight to bb_draft_dispose()/bp_get_user_meta() without
		// re-filtering it onto a different row; `stored_meta_key` keeps the raw
		// value for diagnostics (PROD-9621 N2).
		'rows'     => array_values(
			array_filter(
				array_map(
					function ( $row ) {
						$row['stored_meta_key'] = $row['meta_key'];
						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- an array key on a row already fetched, not a query argument.
						$row['meta_key'] = bb_draft_logical_meta_key( $row['meta_key'] );

						return $row;
					},
					$rows
				),
				function ( $row ) {
					return '' !== $row['meta_key'];
				}
			)
		),
		'last_id'  => $max_id,
		'has_more' => $window_full,
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
 * reached at all. Because that cursor is shared by the recurring event and
 * the continuation event, the sweep holds the `bb_draft_cleanup_lock`
 * transient for its duration; a run that finds the lock held returns
 * `locked` and leaves the work to the holder.
 *
 * @return array { @type int $deleted @type bool $complete @type bool $locked }
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $time_budget Seconds to spend this run; 0 for unlimited.
 */
function bb_drafts_delete_expired( $time_budget = 10 ) {
	$time_budget       = (int) $time_budget;
	$started_at        = time();
	$retention_seconds = bb_draft_retention_seconds();
	$deleted           = 0;
	$complete          = true;

	// The daily recurring event and the self-scheduled continuation both run
	// this function against ONE persisted cursor. Overlapping runs can leave
	// the slower run's cursor behind after the faster one finished and deleted
	// it, and the next slice then skips every row below it. Serialize them.
	// A lock lost to an unreliable object cache only costs duplicate work -
	// disposal is idempotent - so failing open is the safe direction here.
	if ( get_transient( 'bb_draft_cleanup_lock' ) ) {
		return array(
			'deleted'  => 0,
			'complete' => false,
			'locked'   => true,
		);
	}

	set_transient( 'bb_draft_cleanup_lock', 1, 5 * MINUTE_IN_SECONDS );

	$cursor = (int) get_option( 'bb_draft_cleanup_cursor', 0 );

	// Expiry switched off - delete nothing. Guarded here rather than relying on
	// the cutoff arithmetic, where a zero window would expire every draft.
	if ( 1 > $retention_seconds ) {
		delete_option( 'bb_draft_cleanup_cursor' );
		delete_transient( 'bb_draft_cleanup_lock' );

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
						// An empty inner key is the whole-row sentinel for
						// bb_draft_dispose(), so a legacy row carrying one must
						// not be routed through it - that would delete the
						// member's other drafts too (PROD-9621 M5).
						if ( '' === (string) $inner_key ) {
							continue;
						}

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

		// Persisted after EVERY window, not only on a clean budget break: an
		// interruption that never returns here (a fatal, a killed worker) would
		// otherwise leave the cursor where the previous run left it, and the
		// next run would redo the same window indefinitely (PROD-9621 B2).
		update_option( 'bb_draft_cleanup_cursor', $cursor, false );

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

	// Released only after the cursor is settled, so a run starting the instant
	// this one returns cannot read a half-updated cursor.
	delete_transient( 'bb_draft_cleanup_lock' );

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
 * than one draft's cap. Inner drafts over the per-draft cap are dropped
 * individually; the row is then trimmed oldest-first to the per-user draft
 * budget. Only a corrupt (non-array) row is disposed wholesale.
 *
 * Decides everything in memory and writes ONCE. Routing each inner drop
 * through {@see bb_draft_dispose()} meant a full-row read plus a full-row
 * write per drop, and the trim loop re-serialized the whole row on every
 * iteration - O(n·B) on rows that are megabytes wide by definition. On a
 * 5MB row with 40 inner drafts that was ~200MB of serialization and 40
 * multi-MB UPDATEs inside an upgrade request (PROD-9621 H2).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $user_id Owning user ID.
 * @return int Number of inner drafts acted on - disposed plus salvaged.
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
	$removed  = array();
	$salvaged = 0;

	// Pass 1 - inner drafts individually over the per-draft cap. Sizes are
	// measured once, here, and reused by the trim below.
	$sizes = array();

	foreach ( $row as $inner_key => $inner_draft ) {
		$inner_bytes = strlen( maybe_serialize( $inner_draft ) );

		if ( $inner_bytes > $max_size ) {
			// Shed the poster frames before deleting. Kept symmetric with the
			// one-draft-per-row path in bb_drafts_oneshot_batch(): an inner
			// draft that only broke the cap because of a poster keeps its text
			// instead of being dropped (PROD-9621 Q6).
			$shed       = bb_draft_shed_preview_frames( $inner_draft );
			$shed_bytes = strlen( maybe_serialize( $shed ) );

			if ( $shed_bytes <= $max_size ) {
				$row[ $inner_key ]            = $shed;
				$sizes[ (string) $inner_key ] = $shed_bytes;
				++$salvaged;
				continue;
			}

			$removed[ (string) $inner_key ] = $inner_draft;
			unset( $row[ $inner_key ] );
			++$disposed;
			continue;
		}

		$sizes[ (string) $inner_key ] = $inner_bytes;
	}

	// Pass 2 - oldest-first down to the per-user draft budget. The row width is
	// tracked by subtracting each removed element instead of re-serializing the
	// row, which is what made this quadratic. An element costs its entry bytes
	// PLUS its serialized key, so both are subtracted: counting only the entry
	// leaves the tracked width above the truth and over-evicts the member's
	// drafts (17 too many on a 1000-entry row, measured).
	$total_cap = bb_draft_user_total_max_size();
	$row_bytes = strlen( maybe_serialize( $row ) );

	if ( $row_bytes > $total_cap && ! empty( $row ) ) {
		$ordered = array();

		foreach ( $row as $inner_key => $inner_draft ) {
			$ordered[] = array(
				'inner_key' => (string) $inner_key,
				'saved_at'  => isset( $inner_draft['_draft_saved_at'] ) ? (int) $inner_draft['_draft_saved_at'] : 0,
			);
		}

		usort(
			$ordered,
			function ( $a, $b ) {
				if ( $a['saved_at'] === $b['saved_at'] ) {
					return 0;
				}

				return ( $a['saved_at'] < $b['saved_at'] ) ? -1 : 1;
			}
		);

		foreach ( $ordered as $candidate ) {
			if ( $row_bytes <= $total_cap ) {
				break;
			}

			$inner_key = $candidate['inner_key'];

			if ( ! isset( $row[ $inner_key ] ) ) {
				continue;
			}

			$removed[ $inner_key ] = $row[ $inner_key ];
			$entry_bytes           = isset( $sizes[ $inner_key ] ) ? $sizes[ $inner_key ] : strlen( maybe_serialize( $row[ $inner_key ] ) );
			$row_bytes            -= $entry_bytes + bb_draft_serialized_key_bytes( $inner_key );

			unset( $row[ $inner_key ] );
			++$disposed;
		}
	}

	// A salvage-only pass removed nothing but DID rewrite inner drafts, so it
	// still has to reach the write below - returning early here would throw
	// the shed row away and leave the oversized one stored (PROD-9621 Q6).
	if ( empty( $removed ) && 0 === $salvaged ) {
		return 0;
	}

	// One write for the whole heal, then release the stamps of everything
	// dropped that the surviving row no longer references.
	if ( empty( $row ) ) {
		bp_delete_user_meta( $user_id, 'bb_user_topic_reply_draft' );
	} else {
		// wp_slash(): $row was read from storage and is UNSLASHED, and
		// update_metadata() unslashes once more before storing. Writing it back
		// untouched strips a backslash layer from every string in the row -
		// including the \uXXXX escapes in the attachment lists of the very
		// inner drafts this heal exists to preserve (PROD-9621 R2).
		bp_update_user_meta( $user_id, 'bb_user_topic_reply_draft', wp_slash( $row ) );
	}

	bb_draft_flush_user_meta_sizes( $user_id );

	$surviving = array();

	foreach ( $row as $surviving_entry ) {
		$surviving = array_merge( $surviving, bb_draft_collect_attachment_ids( $surviving_entry ) );
	}

	foreach ( $removed as $removed_entry ) {
		foreach ( array_diff( bb_draft_collect_attachment_ids( $removed_entry ), $surviving ) as $attachment_id ) {
			if ( bb_draft_user_can_manage_attachment( $attachment_id, $user_id ) ) {
				delete_post_meta( $attachment_id, 'bb_media_draft' );
				delete_post_meta( $attachment_id, 'bb_activity_post_feature_image_draft' );
			}
		}
	}

	// Salvages count as work done: a caller summing this into a healed
	// total would otherwise report a row it did rewrite as untouched.
	return $disposed + $salvaged;
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
					} elseif ( bb_draft_salvage_oversized_draft( $row_user, $row['meta_key'] ) ) {
						// Shedding the poster frame was enough - the member keeps
						// their text. Tried BEFORE disposal because a one-draft row
						// has nothing to evict, so disposal is total: it destroyed
						// unpublished text to reclaim space the poster alone was
						// using (PROD-9621 Q6).
						++$healed;
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
			$key_sql = bb_draft_meta_key_sql();

			if ( empty( $key_sql['invertible'] ) ) {
				// Same reasoning as the scan: without an invertible key wrap the
				// per-user totals cannot be attributed, so stage 2 is skipped.
				$heavy_users = array();
			} else {
				$aggregate_values   = $key_sql['values'];
				$aggregate_values[] = bb_draft_user_total_max_size();

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- one-time healing aggregate; $key_sql['where'] carries only placeholders.
				$heavy_users = $wpdb->get_col(
					// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- as above; 4 placeholders in the fragment plus the 1 appended here = the 5 values passed.
					$wpdb->prepare(
						"SELECT user_id
						FROM {$wpdb->usermeta}
						WHERE {$key_sql['where']}
						GROUP BY user_id
						HAVING SUM(LENGTH(meta_value)) > %d",
						$aggregate_values
					)
				);
			}

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

				if ( ! empty( $expired['locked'] ) ) {
					WP_CLI::warning( 'Expiry pass skipped: another sweep holds the lock. Re-run once it finishes.' );
					break;
				}

				WP_CLI::log( 'Expiry pass: ' . $expired['deleted'] . ' expired drafts removed.' );
			} while ( empty( $expired['complete'] ) );

			WP_CLI::success( 'Draft cleanup complete.' );
		}
	);
}
