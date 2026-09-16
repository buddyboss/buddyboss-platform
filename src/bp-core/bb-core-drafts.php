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
	// Keyed on the filtered value of a REAL draft key, not a synthetic probe: a
	// test (or a plugin on a late hook) may add or remove the filter
	// mid-request, and the memo must follow it. A synthetic probe cannot follow
	// a filter SCOPED to the draft keys - that filter leaves the probe untouched
	// while wrapping every real key, so the probe's value (and therefore the
	// memo key) never changes and "no filter" and "draft-scoped filter" collapse
	// to the same cached identity, silently disabling the whole maintenance
	// layer (M1). Deriving from `draft_user` makes the wrap follow exactly the
	// filter the stored keys actually went through.
	static $memo = array();

	$canonical_key = 'draft_user';
	$filtered      = (string) bp_get_user_meta_key( $canonical_key );

	if ( isset( $memo[ $filtered ] ) ) {
		return $memo[ $filtered ];
	}

	$identity = array(
		'prefix'     => '',
		'suffix'     => '',
		'invertible' => true,
	);

	$wrap     = $identity;
	$position = strpos( $filtered, $canonical_key );

	if ( false === $position ) {
		// The filter is not a pure wrap of this key (a hash, say), so a stored
		// key cannot be mapped back. Declining is the one outcome safer than
		// guessing.
		$wrap['invertible'] = false;
	} else {
		$wrap['prefix'] = substr( $filtered, 0, $position );
		$wrap['suffix'] = substr( $filtered, $position + strlen( $canonical_key ) );
	}

	// Verify the derived wrap describes EVERY canonical draft key the same way.
	// A filter that rewrites only some of them would otherwise hand back a wrap
	// that silently mismatches the rows the maintenance layer is about to act
	// on (M1).
	if ( $wrap['invertible'] ) {
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
 * re-filtered onto a DIFFERENT, live row (N2).
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
	$stripped = preg_replace( '/data:(?:[a-z0-9.+-]+\/[a-z0-9.+-]+)?;base64,[A-Za-z0-9+\/=]*/i', '', $content );

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
	// instead of stopping early at a `>` inside an SVG.
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
			'/(=\s*")data:(?:[a-z0-9.+-]+\/[a-z0-9.+-]+)?[a-z0-9;=.+-]*,[^"]*/i',
			'/(=\s*\')data:(?:[a-z0-9.+-]+\/[a-z0-9.+-]+)?[a-z0-9;=.+-]*,[^\']*/i',
			'/(\s[a-z][a-z0-9:_-]*\s*=\s*)data:(?:[a-z0-9.+-]+\/[a-z0-9.+-]+)?[a-z0-9;=.+-]*,[^\s"\'>]*/i',
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
 * Most attachments of one type a draft payload may carry.
 *
 * The lists arrive as client JSON and every entry costs a per-ID ownership
 * lookup, so they have to be bounded before that loop runs. The bound was a
 * hard 50, which is BELOW what an administrator can configure: the media
 * "Upload Limit" field accepts up to 100 ({@see bb_media_sanitize_upload_limit()}).
 * On such a site the draft paths silently truncated the list they then STORED
 * and left the dropped attachments unstamped - which is exactly the predicate
 * `bp_media_delete_orphaned_attachments()` reaps. A member attaching 60 photos
 * kept 50 in the draft and lost 10 files six hours later (H4).
 *
 * The floor of 50 keeps the previous behaviour for every site at or below it.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return int Maximum entries accepted per attachment type.
 */
function bb_draft_max_attachments_per_type( $type = 'media' ) {
	// Each attachment type has its OWN independently-configurable upload limit
	// (Photos, Documents and Videos each accept up to 100). Reading only the
	// Photos limit for all three reintroduced the H4 truncation for the other
	// two whenever their limit was raised above the 50 floor while Photos was
	// left lower - a member attaching 60 videos to a draft on a Video-limit-100
	// site was silently cut to 50, and the dropped 10 lost to the orphan cron.
	switch ( $type ) {
		case 'document':
			$configured = function_exists( 'bp_media_allowed_upload_document_per_batch' ) ? (int) bp_media_allowed_upload_document_per_batch() : 0;
			break;
		case 'video':
			$configured = function_exists( 'bp_video_allowed_upload_video_per_batch' ) ? (int) bp_video_allowed_upload_video_per_batch() : 0;
			break;
		case 'media':
		default:
			$configured = function_exists( 'bp_media_allowed_upload_media_per_batch' ) ? (int) bp_media_allowed_upload_media_per_batch() : 0;
			break;
	}

	/**
	 * Filters how many attachments of one type a draft payload may carry.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int    $max  Maximum entries per attachment type.
	 * @param string $type Attachment type: 'media', 'document' or 'video'.
	 */
	return (int) apply_filters( 'bb_draft_max_attachments_per_type', max( 50, $configured ), $type );
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
 * cleanup reaches it, a deleted one is gone (BLOCKER-1).
 *
 * Ownership is still enforced per ID, and each list is bounded before any
 * per-ID lookup runs, so a crafted payload cannot stamp other members'
 * attachments or force thousands of uncached queries.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $lists   Attachment lists keyed by type ('media'/'document'/'video'),
 *                       each an array of entries carrying an `id`. An unkeyed list
 *                       falls back to the media cap.
 * @param int   $user_id Acting user ID.
 * @return int[] Attachment IDs stamped.
 */
function bb_draft_protect_payload_attachments( $lists, $user_id ) {
	$user_id = (int) $user_id;
	$stamped = array();

	if ( empty( $lists ) || ! is_array( $lists ) || $user_id < 1 ) {
		return $stamped;
	}

	foreach ( $lists as $type => $list ) {
		if ( empty( $list ) || ! is_array( $list ) ) {
			continue;
		}

		// Same bound the normalisation loops apply, enforced here too because
		// this pass runs before them. Truncating HERE only limits how many
		// attachments get stamped; the handlers refuse an over-bound list
		// outright rather than storing a subset of it (H4). The cap is PER TYPE,
		// and it must match the type-specific cap the handlers enforce or this
		// pass would stamp fewer than the handler stores (or vice versa) - the
		// BLOCKER-1 mismatch. Callers pass a media/document/video-keyed array.
		$max_per_type = bb_draft_max_attachments_per_type( is_string( $type ) ? $type : 'media' );

		if ( $max_per_type < count( $list ) ) {
			$list = array_slice( $list, 0, $max_per_type );
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

	// The referenced-set cache is NOT invalidated here: this runs BEFORE the
	// draft that references these attachments is written, so a sweep rebuilding
	// the set in the window between here and the write would re-cache without the
	// new reference and pin it for the TTL (F3). The reference only exists once
	// the draft is stored, so invalidation belongs AFTER the draft write.
	//
	// IMPORTANT: this function no longer provides that invalidation, so each
	// caller MUST drop `bb_draft_referenced_stamp_ids` after its own draft
	// write, keyed on EVERY attachment the draft keeps - never on a
	// client-controlled flag. A restored draft echoes bb_media_draft back
	// already set, so a flag-gated queue stamps nothing yet still references the
	// file; if the caller's invalidation is gated on that queue it leaves the
	// cache stale and the sweep reaps a referenced file (F7). Callers:
	// activity/ajax.php and forums/core/actions.php.
	//
	// On a refused save no draft is written, so there is correctly nothing to
	// invalidate - the attachment is a stamped orphan the sweep may reap once it
	// ages past retention.
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
			// same language the writers used (N2).
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
	// PHP normalizes a canonical-integer array key to an int when it is SET,
	// and serialize() then writes it as `i:N;` - not as the quoted string form.
	// Pricing every key as a string overstated an integer key by 4 bytes
	// (`s:1:"5";` vs `i:5;`), which made the derived row width drift from the
	// real one on legacy rows whose inner keys were []-appended integers. The
	// callers cast keys to string on the way in, so the canonical-int check is
	// done HERE rather than trusting the received type: '5' and 5 price
	// identically because PHP stores them identically, while '05' and '5.0'
	// stay strings in both places (GH2).
	if ( is_int( $key ) || (string) (int) $key === (string) $key ) {
		return strlen( 'i:' . (int) $key . ';' );
	}

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
 * 1920x1080. On a legacy row written before the client stopped sending it, it
 * is routinely the overwhelming majority of the stored bytes: measured at
 * 99.8% of a 150 KB row, which drops to 260 bytes once the frame is gone.
 *
 * Shedding it is NOT free, and the earlier claim that "the poster is
 * regenerated from the attachment on publish" is wrong for the video kind this
 * actually applies to (M10). `bp_video_add_generate_thumb_background_process()`
 * returns early both when `bb_video_check_is_ffmpeg_binary()` reports no binary
 * (the PHP library is bundled; the binary is not) AND when the video's privacy
 * is `forums`/`comment`/`message` — and forum videos are created with
 * `privacy => forums`. So for a FORUM video draft, shedding `js_preview` leaves
 * it with no poster, permanently, on every install: `js_preview` was its only
 * thumbnail source.
 *
 * The trade is still the right one — a missing poster beats destroying the
 * member's unpublished text — which is why the healing paths must try shedding
 * before they delete (Q6). But callers must not assume the thumbnail comes
 * back; it does not for forum videos.
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
 * (Q6).
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

	// The one-shot scan measures row size in a metadata-only batch query and
	// heals a whole 200-row window later, so the member can edit an
	// oversized-at-scan row back UNDER the cap in that gap (open the composer,
	// trim it, autosave). Re-measure the fresh row first: an already-valid draft
	// must never fall through to the caller's unconditional bb_draft_dispose().
	// Returning true (handled - nothing to do) is what keeps that from happening.
	// This is the guard bb_draft_heal_forum_row() already applies per inner
	// entry; the flat-key path lacked it (stale-size TOCTOU).
	if ( strlen( maybe_serialize( $stored ) ) <= bb_draft_max_size() ) {
		return true;
	}

	$shed = bb_draft_shed_preview_frames( $stored );

	// Nothing was reclaimable, so this row is oversized on its own merits.
	if ( maybe_serialize( $shed ) === maybe_serialize( $stored ) ) {
		return false;
	}

	if ( strlen( maybe_serialize( $shed ) ) > bb_draft_max_size() ) {
		return false;
	}

	// wp_slash(): $shed is derived from a row read with bp_get_user_meta(), so
	// it is UNSLASHED, and update_metadata() unslashes once more on write. This
	// function exists to PRESERVE the member's text when the row is oversized
	// (Q6) - without the re-slash it mangled exactly that text,
	// turning `path C:\temp\notes` into `path C:tempnotes` (S2).
	bp_update_user_meta( $user_id, $meta_key, wp_slash( $shed ) );
	bb_draft_flush_user_meta_sizes( $user_id );

	return true;
}

/**
 * Collect every attachment ID the user's OTHER stored drafts still reference.
 *
 * The immediate dispose path (a member's own discard, a per-user budget
 * eviction, the expiry cron) released an entry's attachment stamps consulting
 * only same-ROW siblings, never the user's other draft rows. So an attachment
 * legitimately referenced from two rows - two activity drafts, or an activity
 * and a group draft - lost its `bb_media_draft` protection the moment one row
 * was disposed, and the general orphan-media cron could then hard-delete a file
 * a live draft still pointed at (L7). This builds the retain set the dispose
 * passes to {@see bb_draft_unstamp_attachments()} so a still-referenced
 * attachment keeps its stamp.
 *
 * Read from live storage on each call, so inside a multi-eviction loop it
 * reflects the rows already removed this pass - an attachment shared by two
 * evicted rows is retained while the first goes and released when the last does.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int    $user_id       User ID.
 * @param string $exclude_key   Meta key of the entry being disposed.
 * @param string $exclude_inner Inner key being disposed, or '' for a whole row.
 * @return int[] Attachment IDs still referenced by the user's other drafts.
 */
function bb_draft_collect_other_referenced_ids( $user_id, $exclude_key, $exclude_inner = '' ) {
	$user_id = (int) $user_id;
	$ids     = array();

	if ( $user_id <= 0 ) {
		return $ids;
	}

	$sizes = bb_draft_get_user_meta_sizes( $user_id );

	if ( empty( $sizes['drafts'] ) || ! is_array( $sizes['drafts'] ) ) {
		return $ids;
	}

	foreach ( array_keys( $sizes['drafts'] ) as $meta_key ) {
		$stored = bp_get_user_meta( $user_id, $meta_key, true );

		if ( empty( $stored ) || ! is_array( $stored ) ) {
			continue;
		}

		if ( 'bb_user_topic_reply_draft' === $meta_key ) {
			foreach ( $stored as $inner_key => $inner_draft ) {
				// Skip only the exact entry being disposed; every other inner
				// draft - in this row or any other - is a live reference.
				if ( $meta_key === $exclude_key && (string) $inner_key === (string) $exclude_inner ) {
					continue;
				}

				$ids = array_merge( $ids, bb_draft_collect_attachment_ids( $inner_draft ) );
			}
		} else {
			// A whole activity row. Skipped only when IT is the entry being
			// disposed as a whole (empty inner key).
			if ( $meta_key === $exclude_key && '' === (string) $exclude_inner ) {
				continue;
			}

			$ids = array_merge( $ids, bb_draft_collect_attachment_ids( $stored ) );
		}
	}

	return array_values( array_unique( array_map( 'intval', $ids ) ) );
}

/**
 * Collect attachment IDs referenced by the user's draft rows OTHER than one row.
 *
 * The whole-row companion to {@see bb_draft_collect_other_referenced_ids()}, for
 * the release sites that already hold their OWN row's surviving set in memory -
 * the forum row-trim eviction, the expiry batch and the row heal - and only need
 * the cross-ROW additions. Without merging these, releasing an entry from the
 * forum row stripped the stamp of an attachment a DIFFERENT usermeta row still
 * held: the L7 gap, reopened through every release path bb_draft_dispose() did
 * not itself cover.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int    $user_id          User ID.
 * @param string $exclude_meta_key Meta key whose row the caller handles itself.
 * @return int[] Attachment IDs referenced by the user's other draft rows.
 */
function bb_draft_collect_other_row_referenced_ids( $user_id, $exclude_meta_key ) {
	$user_id = (int) $user_id;
	$ids     = array();

	if ( $user_id <= 0 ) {
		return $ids;
	}

	$sizes = bb_draft_get_user_meta_sizes( $user_id );

	if ( empty( $sizes['drafts'] ) || ! is_array( $sizes['drafts'] ) ) {
		return $ids;
	}

	foreach ( array_keys( $sizes['drafts'] ) as $meta_key ) {
		if ( $meta_key === $exclude_meta_key ) {
			continue;
		}

		$stored = bp_get_user_meta( $user_id, $meta_key, true );

		if ( empty( $stored ) || ! is_array( $stored ) ) {
			continue;
		}

		if ( 'bb_user_topic_reply_draft' === $meta_key ) {
			foreach ( $stored as $inner_draft ) {
				$ids = array_merge( $ids, bb_draft_collect_attachment_ids( $inner_draft ) );
			}
		} else {
			$ids = array_merge( $ids, bb_draft_collect_attachment_ids( $stored ) );
		}
	}

	return array_values( array_unique( array_map( 'intval', $ids ) ) );
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
 *
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $draft          Draft array.
 * @param int   $user_id        Owning user ID.
 * @param array $retain_entries Optional. Draft entries that survive this
 *                              removal and whose attachments must keep
 *                              their stamps.
 * @param array $retain_ids     Optional. Attachment IDs a DIFFERENT stored row
 *                              still references (cross-row retain), which must
 *                              also keep their stamps.
 * @return void
 */
function bb_draft_unstamp_attachments( $draft, $user_id, $retain_entries = array(), $retain_ids = array() ) {
	$attachment_ids = bb_draft_collect_attachment_ids( $draft );
	// Pre-resolved IDs a DIFFERENT draft row still references (cross-row retain).
	// The same-row $retain_entries only ever covered siblings in one aggregated
	// row, so an attachment held by another usermeta row lost its stamp when this
	// one was disposed (L7); callers now pass those ids here.
	$retained = array_map( 'intval', (array) $retain_ids );

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
 * this same whole-row exclusion.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $previous_entry Draft entry being replaced.
 * @param array $current_entry  Draft entry replacing it.
 * @param int   $user_id        Owning user ID.
 * @param array $retain_entries Optional. Draft entries that survive the write
 *                              and whose attachments must keep their stamps.
 * @param array $retain_ids     Optional. Attachment IDs a DIFFERENT stored row
 *                              still references (cross-row retain), which must
 *                              also keep their stamps (L7).
 * @return int[] Attachment IDs whose stamps were released.
 */
function bb_draft_release_replaced_attachments( $previous_entry, $current_entry, $user_id, $retain_entries = array(), $retain_ids = array() ) {
	$retained = array_merge( bb_draft_collect_attachment_ids( $current_entry ), array_map( 'intval', (array) $retain_ids ) );

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
 * (M5).
 *
 * `$defer_attachment_release` exists for the root-only expiry sweep on
 * multisite (H2): drafts live in network-global usermeta, so that sweep sees
 * every blog's drafts, but attachments live in per-site `wp_posts`/`wp_postmeta`
 * and the stamp release resolves attachment IDs through `get_post()` in the
 * CURRENT blog. Releasing a subsite draft's attachment ID from root context
 * would strip protection from - and let the orphan cron delete - a same-numbered
 * attachment on the ROOT blog that a live draft still references. When true, the
 * unstamp is skipped and the per-site orphan-stamp sweep ({@see
 * bb_drafts_release_orphaned_draft_stamps}), which runs in each blog's own
 * context, releases the stamp correctly once the draft is gone.
 *
 * @param int    $user_id                  Owning user ID.
 * @param string $meta_key                 Draft usermeta key.
 * @param string $inner_key                Optional. Inner draft key inside the
 *                                         forum aggregate row; empty means
 *                                         "remove the whole row".
 * @param bool   $defer_attachment_release Optional. Skip the attachment unstamp
 *                                         and leave it to the per-site sweep.
 *                                         Default false.
 * @return bool Whether a stored draft was removed.
 */
function bb_draft_dispose( $user_id, $meta_key, $inner_key = '', $defer_attachment_release = false ) {
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
		// stored inner draft was still using.
		$retain_entries = $stored;
		unset( $retain_entries[ $inner_key ] );

		if ( ! $defer_attachment_release ) {
			// Same-row siblings ($retain_entries) PLUS anything the user's other
			// draft rows still reference (L7).
			bb_draft_unstamp_attachments(
				$stored[ $inner_key ],
				$user_id,
				$retain_entries,
				bb_draft_collect_other_referenced_ids( $user_id, $meta_key, $inner_key )
			);
		}
		unset( $stored[ $inner_key ] );

		if ( empty( $stored ) ) {
			bp_delete_user_meta( $user_id, $meta_key );
		} else {
			// wp_slash(): $stored came from bp_get_user_meta() and is therefore
			// UNSLASHED, while update_metadata() unslashes the value once more
			// before storing it. Writing it back untouched stripped a backslash
			// layer from the SURVIVING siblings - `résumé.pdf` became
			// `ru00e9sumu00e9.pdf`, and a filename holding a double quote broke
			// that draft's attachment list JSON outright.
			//
			// This is the shared removal path: the member's own discard, the
			// per-user budget eviction and the nightly expiry cron all route
			// through here, so it corrupted drafts the member never touched
			// (S1).
			bp_update_user_meta( $user_id, $meta_key, wp_slash( $stored ) );
		}

		bb_draft_flush_user_meta_sizes( $user_id );

		return true;
	}

	if ( ! $defer_attachment_release ) {
		// The whole row goes, so nothing in it survives to retain; the retain set
		// is whatever the user's OTHER draft rows still reference (L7).
		$retain_ids = bb_draft_collect_other_referenced_ids( $user_id, $meta_key, '' );

		if ( 'bb_user_topic_reply_draft' === $meta_key ) {
			foreach ( $stored as $inner_draft ) {
				bb_draft_unstamp_attachments( $inner_draft, $user_id, array(), $retain_ids );
			}
		} else {
			bb_draft_unstamp_attachments( $stored, $user_id, array(), $retain_ids );
		}
	}

	bp_delete_user_meta( $user_id, $meta_key );
	bb_draft_flush_user_meta_sizes( $user_id );

	return true;
}

/**
 * Remove several expired inner drafts from the aggregate forum row in ONE write.
 *
 * The expiry sweep used to route each expired inner draft through
 * {@see bb_draft_dispose()}, and each of those does a full-row read plus a
 * full-row write - the O(n.B) shape {@see bb_draft_heal_forum_row()} was
 * rewritten to avoid, now reintroduced by the RECURRING daily cron against the
 * widest rows on the site by definition (M4). This does what the healer does:
 * decide in memory, write once, then release the attachment stamps of the
 * removed entries that no surviving inner draft still references.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int      $user_id                  Owning user ID.
 * @param string[] $inner_keys               Inner draft keys to remove.
 * @param bool     $defer_attachment_release Optional. Skip attachment release
 *                                           and leave it to the per-site sweep
 *                                           (H2). Default false.
 * @return int Number of inner drafts removed.
 */
function bb_draft_dispose_forum_inner_keys( $user_id, $inner_keys, $defer_attachment_release = false ) {
	$user_id = (int) $user_id;

	if ( $user_id <= 0 || empty( $inner_keys ) || ! is_array( $inner_keys ) ) {
		return 0;
	}

	$row = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

	if ( ! is_array( $row ) || empty( $row ) ) {
		return 0;
	}

	$removed = array();

	foreach ( $inner_keys as $inner_key ) {
		$inner_key = (string) $inner_key;

		// An empty inner key is bb_draft_dispose()'s whole-row sentinel, never a
		// real entry to remove (M5).
		if ( '' === $inner_key || ! isset( $row[ $inner_key ] ) ) {
			continue;
		}

		$removed[ $inner_key ] = $row[ $inner_key ];
		unset( $row[ $inner_key ] );
	}

	if ( empty( $removed ) ) {
		return 0;
	}

	// One write for the whole batch.
	if ( empty( $row ) ) {
		bp_delete_user_meta( $user_id, 'bb_user_topic_reply_draft' );
	} else {
		// wp_slash(): the row came back unslashed and update_metadata() unslashes
		// once more on write, so re-slash to store the surviving drafts
		// byte-for-byte (R2, S5).
		bp_update_user_meta( $user_id, 'bb_user_topic_reply_draft', wp_slash( $row ) );
	}

	bb_draft_flush_user_meta_sizes( $user_id );

	// Release the stamps of removed attachments no surviving inner draft holds.
	// On multisite the root-only expiry sweep passes $defer_attachment_release,
	// because these IDs resolve against the current blog's tables and would
	// otherwise release a same-numbered attachment on the wrong blog (H2); the
	// per-site orphan-stamp sweep releases them in the correct context instead.
	if ( ! $defer_attachment_release ) {
		// Same-row survivors PLUS every attachment the user's OTHER draft rows
		// still reference. This runs from the expiry cron, so without the
		// cross-row set an aged-out forum inner draft would strip the stamp of a
		// file a live activity/group draft still holds - the L7 gap, reached
		// through an unattended path (L8 fan-out).
		$surviving = bb_draft_collect_other_row_referenced_ids( $user_id, 'bb_user_topic_reply_draft' );

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
	}

	return count( $removed );
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
 * draft is one draft per meta row (M4).
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
					// which is the sibling of this loop.
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

	// Decide BEFORE destroying anything: if evicting every candidate still
	// cannot bring the total under the cap, refuse now, without deleting a
	// single draft (H1). bb_draft_dispose() writes to storage immediately, so a
	// post-eviction refusal - the shape this replaced - would have permanently
	// deleted the member's older drafts AND rejected the new save, telling them
	// to "discard some drafts" that no longer exist. Rows that are corrupt /
	// non-array are skipped above and never enter $candidates, so they are
	// correctly excluded from what is reclaimable here.
	$reclaimable = 0;
	foreach ( $candidates as $candidate ) {
		$reclaimable += $candidate['bytes'];
	}

	// The feasibility refusal is a SAVE concept only: refuse without deleting
	// when the new save can never fit (H1). The healing context has no new save
	// to protect - partial reclamation is its whole purpose, so it must fall
	// through to the eviction loop and reclaim whatever it can (F4). Its caller
	// ignores 'allowed' and counts 'evicted'.
	if ( 'heal' !== $context && ( $draft_total - $reclaimable ) > $total_cap ) {
		$result['allowed'] = false;

		return $result;
	}

	// The eviction below deletes rows and, by default, releases their attachment
	// stamps in the CURRENT blog. That is correct for a live in-blog save, but
	// the healing context runs from the root-only one-shot and evicts heavy
	// users' rows network-wide, so on multisite it would strip a same-numbered
	// attachment on the wrong blog - H2 verbatim (F1). Defer the release there
	// so the per-site orphan-stamp sweep reaps in the correct blog instead.
	$defer_attachment_release = ( 'heal' === $context && is_multisite() );

	foreach ( $candidates as $candidate ) {
		if ( $draft_total <= $total_cap ) {
			break;
		}

		if ( ! bb_draft_dispose( $user_id, $candidate['meta_key'], $candidate['inner_key'], $defer_attachment_release ) ) {
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

	// The pre-loop feasibility check above guarantees the candidate set CAN
	// reach the cap, so the only way to still be over it here is a
	// bb_draft_dispose() that failed mid-loop (a second tab racing this one
	// against a stale size memo) - a genuine, narrow race. Refuse, so the caller
	// cannot store past the cap on a partial eviction. $result['evicted'] lists
	// what was actually deleted; the callers surface it on refusal too, so the
	// client can reconcile its localStorage/UI instead of showing drafts that no
	// longer exist.
	if ( $draft_total > $total_cap ) {
		$result['allowed'] = false;
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
 * publish path applies, rather than one loose site-wide check.
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

			// The reply must belong to the TOPIC named in the same key. Without
			// this the two halves are validated independently, so a member could
			// pair a public topic they can view with a reply id from an unrelated
			// (possibly private) forum: forum_id below is derived from the topic,
			// so the reply's real forum is never gated, and the "reply exists / is
			// a reply" vs "does not" outcomes reopen the L1 existence/post-type
			// oracle for the reply shape. Tying it to the topic collapses both
			// outcomes into the same indistinguishable rejection (L9).
			if ( (int) bbp_get_reply_topic_id( (int) $matches[2] ) !== (int) $matches[1] ) {
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
 * Prime the post and meta caches for a batch of forum draft keys.
 *
 * The bb_draft_topic_reply_key_context() resolver reads each key through
 * get_post(), and the reply shape resolves two more values -
 * bbp_get_reply_topic_id() and bbp_get_topic_forum_id() - out of post meta.
 * Called once per key in a loop
 * that is bounded only by how many drafts a member has stored, that is a textbook
 * N+1: the unload beacon replays every key the tab holds, so a member with many
 * small drafts turns one autosave into one uncached query per draft (H-5).
 *
 * Priming collapses those into a single `IN (...)` post query plus one meta
 * query. It changes no behaviour - every guard still runs per key, and a key
 * naming a post that does not exist still resolves to false, just from cache.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string[] $data_keys Draft keys about to be resolved.
 * @return void
 */
function bb_draft_prime_topic_reply_key_posts( $data_keys ) {
	if ( ! is_array( $data_keys ) || empty( $data_keys ) ) {
		return;
	}

	$post_ids = array();

	foreach ( $data_keys as $data_key ) {
		$data_key = (string) $data_key;

		// The bare 'draft_topic' / 'draft_reply' shapes name no post at all.
		if ( preg_match( '/^draft_discussion_(\d+)$/', $data_key, $matches ) ) {
			$post_ids[] = (int) $matches[1];

			continue;
		}

		if ( preg_match( '/^draft_reply_(\d+)(?:_(\d+))?$/', $data_key, $matches ) ) {
			$post_ids[] = (int) $matches[1];

			if ( isset( $matches[2] ) ) {
				$post_ids[] = (int) $matches[2];
			}
		}
	}

	$post_ids = array_filter( array_unique( $post_ids ) );

	if ( empty( $post_ids ) ) {
		return;
	}

	// Meta is primed too, not just the posts: the reply shape reads its topic and
	// that topic's forum out of post meta, so leaving meta cold would only move
	// the N+1 from the post table to the meta table.
	_prime_post_caches( $post_ids, false, true );
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
 * (Q12). The client no longer does that — but it also no longer
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
			// Declined, NOT finished - but PERMANENT, unlike 'failed'. A filter is
			// either invertible or it is not, so a caller that retries on this
			// would retry for ever. Callers must stop without acting and without
			// scheduling a retry (M2).
			'failed'   => false,
			'declined' => true,
		);
	}

	$query_values   = $key_sql['values'];
	$query_values[] = (int) $last_umeta_id;
	$query_values[] = (int) $limit;

	// Metadata first, ALWAYS - never `meta_value` in the windowing query. The
	// widths come from LENGTH() so the window can be byte-bounded before any
	// payload is pulled into PHP (B2).
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

	// A FAILED query and an exhausted table both arrive here as an empty result,
	// and telling them apart is the whole point: get_results() returns null on
	// failure while a genuinely empty window returns array(). wpdb::query() calls
	// flush(), which clears last_error, so it describes only the query above.
	//
	// Conflating the two is a data-loss bug, not a reporting one: a failed scan
	// reads as "no draft references exist anywhere", and the orphan-stamp sweep
	// then releases bb_media_draft from every stamped file and caches that answer
	// network-wide for the TTL (BLOCKER-2).
	if ( null === $rows || '' !== $wpdb->last_error ) {
		return array(
			'rows'     => array(),
			'last_id'  => (int) $last_umeta_id,
			'has_more' => false,
			'failed'   => true,
			'declined' => false,
		);
	}

	if ( empty( $rows ) ) {
		return array(
			'rows'     => array(),
			'last_id'  => (int) $last_umeta_id,
			'has_more' => false,
			'failed'   => false,
			'declined' => false,
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

		// Same trap one query later: a failure here leaves $by_id empty, every row
		// reads as an empty meta_value, and the callers' shape checks skip them -
		// so a failed fetch would look like "these drafts reference nothing".
		if ( null === $values || '' !== $wpdb->last_error ) {
			return array(
				'rows'     => array(),
				'last_id'  => (int) $last_umeta_id,
				'has_more' => false,
				'failed'   => true,
				'declined' => false,
			);
		}

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
		// value for diagnostics (N2).
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
		'failed'   => false,
		'declined' => false,
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
 * The scan cursor is persisted in the `bb_draft_cleanup_cursor` site option
 * between budget-interrupted slices: without it every continuation slice
 * would restart from row zero, and on a site whose draft rows cannot be
 * scanned inside one budget the rows past the time horizon would never be
 * reached at all. Because that cursor is shared by the recurring event and
 * the continuation event, the sweep holds the `bb_draft_cleanup_lock` site
 * transient for its duration; a run that finds the lock held returns
 * `locked` and leaves the work to the holder.
 *
 * Cursor and lock are network-scoped (`*_site_*`) to match the epoch option
 * and the usermeta rows the sweep walks - all network-global. On multisite a
 * `wp bb drafts cleanup --url=subsite` run would otherwise take a per-site
 * cursor/lock and race the root cron's over the one shared row set; site
 * scope collapses them to a single cursor and a single lock. On single site
 * `*_site_*` transparently falls back to the per-site store, so nothing
 * changes there.
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
	//
	// Also back off while the one-shot migration holds bb_draft_oneshot_lock:
	// both sweeps read-modify-write the SAME aggregated usermeta rows, and
	// running concurrently (each holding only its own lock) lets one write a
	// stale copy over the other's change - the daily cron's just-expired key
	// silently resurrected by the one-shot's size heal, or vice versa. They must
	// serialize against each other, not only against themselves.
	//
	// The two reads below are necessary but NOT sufficient, and on their own they
	// were the very check-then-set this file already diagnosed and fixed for the
	// stamp sweep: each party reads the OTHER's key and then sets its OWN, so
	// there is no instant at which a winner is decided and both can read "free"
	// and proceed. Mutual exclusion between two parties needs ONE shared key,
	// acquired atomically - that is what bb_draft_maintenance_lock is. The two
	// per-sweep keys are kept because they are what the other party reads, and
	// because the reads short-circuit before the gate is taken, so a caller
	// blocked by them never acquires (and never has to release) it.
	if ( get_site_transient( 'bb_draft_cleanup_lock' ) || get_site_transient( 'bb_draft_oneshot_lock' ) || ! bb_draft_acquire_lock( 'bb_draft_maintenance_lock', 5 * MINUTE_IN_SECONDS, true ) ) {
		// Re-arm the continuation on a lock collision when a drain is already
		// in progress (a cursor is persisted). WP core deletes a single-event
		// cron entry BEFORE invoking its callback, so a `bb_draft_cleanup`
		// continuation tick that bails here would otherwise leave nothing to
		// resume the half-drained cursor until the next daily recurring fire -
		// up to 24h later. The one-shot migration (now a second, more
		// contention-prone lock) can hold its lock across many cron cycles, so
		// this collision is reachable; re-arming keeps the drain moving in ~60s.
		if ( get_site_option( 'bb_draft_cleanup_cursor' ) && ! wp_next_scheduled( 'bb_draft_cleanup' ) ) {
			wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'bb_draft_cleanup' );
		}

		return array(
			'deleted'  => 0,
			'complete' => false,
			'locked'   => true,
		);
	}

	set_site_transient( 'bb_draft_cleanup_lock', 1, 5 * MINUTE_IN_SECONDS );

	$cursor = (int) get_site_option( 'bb_draft_cleanup_cursor', 0 );

	// Expiry switched off - delete nothing. Guarded here rather than relying on
	// the cutoff arithmetic, where a zero window would expire every draft.
	if ( 1 > $retention_seconds ) {
		delete_site_option( 'bb_draft_cleanup_cursor' );
		delete_site_transient( 'bb_draft_cleanup_lock' );
		bb_draft_release_lock( 'bb_draft_maintenance_lock', true );

		return array(
			'deleted'  => 0,
			'complete' => true,
		);
	}

	$cutoff = time() - $retention_seconds;

	// Record the epoch lazily when the upgrade routine never did (fresh
	// installs, removed option) so timestamp-less legacy rows still age out
	// eventually instead of never.
	$epoch = (int) get_site_option( 'bb_draft_cleanup_epoch' );
	if ( ! $epoch ) {
		$epoch = time();
		add_site_option( 'bb_draft_cleanup_epoch', $epoch );
	}

	do {
		$batch = bb_draft_get_rows_batch( $cursor, 200, true );

		// A failed batch is not an empty one: continuing would move the cursor
		// past rows this pass never read, so they would never be examined again.
		// $complete = false persists the cursor and schedules a retry, which is
		// right for a TRANSIENT database error (BLOCKER-2).
		if ( ! empty( $batch['failed'] ) ) {
			$complete = false;

			break;
		}

		// A DECLINED batch is permanent - a bp_get_user_meta_key filter this code
		// cannot invert. Marking it incomplete would reschedule this pass every
		// minute, for ever, to do nothing. Stop instead, exactly as this function
		// behaved before the failure signal existed. That it is silent is tracked
		// separately as M2; making it noisy must not make it a spin.
		if ( ! empty( $batch['declined'] ) ) {
			break;
		}

		foreach ( $batch['rows'] as $row ) {
			$user_id = (int) $row['user_id'];
			$value   = bb_draft_safe_unserialize( $row['meta_value'] );

			if ( 'bb_user_topic_reply_draft' === $row['meta_key'] ) {
				if ( ! is_array( $value ) || empty( $value ) ) {
					// A legacy-empty array() row (old publish paths) holds no member
					// content and is collected immediately; a corrupt row ages from
					// the epoch like any unstamped legacy row.
					if ( ( is_array( $value ) || $epoch < $cutoff ) && bb_draft_dispose( $user_id, $row['meta_key'], '', is_multisite() ) ) {
						++$deleted;
					}
				} else {
					// Collect every expired inner key, then remove them in ONE
					// write instead of a full-row read+write per key - the O(n.B)
					// shape the healer avoids, on the recurring cron (M4).
					$expired_inner_keys = array();

					foreach ( $value as $inner_key => $inner_draft ) {
						// An empty inner key is the whole-row sentinel for
						// bb_draft_dispose(), so a legacy row carrying one must
						// not be routed through it - that would delete the
						// member's other drafts too (M5).
						if ( '' === (string) $inner_key ) {
							continue;
						}

						$saved_at = isset( $inner_draft['_draft_saved_at'] ) ? (int) $inner_draft['_draft_saved_at'] : $epoch;

						if ( $saved_at < $cutoff ) {
							$expired_inner_keys[] = (string) $inner_key;
						}
					}

					$deleted += bb_draft_dispose_forum_inner_keys( $user_id, $expired_inner_keys, is_multisite() );
				}
			} else {
				$saved_at = ( is_array( $value ) && isset( $value['_draft_saved_at'] ) ) ? (int) $value['_draft_saved_at'] : $epoch;
				if ( $saved_at < $cutoff && bb_draft_dispose( $user_id, $row['meta_key'], '', is_multisite() ) ) {
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
		// next run would redo the same window indefinitely (B2).
		update_site_option( 'bb_draft_cleanup_cursor', $cursor );

		// Refresh the lock each window. It is set once for 5 minutes at the top,
		// but `wp bb drafts cleanup` runs with an UNLIMITED budget and the
		// docblock promises it drains to completion - on a site with hundreds of
		// thousands of draft rows that exceeds 5 minutes, the lock would expire
		// mid-run, the daily cron would acquire it, and the two runs would then
		// share the single cursor - the clobbering the lock exists to prevent
		// (M6). Refreshing per window keeps it held for the life of the drain.
		// The shared maintenance gate is refreshed with it: it has the same TTL
		// and the same unbudgeted-drain exposure, and a gate that expires
		// mid-drain lets the one-shot in against this sweep's own rows.
		set_site_transient( 'bb_draft_cleanup_lock', 1, 5 * MINUTE_IN_SECONDS );
		set_site_transient( 'bb_draft_maintenance_lock', 1, 5 * MINUTE_IN_SECONDS );

		// Window-level budget check: a window whose rows are ALL filtered-out
		// third-party draft_* keys never reaches the per-row check above, so
		// consecutive such windows would otherwise run past the budget.
		if ( 0 < $time_budget && $batch['has_more'] && ( time() - $started_at ) >= $time_budget ) {
			$complete = false;
			break;
		}
	} while ( $batch['has_more'] );

	if ( $complete ) {
		delete_site_option( 'bb_draft_cleanup_cursor' );
	} else {
		update_site_option( 'bb_draft_cleanup_cursor', $cursor );

		if ( ! wp_next_scheduled( 'bb_draft_cleanup' ) ) {
			wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'bb_draft_cleanup' );
		}
	}

	// Released only after the cursor is settled, so a run starting the instant
	// this one returns cannot read a half-updated cursor.
	delete_site_transient( 'bb_draft_cleanup_lock' );
	bb_draft_release_lock( 'bb_draft_maintenance_lock', true );

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
 * multi-MB UPDATEs inside an upgrade request (H2).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int  $user_id                  Owning user ID.
 * @param bool $defer_attachment_release Optional. Skip attachment release and
 *                                        leave it to the per-site sweep (H2).
 *                                        Default false.
 * @return int Number of inner drafts acted on - disposed plus salvaged.
 */
function bb_draft_heal_forum_row( $user_id, $defer_attachment_release = false ) {
	$user_id  = (int) $user_id;
	$disposed = 0;
	$row      = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

	if ( empty( $row ) ) {
		return 0;
	}

	if ( ! is_array( $row ) ) {
		return bb_draft_dispose( $user_id, 'bb_user_topic_reply_draft', '', $defer_attachment_release ) ? 1 : 0;
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
			// instead of being dropped (Q6).
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
	// the shed row away and leave the oversized one stored (Q6).
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
		// inner drafts this heal exists to preserve (R2).
		bp_update_user_meta( $user_id, 'bb_user_topic_reply_draft', wp_slash( $row ) );
	}

	bb_draft_flush_user_meta_sizes( $user_id );

	// Same-row survivors PLUS every attachment the user's OTHER draft rows still
	// reference, or healing/trimming this forum row would strip the stamp of a
	// file a different row still holds (L7 fan-out).
	$surviving = bb_draft_collect_other_row_referenced_ids( $user_id, 'bb_user_topic_reply_draft' );

	foreach ( $row as $surviving_entry ) {
		$surviving = array_merge( $surviving, bb_draft_collect_attachment_ids( $surviving_entry ) );
	}

	// Skipped on multisite when healing from the root-only one-shot: these
	// IDs resolve against the current blog and could release a same-numbered
	// attachment on the wrong blog (H2). The per-site orphan-stamp sweep reaps.
	if ( ! $defer_attachment_release ) {
		foreach ( $removed as $removed_entry ) {
			foreach ( array_diff( bb_draft_collect_attachment_ids( $removed_entry ), $surviving ) as $attachment_id ) {
				if ( bb_draft_user_can_manage_attachment( $attachment_id, $user_id ) ) {
					delete_post_meta( $attachment_id, 'bb_media_draft' );
					delete_post_meta( $attachment_id, 'bb_activity_post_feature_image_draft' );
				}
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

	// Defensive re-arm. WordPress core deletes a single-event cron entry from
	// storage BEFORE it invokes the callback, so a fatal (OOM inside a
	// multi-megabyte heal, a killed worker) anywhere below would otherwise
	// leave nothing to resume the pass - only the very first, upgrade-triggered
	// call is pre-scheduled defensively. Schedule the continuation up front and
	// cancel it at the end if this slice finishes. Time-limited (cron) slices
	// only; the WP-CLI drain ($time_budget 0) loops to completion itself.
	if ( 0 < $time_budget && ! get_site_option( 'bb_draft_oneshot_done' ) ) {
		bb_draft_oneshot_schedule( 2 * MINUTE_IN_SECONDS );
	}

	// Serialize overlapping triggers. Two concurrent admin requests in the
	// upgrade window, or a double-fired cron, would otherwise race on
	// bb_draft_oneshot_state with last-writer-wins and silently discard one
	// run's progress - the same hazard bb_drafts_delete_expired() guards with
	// bb_draft_cleanup_lock. A lock lost to an unreliable object cache only
	// costs duplicate work (every operation here is idempotent), so failing
	// open is the safe direction. The WP-CLI drain releases the lock between
	// its own calls, so it never blocks itself.
	//
	// Also back off while the daily expiry sweep holds bb_draft_cleanup_lock:
	// both read-modify-write the same aggregated usermeta rows, so a concurrent
	// run (each holding only its own lock) could resurrect a key the other just
	// removed. The two must serialize against each other. This pairs with the
	// same cross-check in bb_drafts_delete_expired().
	// See bb_drafts_delete_expired(): the two reads are what the other party
	// observes, and the shared gate is what actually decides a winner.
	if ( get_site_transient( 'bb_draft_oneshot_lock' ) || get_site_transient( 'bb_draft_cleanup_lock' ) || ! bb_draft_acquire_lock( 'bb_draft_maintenance_lock', 5 * MINUTE_IN_SECONDS, true ) ) {
		return array(
			'healed'   => 0,
			'complete' => false,
			'locked'   => true,
		);
	}

	set_site_transient( 'bb_draft_oneshot_lock', 1, 5 * MINUTE_IN_SECONDS );

	$state = get_site_option( 'bb_draft_oneshot_state' );
	$state = wp_parse_args(
		is_array( $state ) ? $state : array(),
		array(
			'cursor'      => 0,
			'heavy_users' => null,
		)
	);

	// Stage 1 - per-row healing scan. Skipped once a previous slice finished it
	// (scan_done set) or the whole pass reached stage 2 (heavy_users an array).
	if ( ! is_array( $state['heavy_users'] ) && empty( $state['scan_done'] ) ) {
		$cursor = (int) $state['cursor'];

		do {
			$batch = bb_draft_get_rows_batch( $cursor, 200, false );

			// Same rule as the expiry pass: stop rather than advance the cursor
			// over rows that were never read. $complete = false persists the
			// cursor and leaves scan_done UNSET, so the next slice resumes here
			// instead of declaring the scan finished (BLOCKER-2).
			if ( ! empty( $batch['failed'] ) ) {
				$complete = false;

				break;
			}

			// Permanent decline: stop without marking the pass incomplete, so the
			// one-shot does not re-defer itself for ever (see the expiry pass).
			if ( ! empty( $batch['declined'] ) ) {
				break;
			}

			foreach ( $batch['rows'] as $row ) {
				$row_user  = (int) $row['user_id'];
				$row_bytes = (int) $row['bytes'];

				if ( $row_bytes > $max_size ) {
					if ( 'bb_user_topic_reply_draft' === $row['meta_key'] ) {
						$healed += bb_draft_heal_forum_row( $row_user, is_multisite() );
					} elseif ( bb_draft_salvage_oversized_draft( $row_user, $row['meta_key'] ) ) {
						// Shedding the poster frame was enough - the member keeps
						// their text. Tried BEFORE disposal because a one-draft row
						// has nothing to evict, so disposal is total: it destroyed
						// unpublished text to reclaim space the poster alone was
						// using (Q6).
						++$healed;
					} elseif ( bb_draft_dispose( $row_user, $row['meta_key'], '', is_multisite() ) ) {
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

			// Persisted after EVERY window, not only on a clean budget break -
			// the same reasoning bb_drafts_delete_expired() already carries, and
			// this pass is the MORE exposed of the two: it is the one that
			// reads, heals, salvages and disposes the multi-megabyte rows, and
			// its first slice runs synchronously on an upgrade admin request.
			//
			// Without this an interruption that never returns below (a fatal, a
			// killed worker, an OOM inside bb_draft_heal_forum_row()) discarded
			// every window already completed, so the +60s continuation re-read
			// the same cursor and walked the same rows again - and under
			// WP-CLI, where $time_budget is 0, neither post-loop branch runs at
			// all, so a whole-table drain that died at 90% restarted at 0%
			// (H5).
			//
			// Known residual: this bounds how much progress an interruption can
			// lose, but it cannot rescue a window whose own contents fatal every
			// time. A single unhealable row still stalls the pass at that
			// window; it no longer costs the windows before it.
			$state['cursor'] = $cursor;
			update_site_option( 'bb_draft_oneshot_state', $state );

			// Refresh the locks each window. This was the one sweep of the three
			// whose lock was written exactly once and never renewed, while both
			// siblings already refreshed theirs for precisely this reason:
			// `wp bb drafts cleanup` runs this with an UNLIMITED budget, so a
			// stage-1 drain longer than 5 minutes let the transient lapse
			// mid-run, after which the daily expiry sweep read both keys as free
			// and started read-modify-writing the same aggregated usermeta rows.
			// Last writer wins on those rows, so a heal could resurrect an inner
			// draft the expiry pass had just removed - with its attachment stamps
			// already released, which hands the 6-hour orphan cron a live draft's
			// media.
			set_site_transient( 'bb_draft_oneshot_lock', 1, 5 * MINUTE_IN_SECONDS );
			set_site_transient( 'bb_draft_maintenance_lock', 1, 5 * MINUTE_IN_SECONDS );

			// Window-level budget check - see bb_drafts_delete_expired(): a
			// window of only filtered-out keys never reaches the per-row check.
			if ( 0 < $time_budget && $batch['has_more'] && ( time() - $started_at ) >= $time_budget ) {
				$complete = false;
				break;
			}
		} while ( $batch['has_more'] );

		if ( ! $complete ) {
			$state['cursor'] = $cursor;
			update_site_option( 'bb_draft_oneshot_state', $state );
		} else {
			// Stage 1 scan finished. Mark it done, then DEFER the stage-2
			// aggregate to a SEPARATE slice on any time-limited (cron) run: the
			// GROUP BY/HAVING below is bounded in ROWS but not in TIME, and the
			// upgrade routine runs the first slice synchronously on an admin
			// request, so it must never execute there (reviewer: stage-2
			// unbounded). Marking scan_done (rather than re-deferring on an
			// empty re-scan) is what stops the defer from looping forever. The
			// WP-CLI drain ($time_budget 0) does not defer and falls straight
			// into the aggregate block below, running it inline.
			$state['scan_done'] = true;
			$state['cursor']    = $cursor;
			update_site_option( 'bb_draft_oneshot_state', $state );

			if ( 0 < $time_budget ) {
				$complete = false;
			}
		}
	}

	// Stage 1b - the indexed aggregate that finds aggregate-oversized users.
	// Its own block, gated on scan_done, so it runs on a FRESH cron slice after
	// the scan instead of piggybacking on the synchronous upgrade request. The
	// (small) user list is persisted rather than per-user byte totals, keeping
	// the state option bounded on sites with many draft holders.
	if ( $complete && ! is_array( $state['heavy_users'] ) && ! empty( $state['scan_done'] ) ) {
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
		update_site_option( 'bb_draft_oneshot_state', $state );
	}

	// Stage 2 - aggregate healing, draining the persisted user list.
	if ( $complete && is_array( $state['heavy_users'] ) ) {
		while ( ! empty( $state['heavy_users'] ) ) {
			$heavy_user_id = (int) array_shift( $state['heavy_users'] );

			$budget_result = bb_draft_enforce_user_budget( $heavy_user_id, '', 0, 'heal' );
			$healed       += count( $budget_result['evicted'] );

			// Stage 2 is unbudgeted under WP-CLI exactly as stage 1 is, and it
			// evicts rows network-wide, so it needs the same per-iteration lock
			// renewal.
			set_site_transient( 'bb_draft_oneshot_lock', 1, 5 * MINUTE_IN_SECONDS );
			set_site_transient( 'bb_draft_maintenance_lock', 1, 5 * MINUTE_IN_SECONDS );

			if ( 0 < $time_budget && ( time() - $started_at ) >= $time_budget && ! empty( $state['heavy_users'] ) ) {
				$complete = false;
				update_site_option( 'bb_draft_oneshot_state', $state );
				break;
			}
		}
	}

	if ( $complete ) {
		update_site_option( 'bb_draft_oneshot_done', 1 );
		delete_site_option( 'bb_draft_oneshot_state' );

		// Nothing left to resume - cancel the defensive continuation (on root).
		bb_draft_oneshot_unschedule();
	} else {
		bb_draft_oneshot_schedule( MINUTE_IN_SECONDS );
	}

	// Released after the state and the schedule are settled, so a run starting
	// the instant this one returns cannot read half-updated state.
	delete_site_transient( 'bb_draft_oneshot_lock' );
	bb_draft_release_lock( 'bb_draft_maintenance_lock', true );

	return array(
		'healed'   => $healed,
		'complete' => $complete,
	);
}


/**
 * Schedule the one-shot healing continuation on the ROOT blog.
 *
 * The one-shot heals network-GLOBAL draft usermeta and tracks its progress in
 * network-global site options, so it must run once network-wide. It is triggered
 * by bp_version_updater() on whichever blog's admin loads first after the
 * DB-version bump, but WP-Cron events live in the PER-BLOG cron table - so
 * scheduling on the current blog can strand the continuation on a low-traffic
 * subsite's cron where it never fires, while the version stays bumped so nothing
 * re-triggers it (L10). Pin every schedule/check of this event to the root blog,
 * mirroring bb_draft_cleanup_hook's root-only gate. The synchronous slice itself
 * still runs on the current blog - it operates on the global usermeta either way.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $delay Seconds from now to run.
 * @return void
 */
function bb_draft_oneshot_schedule( $delay ) {
	$switched = false;

	if ( is_multisite() && ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
		$switched = true;
	}

	if ( ! wp_next_scheduled( 'bb_draft_oneshot' ) ) {
		wp_schedule_single_event( time() + (int) $delay, 'bb_draft_oneshot' );
	}

	if ( $switched ) {
		restore_current_blog();
	}
}

/**
 * Cancel the root-blog one-shot healing continuation.
 *
 * Companion to {@see bb_draft_oneshot_schedule()} - the event lives on the root
 * blog's cron table, so it must be looked up and cleared there too (L10).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_draft_oneshot_unschedule() {
	$switched = false;

	if ( is_multisite() && ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
		$switched = true;
	}

	$scheduled = wp_next_scheduled( 'bb_draft_oneshot' );
	if ( $scheduled ) {
		wp_unschedule_event( $scheduled, 'bb_draft_oneshot' );
	}

	if ( $switched ) {
		restore_current_blog();
	}
}

/**
 * Acquire the orphan-stamp sweep lock, atomically where that is possible.
 *
 * The previous shape was `get_transient()` then `set_transient()` - a
 * check-then-set with a window between the two. Two sweeps (the daily cron and a
 * WP-CLI drain, or two overlapping cron workers) could both read "free" and both
 * proceed, and they share ONE cursor option: the slower one then writes its older
 * position over the faster one's, so a range is skipped for that cycle. No data
 * is lost - the skipped range is re-examined on the next run - but the sweep does
 * less work than it reports.
 *
 * `wp_cache_add()` is atomic on a persistent object cache: it fails if the key
 * already exists, so exactly one caller can win. It writes to the SAME store and
 * group that set/get/delete_transient() use, so the refresh and release calls
 * elsewhere keep working unchanged.
 *
 * Without a persistent object cache there is no atomic primitive available here:
 * transients live in options, and add_option() is `INSERT … ON DUPLICATE KEY
 * UPDATE`, which succeeds for a concurrent second caller. WordPress core reaches
 * for a raw `INSERT IGNORE` in that situation ({@see WP_Upgrader::create_lock()}).
 * That is deliberately NOT done here: it would only cover the configuration where
 * this race is least likely - a site small enough to run without an object cache
 * is unlikely to have overlapping sweeps - and the consequence is a skipped range,
 * not lost member data. That configuration therefore keeps the previous
 * check-then-set behaviour, stated rather than hidden.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $timeout Lock lifetime in seconds.
 * @return bool True when this caller now holds the lock.
 */
function bb_draft_acquire_stamp_sweep_lock( $timeout ) {
	return bb_draft_acquire_lock( 'bb_draft_stamp_sweep_lock', $timeout );
}

/**
 * Acquire a draft-maintenance lock, atomically where that is possible.
 *
 * The generic form of {@see bb_draft_acquire_stamp_sweep_lock()}, which now
 * delegates here. Three sweeps need the same primitive and two of them need it
 * NETWORK-scoped, so the scope is a parameter rather than three near-copies:
 *
 * - `transient` is NOT a global cache group, so a lock taken there is per blog.
 * - `site-transient` IS global, so a lock taken there covers the whole network.
 *
 * Getting that pairing wrong is not a style problem. The orphan-stamp sweep took
 * a per-blog lock around a network-global reference-scan window, so two blogs'
 * sweeps could run concurrently and the second would reset the first's ledger -
 * after which the first released stamps against a set that was no longer a
 * superset, and the 6-hour orphan cron deleted a member's file out of a draft
 * they were still writing. Scope the lock to the resource, not to the caller.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $key     Lock key, without any prefix.
 * @param int    $timeout Lock lifetime in seconds.
 * @param bool   $network Whether the lock must cover the whole network.
 *                        Default false (per blog).
 * @return bool True when this caller now holds the lock.
 */
function bb_draft_acquire_lock( $key, $timeout, $network = false ) {
	$timeout = (int) $timeout;
	$group   = $network ? 'site-transient' : 'transient';

	if ( wp_using_ext_object_cache() ) {
		// Atomic: fails outright when another sweep already holds it.
		return (bool) wp_cache_add( $key, 1, $group, $timeout );
	}

	if ( $network ? get_site_transient( $key ) : get_transient( $key ) ) {
		return false;
	}

	if ( $network ) {
		set_site_transient( $key, 1, $timeout );
	} else {
		set_transient( $key, 1, $timeout );
	}

	return true;
}

/**
 * Release a lock taken with {@see bb_draft_acquire_lock()}.
 *
 * Deliberately a named counterpart rather than a bare delete at each exit: a
 * lock released on some return paths and not others is held until its TTL, which
 * on these sweeps means a whole day of doing nothing.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $key     Lock key, without any prefix.
 * @param bool   $network Whether the lock was taken network-wide. Default false.
 * @return void
 */
function bb_draft_release_lock( $key, $network = false ) {
	if ( $network ) {
		delete_site_transient( $key );
	} else {
		delete_transient( $key );
	}
}

/**
 * Maximum attachment IDs the pending-reference ledger will hold for one scan.
 *
 * The ledger only ever collects references added DURING a reference scan, so on
 * any realistic site it holds a handful. The cap exists so a pathological window
 * cannot grow a site option without bound; crossing it makes the sweep fall back
 * to abstaining, which is the safe direction.
 *
 * @since BuddyBoss [BBVERSION]
 */
const BB_DRAFT_PENDING_REFERENCE_CAP = 5000;

/**
 * Maximum attachment IDs the shared referenced-set cache will store.
 *
 * The set is an aggregate of every draft-referenced attachment on the network and
 * is written whole into one cache entry, so it is unbounded by construction -
 * exactly the shape this ticket exists to fix, one tier down. An object cache
 * with a per-item size limit (Memcached's default is 1 MB) silently REFUSES an
 * oversized entry, so the cache would never warm, every save would fall through
 * the superset check, and the sweep would rebuild from scratch for ever.
 *
 * Declining to cache above this bound makes that outcome explicit and bounded
 * instead of silent. The cost is that very large networks lose the skip and
 * return to invalidating on every attachment-bearing save; correctness is
 * unaffected, because the set is a performance aid and never an authority the
 * sweep cannot rebuild.
 *
 * @since BuddyBoss [BBVERSION]
 */
const BB_DRAFT_REFERENCED_CACHE_MAX_IDS = 50000;

/**
 * Whether a network-wide reference scan is currently running.
 *
 * Invalidations consult this so the ledger below is only written while a scan
 * could actually miss something. Outside a scan window there is nothing to
 * record - the next scan reads the drafts straight from the database.
 *
 * Network-wide on purpose: the sweep runs per blog but scans drafts across the
 * network, so a save on blog A can add a reference that blog B's in-flight scan
 * would otherwise miss. A per-blog transient would not be visible to it.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool True while a scan is in progress.
 */
function bb_draft_reference_scan_is_active() {
	return (bool) get_site_transient( 'bb_draft_reference_scan_active' );
}

/**
 * Record attachment IDs referenced by a draft written DURING a reference scan.
 *
 * This is what lets the sweep tolerate a concurrent save instead of throwing its
 * whole scan away. A scan walks the draft rows with a cursor, so a draft edited
 * after the cursor has passed it contributes nothing to the result. Unioning the
 * ledger into that result restores the "cache is a SUPERSET of the live set"
 * invariant without requiring the scan to be atomic - and atomicity is exactly
 * what could never be achieved on a busy community, where the multi-minute scan
 * essentially never survives without a concurrent draft save (H-4 livelock).
 *
 * Over-collection is harmless and deliberate: an id recorded here that turns out
 * not to be referenced just keeps its stamp for one more cycle.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int[] $attachment_ids Attachment IDs the just-written draft keeps.
 * @return void
 */
function bb_draft_record_pending_reference_ids( $attachment_ids ) {
	if ( empty( $attachment_ids ) || ! is_array( $attachment_ids ) || ! bb_draft_reference_scan_is_active() ) {
		return;
	}

	$ledger = get_site_option( 'bb_draft_pending_reference_ids', array() );

	if ( ! is_array( $ledger ) ) {
		$ledger = array();
	}

	// Already over the cap: the sweep will abstain anyway, so stop growing it.
	if ( isset( $ledger['overflow'] ) ) {
		return;
	}

	$ids = isset( $ledger['ids'] ) && is_array( $ledger['ids'] ) ? $ledger['ids'] : array();

	foreach ( $attachment_ids as $attachment_id ) {
		$attachment_id = (int) $attachment_id;

		if ( $attachment_id > 0 ) {
			$ids[ $attachment_id ] = true;
		}
	}

	if ( count( $ids ) > BB_DRAFT_PENDING_REFERENCE_CAP ) {
		// Cannot prove the union is complete any more, so say so rather than
		// silently under-collecting. The sweep reads this and abstains.
		update_site_option( 'bb_draft_pending_reference_ids', array( 'overflow' => true ) );

		return;
	}

	update_site_option( 'bb_draft_pending_reference_ids', array( 'ids' => $ids ) );
}

/**
 * Open a reference-scan window: start recording concurrent additions.
 *
 * The ledger is reset here, not at the end, so a window always begins empty even
 * if a previous scan died mid-run.
 *
 * The returned token identifies THIS window. Because the reset above destroys
 * whatever a still-running sweep had recorded, a caller must hold on to the
 * token and re-check it ({@see bb_draft_reference_scan_token()}) before trusting
 * the ledger - otherwise it cannot tell its own window from a replacement.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string Token identifying the window that was just opened.
 */
function bb_draft_reference_scan_begin() {
	$token = uniqid( 'bbdrs', true );

	delete_site_option( 'bb_draft_pending_reference_ids' );
	set_site_transient( 'bb_draft_reference_scan_active', $token, 30 * MINUTE_IN_SECONDS );

	return $token;
}

/**
 * Read the identity of the reference-scan window that is currently open.
 *
 * The window used to be a bare `1`, which made it impossible to tell "my window
 * is still open" from "my window closed and somebody else opened a new one".
 * Both of those look identical to {@see bb_draft_reference_scan_is_active()},
 * and they have opposite consequences: in the second case the ledger the sweep
 * is about to trust was reset by the other run, so it is no longer a record of
 * everything added since THIS scan started.
 *
 * A sweep therefore keeps the token it was handed by
 * {@see bb_draft_reference_scan_begin()} and re-checks it before it trusts the
 * ledger. Anything other than an exact match means abstain.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string The open window's token, or '' when no window is open.
 */
function bb_draft_reference_scan_token() {
	$token = get_site_transient( 'bb_draft_reference_scan_active' );

	return is_string( $token ) ? $token : '';
}

/**
 * Extend the reference-scan window, but only while this caller still owns it.
 *
 * The window is a 30-minute transient and was written exactly once, at
 * `begin()`. The scan it protects is deliberately unbudgeted, and
 * `wp bb drafts cleanup` removes the bound from the release drain as well, so a
 * run on a large community can outlive its own window. Nothing detected that:
 * once the transient expired, {@see bb_draft_record_pending_reference_ids()}
 * silently stopped recording, the ledger read back empty with
 * `overflow => false`, and the sweep could not tell "nothing was added" from
 * "I stopped listening" - so it released stamps for attachments a live draft
 * had just claimed.
 *
 * This is the same treatment the sweep LOCK already gets two lines away, for the
 * identical reason. The ownership check matters: a stale run must not resurrect
 * a window that now belongs to somebody else.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $token The token this caller received from `begin()`.
 * @return bool True when the window is still this caller's and was extended.
 */
function bb_draft_reference_scan_refresh( $token ) {
	if ( '' === (string) $token || bb_draft_reference_scan_token() !== (string) $token ) {
		return false;
	}

	set_site_transient( 'bb_draft_reference_scan_active', $token, 30 * MINUTE_IN_SECONDS );

	return true;
}

/**
 * Read the pending-reference ledger WITHOUT closing the window.
 *
 * The release loop folds this in before judging each batch, so a save that lands
 * mid-drain protects its own attachments straight away instead of waiting for
 * the next run.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array{ids:int[],overflow:bool} Recorded IDs, and whether the cap was hit.
 */
function bb_draft_peek_pending_reference_ids() {
	$ledger = get_site_option( 'bb_draft_pending_reference_ids', array() );

	if ( ! is_array( $ledger ) ) {
		return array(
			'ids'      => array(),
			'overflow' => false,
		);
	}

	return array(
		'ids'      => isset( $ledger['ids'] ) && is_array( $ledger['ids'] ) ? array_keys( $ledger['ids'] ) : array(),
		'overflow' => ! empty( $ledger['overflow'] ),
	);
}

/**
 * Close a reference-scan window and return what was recorded during it.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array{ids:int[],overflow:bool} Recorded IDs, and whether the cap was hit.
 */
function bb_draft_reference_scan_end() {
	$ledger = get_site_option( 'bb_draft_pending_reference_ids', array() );

	delete_site_transient( 'bb_draft_reference_scan_active' );
	delete_site_option( 'bb_draft_pending_reference_ids' );

	if ( ! is_array( $ledger ) ) {
		return array(
			'ids'      => array(),
			'overflow' => false,
		);
	}

	return array(
		'ids'      => isset( $ledger['ids'] ) && is_array( $ledger['ids'] ) ? array_keys( $ledger['ids'] ) : array(),
		'overflow' => ! empty( $ledger['overflow'] ),
	);
}

/**
 * Read the referenced-attachment cache's change token.
 *
 * The sweep reads this twice - once before it gathers anything, once before it
 * commits - and abstains if the two differ. Both reads and the write in
 * {@see bb_draft_invalidate_referenced_cache()} must therefore agree about where
 * the token lives, so the read is centralised here rather than repeated at each
 * site. Tests assert the token's DURABILITY through this accessor for the same
 * reason: a test that hard-coded the storage call would keep passing if the
 * storage moved somewhere non-durable, which is exactly the regression the
 * durability requirement exists to prevent.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string The current token, or '' when no invalidation has been recorded.
 */
function bb_draft_get_referenced_cache_token() {
	return (string) get_site_option( 'bb_draft_referenced_stamp_ids_token', '' );
}

/**
 * Invalidate the shared referenced-attachment cache after a draft write adds a
 * reference, and record that the invalidation happened.
 *
 * A bare delete of `bb_draft_referenced_stamp_ids` is not enough on its own. The
 * orphan-stamp sweep reads the cache and, on a miss, runs an unbudgeted
 * network-wide scan and only writes the result back when the scan finishes. A
 * save landing BETWEEN the sweep's cache-miss read and that write deletes
 * nothing (the cache is momentarily empty) and the sweep then commits a set that
 * is MISSING the just-added reference for the whole TTL - so the sweep can
 * release, and the orphan cron then hard-delete, an attachment a live draft
 * still holds (a TOCTOU that violates the "cache is always a SUPERSET of the
 * live set" invariant the sweep relies on).
 *
 * The token turns that otherwise-silent overwrite into a detectable event: the
 * sweep captures the token before it gathers data and refuses to cache or
 * release a set built across a change. The value must differ on every call, so
 * a no-op write can never hide a change.
 *
 * The token is a SITE OPTION, and its DURABILITY is the correctness property -
 * do not move it back to a transient or the object cache.
 *
 * It was briefly a site transient, for performance: this sits on the draft-save
 * path, and a site option write here cost a full autoloaded-option rebuild
 * (~900 rows / ~0.6 MB on a mature install, measured). That was a real cost, but
 * the trade was unsound. A transient shares the object cache it is meant to
 * guard, so the token can be lost by eviction or by any wp_cache_flush() - and
 * the loss is SYMMETRIC:
 *
 *   1. the sweep starts on a cache miss and captures the token - absent, ''
 *   2. its unbudgeted network-wide scan runs, for minutes on a large site
 *   3. a save adds a genuinely NEW reference and writes a token
 *   4. the cache evicts it, or anything calls wp_cache_flush()
 *   5. the sweep re-reads - absent, '' - and compares EQUAL to step 1
 *   6. it commits a set missing the step-3 reference and releases that stamp,
 *      and the orphan cron hard-deletes a file a live draft still holds
 *
 * An earlier version of this docblock asserted that a lost token could only ever
 * make the sweep abstain. That is true only for ASYMMETRIC loss; the timeline
 * above is the symmetric case, and it loses member data. A successful
 * update_site_option() persists, so step 4 has no equivalent.
 *
 * The performance objection is now largely moot: the superset check below means
 * an ordinary autosave of an unchanged attachment list writes NOTHING at all, so
 * the option write only happens when a genuinely new reference appears - rarely,
 * and exactly when correctness demands a durable record of it (H-4).
 *
 * Residual, stated rather than hidden: a FAILED option write (a DB error) still
 * reads as "no change". At that point the site has larger problems, but the
 * window is real and is not closed here.
 *
 * On the mechanism, for anyone re-testing the cost: update_site_option() routes
 * through update_network_option(), which on single site calls update_option()
 * with an EXPLICIT autoload of false. It therefore does not take update_option()'s
 * "no autoload argument" branch - it takes the final else, which reloads
 * alloptions too. Every branch of update_option() rebuilds it; the earlier claim
 * here that it happens "before it even looks at whether the option is autoloaded"
 * described a branch this call path never reaches.
 *
 * Pass `$kept_attachment_ids` wherever the caller knows every attachment the
 * draft it just wrote keeps. When the cached set ALREADY contains all of them it
 * is still a superset of the live referenced set, so there is nothing to
 * invalidate and no reason to move the token - and moving it anyway is what let
 * ordinary autosaves starve the sweep (H-4). This is an exact test of the
 * superset invariant, not a heuristic.
 *
 * It is deliberately NOT the other tempting test - "did we just stamp something
 * new?". A restored draft echoes `bb_media_draft` back already set and a stamp
 * can outlive the draft that created it, so stamp-presence is not set-membership
 * and a draft newly referencing an already-stamped attachment grows the live set
 * with no stamp transition at all (F7). Key on what the draft KEEPS, always.
 *
 * Omit the argument (or pass a non-array) to invalidate unconditionally, which
 * is the correct conservative default for any caller that cannot enumerate the
 * kept set.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int[]|null $kept_attachment_ids Attachment IDs the just-written draft
 *                                        still references. Default null
 *                                        (invalidate unconditionally).
 * @return void
 */
function bb_draft_invalidate_referenced_cache( $kept_attachment_ids = null ) {
	if ( is_array( $kept_attachment_ids ) ) {
		$referenced = get_site_transient( 'bb_draft_referenced_stamp_ids' );

		// Only a PRESENT cache can prove the superset still holds. On a miss the
		// sweep may be mid-scan with nothing cached, which is exactly when the
		// token has to move so that scan abstains.
		if ( is_array( $referenced ) ) {
			$adds_reference = false;

			foreach ( $kept_attachment_ids as $kept_attachment_id ) {
				if ( ! isset( $referenced[ (int) $kept_attachment_id ] ) ) {
					$adds_reference = true;
					break;
				}
			}

			if ( ! $adds_reference ) {
				return;
			}
		}
	}

	// Recorded BEFORE the cache is dropped, so a scan running right now cannot
	// finish between the two and miss this reference. No-ops unless a scan is
	// actually in flight ({@see bb_draft_record_pending_reference_ids()}).
	if ( is_array( $kept_attachment_ids ) ) {
		bb_draft_record_pending_reference_ids( $kept_attachment_ids );
	} elseif ( bb_draft_reference_scan_is_active() ) {
		// A caller that cannot enumerate what it kept has told us something
		// changed without telling us what. The union below is only a provable
		// superset when every concurrent addition was recorded, so an unenumerated
		// change makes it unprovable - mark the window and let the sweep fall back
		// to abstaining, which is what it did for every change before H-4.
		//
		// This is why the livelock fix is safe: it relaxes the guard ONLY for
		// callers that say exactly which attachments they kept.
		update_site_option( 'bb_draft_pending_reference_ids', array( 'overflow' => true ) );
	}

	delete_site_transient( 'bb_draft_referenced_stamp_ids' );
	update_site_option( 'bb_draft_referenced_stamp_ids_token', uniqid( (string) wp_rand(), true ) );
}

/**
 * Collect every attachment ID any STORED draft still references.
 *
 * The orphan-attachment cron reaps an attachment only when it has NO
 * `bb_media_draft` meta, so that stamp is a "a draft still needs this" flag.
 * The stamp is released when a draft is disposed, replaced or evicted - but a
 * save REFUSED by a size/attachment cap stamps the member's uploads (correctly,
 * before any cap can run - the BLOCKER-1 rule) and then stores no draft, so
 * nothing ever releases those stamps and the files become permanently
 * uncollectable (M3). This builds the authoritative "still referenced" set so
 * the sweep below can release exactly the stamps nothing points at.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int           $time_budget Seconds to spend; 0 for unlimited.
 * @param int           $started_at  Run start time; 0 uses now.
 * @param callable|null $keep_alive  Optional. Invoked once per scan window so a
 *                                   long unbudgeted scan can refresh the caller's
 *                                   lock. Default null.
 * @return array|false Map of attachment ID => true, or false when the scan could
 *                     not be completed authoritatively - either it ran out of
 *                     budget, or a batch query failed / declined. Callers MUST
 *                     treat false as "unknown", never as "nothing referenced".
 */
function bb_drafts_collect_referenced_attachment_ids( $time_budget = 0, $started_at = 0, $keep_alive = null ) {
	$referenced  = array();
	$cursor      = 0;
	$time_budget = (int) $time_budget;
	$started_at  = $started_at ? (int) $started_at : time();

	/**
	 * Filters the row batch size for the draft reference scan.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $batch_size Draft rows read per window. Default 200.
	 */
	$batch_size = max( 1, (int) apply_filters( 'bb_draft_reference_scan_batch_size', 200 ) );

	// Only attachment IDs are retained, so the map is bounded by distinct
	// referenced attachments, not by row width; the per-window payload is
	// byte-bounded by bb_draft_get_rows_batch(). The set must be COMPLETE
	// before any release, or a referenced attachment scanned late would be
	// wrongly freed - so a run that cannot finish the scan inside its budget
	// returns false and the caller releases nothing this time (M3 HIGH).
	do {
		$batch = bb_draft_get_rows_batch( $cursor, $batch_size, true );

		// Both are "not authoritative", and for the release decision they mean the
		// same thing: this scan cannot prove what is referenced, so nothing may be
		// freed against it. Returning false routes them into the same path the
		// budget-exhaustion case already uses - release nothing, cache nothing
		// (BLOCKER-2).
		if ( ! empty( $batch['failed'] ) || ! empty( $batch['declined'] ) ) {
			return false;
		}

		foreach ( $batch['rows'] as $row ) {
			$value = bb_draft_safe_unserialize( $row['meta_value'] );

			if ( ! is_array( $value ) ) {
				$cursor = (int) $row['umeta_id'];
				continue;
			}

			if ( 'bb_user_topic_reply_draft' === $row['meta_key'] ) {
				foreach ( $value as $inner_draft ) {
					foreach ( bb_draft_collect_attachment_ids( $inner_draft ) as $attachment_id ) {
						$referenced[ (int) $attachment_id ] = true;
					}
				}
			} else {
				foreach ( bb_draft_collect_attachment_ids( $value ) as $attachment_id ) {
					$referenced[ (int) $attachment_id ] = true;
				}
			}

			$cursor = (int) $row['umeta_id'];
		}

		$cursor = (int) $batch['last_id'];

		// Refresh the caller's lock each window. This unbudgeted scan runs FIRST,
		// before the release loop's own refresh, and on the large-community case
		// this feature targets it can itself outlast the 5-minute lock TTL - the
		// lock would then expire mid-scan and a second run could start, reopening
		// the cursor-clobber race the lock exists to prevent, just relocated to
		// the scan phase. The sweep passes a closure that re-sets its lock.
		if ( is_callable( $keep_alive ) ) {
			call_user_func( $keep_alive );
		}

		if ( 0 < $time_budget && ( time() - $started_at ) >= $time_budget ) {
			return false;
		}
	} while ( $batch['has_more'] );

	return $referenced;
}

/**
 * Release draft-protection stamps from attachments no stored draft references.
 *
 * A save refused by a cap leaves the member's uploads stamped `bb_media_draft`
 * with no draft to release them, so the orphan cron never reaps them (M3). This
 * releases the stamp - it does NOT hard-delete - from stamped, unsaved
 * attachments older than the retention window that the reference scan proves
 * nothing still points at. The existing orphan cron then collects the file on
 * its own schedule. Releasing on refusal itself is deliberately NOT the fix:
 * the member may retry the same attachments with less text, and unprotecting
 * them mid-compose is the data loss BLOCKER-1 closed.
 *
 * Bounded like its siblings ({@see bb_drafts_delete_expired}): held under
 * `bb_draft_stamp_sweep_lock` (refreshed each window so an unlimited CLI drain
 * keeps holding it) and CURSORED over the candidate attachments by ID
 * (`bb_draft_stamp_sweep_cursor`). Both are BLOG-scoped (get_option /
 * get_transient, not the *_site_* variants) because the candidate attachments
 * live in the current blog's per-site tables, so every blog cursors its own
 * attachment space independently. The cursor is what fixes the earlier fatal
 * shape - a bare `LIMIT 500` with no order returned the same referenced rows
 * every run once a site held more than 500 of them, so not one orphan past that
 * window was ever released. The cursor advances over EVERY candidate,
 * referenced or not, so a referenced attachment is skipped without stalling
 * progress and is simply re-examined on the next full pass.
 *
 * The time budget applies ONLY to the release loop, which is sliceable (its
 * cursor persists and resumes on the next daily run). The reference scan is
 * NOT sliceable - the referenced set must be complete before any release, and
 * a draft edited between slices could add a reference to an already-scanned
 * attachment - so it always runs to completion regardless of the budget. That
 * is why the budget is not passed to it (an earlier version did, and a scan
 * that could not finish in the daily 10s released nothing and, with no
 * continuation, restarted from zero every day - permanent leak on large sites).
 *
 * Runs on its own per-site daily hook (`bb_draft_stamp_release_hook`), never the
 * 60-second expiry continuation, so the full-table reference scan cannot re-run
 * every minute. That per-site hook (not the root-only `bb_draft_cleanup_hook`)
 * is what lets the sweep reach every blog's attachments (D2). The reference set
 * is still built from network-global usermeta, so it is a global superset for
 * any one blog: a cross-blog attachment-ID collision can only wrongly KEEP a
 * stamp (a leak), never wrongly release an in-use file. Expiry being disabled
 * ({@see bb_draft_retention_days()} = 0) switches this off too.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $time_budget Seconds to spend this run; 0 for unlimited.
 * @return array { @type int $released @type bool $complete @type bool $locked }
 */
function bb_drafts_release_orphaned_draft_stamps( $time_budget = 10 ) {
	global $wpdb;

	$time_budget = (int) $time_budget;
	$started_at  = time();

	$retention_seconds = bb_draft_retention_seconds();

	// Expiry off means "keep drafts forever", so their attachments must keep
	// their protection forever too.
	if ( 1 > $retention_seconds ) {
		return array(
			'released' => 0,
			'complete' => true,
		);
	}

	// Serialize against a second sweep (a concurrent daily fire, or a CLI drain
	// racing the cron) so two runs cannot advance the cursor over each other.
	// Acquired in ONE atomic step where the backend allows it - the previous
	// check-then-set let both runs read "free" and proceed
	// ({@see bb_draft_acquire_stamp_sweep_lock()}).
	if ( ! bb_draft_acquire_stamp_sweep_lock( 5 * MINUTE_IN_SECONDS ) ) {
		return array(
			'released' => 0,
			'complete' => false,
			'locked'   => true,
		);
	}

	// That lock is per BLOG - `transient` is not a global cache group - which is
	// correct for the cursor it protects, because the cursor is a per-blog
	// option. The reference-scan window and its ledger below are NETWORK-global,
	// so the blog lock does not serialize them at all.
	//
	// That gap was reachable by design, not by accident: D2 schedules this sweep
	// on EVERY blog. Blog B's bb_draft_reference_scan_begin() resets blog A's
	// in-flight ledger while A is still scanning, and B's end() closes the shared
	// window from under A. A then read an empty ledger with overflow => false,
	// could not distinguish that from "nothing was added", and released stamps
	// against a set that was no longer a superset - after which the pre-existing
	// 6-hour orphan cron hard-deleted a member's photo out of a draft they were
	// still writing. Serialize the resource that is actually shared.
	//
	// Abstaining (rather than reporting `locked`) is deliberate: the other blog's
	// run is doing this network's scan, so there is nothing for this one to do
	// and nothing here to retry immediately.
	if ( ! bb_draft_acquire_lock( 'bb_draft_reference_scan_lock', 30 * MINUTE_IN_SECONDS, true ) ) {
		delete_transient( 'bb_draft_stamp_sweep_lock' );

		return array(
			'released'  => 0,
			'complete'  => false,
			'abstained' => true,
			'reason'    => 'scan_locked',
		);
	}

	// The referenced set must be COMPLETE before any release, or an attachment a
	// not-yet-scanned draft still holds would be wrongly freed. The scan
	// therefore CANNOT be sliced across runs (a draft edited between slices
	// could add a reference to an already-scanned attachment), so it always runs
	// to completion regardless of this run's budget. Passing the run budget here
	// was the bug: on a site whose full draft scan exceeds the daily 10s the
	// scan returned false every day, the sweep released nothing, and - with no
	// continuation - the next daily run restarted from zero, so M3's leak stayed
	// permanent on exactly the large communities it targets. The budget now
	// governs only the release loop below, which IS sliceable (its cursor is
	// persisted and resumes on the next daily run). A community so large that
	// even the reference scan alone exceeds PHP's execution limit should drain
	// with `wp bb drafts cleanup` (unlimited).
	// The referenced set is built from network-GLOBAL draft usermeta, so it is
	// identical on every blog. Now that the sweep runs per-site (D2), scanning
	// the whole draft table once per blog per day is wasted work (H3): cache the
	// set network-wide and reuse it. The cache is dropped whenever a draft
	// stamps a new attachment ({@see bb_draft_protect_payload_attachments}), so
	// between rebuilds it can only ever be a SUPERSET of the live referenced set
	// - safe, because over-protecting leaks a stamp for one cycle but can never
	// release an attachment a draft still holds. A TTL backstop rebuilds it if
	// an invalidation is ever missed. On a busy network the set genuinely
	// changes and each rescan is legitimate; the cache only elides the identical
	// re-scans on quiet cleanup windows.
	//
	// That SUPERSET guarantee holds only while no reference is ADDED across the
	// scan/commit boundary. Two independent mechanisms cover that, and they cover
	// different halves of it:
	//
	// The recording window below logs every reference added while this sweep
	// runs, and the union of the scan result with that ledger restores the
	// superset property. That is what makes a release decision safe.
	//
	// The cache token ({@see bb_draft_invalidate_referenced_cache()}) is captured
	// much later, immediately before that union is read, and governs CACHING
	// only - see the comment at the set_site_transient() call.
	//
	// Opened for the WHOLE sweep, before the cache is even read, and closed on
	// every exit below. The scan is not the only place a concurrent save can be
	// missed - the release loop judges batch after batch against a set that was
	// fixed before it started - so the window has to span both (H-4).
	//
	// The token this returns identifies this window. Everything that trusts the
	// ledger re-checks it first: a window that expired, or that another run
	// replaced, means the ledger is no longer a record of everything added since
	// this scan started, and the only safe response is to abstain.
	$scan_token = bb_draft_reference_scan_begin();

	$referenced = get_site_transient( 'bb_draft_referenced_stamp_ids' );

	if ( ! is_array( $referenced ) ) {
		// Hold the lock across the (unbudgeted) scan too, not just the release
		// loop below - on a large library the scan alone can outlast the TTL.
		//
		// The recording WINDOW has exactly the same exposure and used to be left
		// out, which was worse than losing the lock: an expired lock costs
		// duplicate work, but an expired window makes
		// bb_draft_record_pending_reference_ids() silently stop recording, and
		// the ledger then reads back empty and indistinguishable from "nothing
		// was added". Refresh all three together or the sweep can go blind
		// halfway through and never know.
		$referenced = bb_drafts_collect_referenced_attachment_ids(
			0,
			$started_at,
			function () use ( $scan_token ) {
				set_transient( 'bb_draft_stamp_sweep_lock', 1, 5 * MINUTE_IN_SECONDS );
				set_site_transient( 'bb_draft_reference_scan_lock', 1, 30 * MINUTE_IN_SECONDS );
				bb_draft_reference_scan_refresh( $scan_token );
			}
		);

		// false means the scan is NOT authoritative - it ran out of budget, or a
		// batch query failed or was declined. A partial set must never reach the
		// release loop or seed the cache: "I could not read the drafts" and "the
		// drafts reference nothing" have opposite consequences, and conflating
		// them releases every stamp on the site (BLOCKER-2).
		if ( false === $referenced ) {
			bb_draft_reference_scan_end();
			bb_draft_release_lock( 'bb_draft_reference_scan_lock', true );
			delete_transient( 'bb_draft_stamp_sweep_lock' );

			return array(
				'released'  => 0,
				'complete'  => false,
				'abstained' => true,
				'reason'    => 'scan_incomplete',
			);
		}

		// Close the recording window and fold in everything that was written
		// while the scan ran. The scan walks the draft rows with a cursor, so a
		// draft edited after the cursor passed it contributes nothing to
		// $referenced - and that is precisely what the union repairs.
		//
		// This REPLACES the previous behaviour, which abstained outright whenever
		// the token moved during the scan. That was safe but unachievable on the
		// sites it was written for: while the cache is cold every attachment-
		// bearing save network-wide bumps the token, the scan takes minutes, and
		// the chance of zero such saves across it is nil - so the sweep abstained
		// every run, for ever, and the stamps it exists to reclaim accumulated
		// without bound (the H-4 livelock). Tolerating the change is strictly
		// better than requiring an atomicity the system cannot provide.
		//
		// Tolerating it still requires the ledger to be TRUSTWORTHY, and the two
		// ways it silently stops being trustworthy are an expired window and a
		// window another run replaced. Both read back as an empty ledger with
		// overflow => false, i.e. exactly like "nothing was added". Check the
		// window is still ours before believing it.
		if ( ! bb_draft_reference_scan_refresh( $scan_token ) ) {
			bb_draft_reference_scan_end();
			bb_draft_release_lock( 'bb_draft_reference_scan_lock', true );
			delete_transient( 'bb_draft_stamp_sweep_lock' );

			return array(
				'released'  => 0,
				'complete'  => false,
				'abstained' => true,
				'reason'    => 'scan_window_lost',
			);
		}

		// Captured HERE, not before the scan. The token governs caching only, and
		// capturing it before a multi-minute scan meant it had essentially always
		// moved by the time the cache write was reached - so on any populated
		// community the set was rebuilt and thrown away every single run and the
		// cache could never warm, which is precisely the site the cache exists
		// for. Read across the narrow peek-to-write gap instead, which is the
		// only window the union below does not already cover.
		$reference_token = bb_draft_get_referenced_cache_token();

		$pending = bb_draft_peek_pending_reference_ids();

		foreach ( $pending['ids'] as $pending_id ) {
			$referenced[ (int) $pending_id ] = true;
		}

		// The one case the union cannot cover: so many references arrived during
		// the window that the ledger stopped recording, so it is no longer a
		// provable superset. Fall back to the old conservative behaviour.
		if ( $pending['overflow'] ) {
			bb_draft_reference_scan_end();
			bb_draft_release_lock( 'bb_draft_reference_scan_lock', true );
			delete_transient( 'bb_draft_stamp_sweep_lock' );

			return array(
				'released'  => 0,
				'complete'  => false,
				'abstained' => true,
				'reason'    => 'ledger_overflow',
			);
		}

		/**
		 * Filters the TTL (seconds) of the network-wide referenced-attachment
		 * cache shared by every blog's orphan-stamp sweep. Correctness does not
		 * depend on it (stamping invalidates the cache); it only bounds how long
		 * a released reference lingers as safe over-protection.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int $ttl Cache lifetime in seconds. Default 12 hours.
		 */
		$referenced_ttl = (int) apply_filters( 'bb_draft_referenced_cache_ttl', 12 * HOUR_IN_SECONDS );

		// $referenced is now a superset of the live set and is SAFE to release
		// against. Caching it is a separate question: if the token moved, the set
		// is correct for right now but would be pinned for the whole TTL while
		// the drafts behind it keep changing, so it is used and discarded.
		/**
		 * Filters the maximum number of attachment IDs the referenced-set cache
		 * will hold before it declines to cache at all.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int $max_ids Maximum IDs. Default BB_DRAFT_REFERENCED_CACHE_MAX_IDS.
		 */
		$referenced_max = (int) apply_filters( 'bb_draft_referenced_cache_max_ids', BB_DRAFT_REFERENCED_CACHE_MAX_IDS );

		// Two independent reasons to use the set and then throw it away.
		//
		// Token moved: the set is correct for right now, but caching it would pin
		// it for the whole TTL while the drafts behind it keep changing.
		//
		// Too large: one cache entry holding every referenced attachment on the
		// network is unbounded, and a backend with a per-item cap refuses it
		// SILENTLY - the write appears to succeed and the read comes back empty
		// for ever. Declining above a known bound turns that into a stated
		// limitation instead of a permanent invisible cache miss.
		$referenced_fits = ( $referenced_max <= 0 || count( $referenced ) <= $referenced_max );

		if ( ! $referenced_fits && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- gated behind WP_DEBUG; a silent decline here is what this logs.
				sprintf(
					'BuddyBoss drafts: referenced-attachment set (%d ids) exceeds the cache bound (%d); not caching. The orphan-stamp sweep will rebuild it each run.',
					count( $referenced ),
					$referenced_max
				)
			);
		}

		if ( $referenced_fits && bb_draft_get_referenced_cache_token() === $reference_token ) {
			set_site_transient( 'bb_draft_referenced_stamp_ids', $referenced, $referenced_ttl );
		}
	}

	// Measure the release budget from AFTER the mandatory scan, so a slow scan
	// does not eat the slice the sliceable release loop is entitled to.
	$started_at = time();

	$cutoff         = gmdate( 'Y-m-d H:i:s', time() - $retention_seconds );
	$cursor         = (int) get_option( 'bb_draft_stamp_sweep_cursor', 0 );
	$released       = 0;
	$complete       = true;
	$abstained      = false;
	$abstain_reason = '';

	/**
	 * Filters the candidate batch size for the orphan-stamp sweep.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $batch_size Attachments examined per query. Default 200.
	 */
	$batch_size = max( 1, (int) apply_filters( 'bb_draft_stamp_sweep_batch_size', 200 ) );

	do {
		// The ledger is the ONLY thing standing between a save that lands mid-
		// drain and this loop releasing that draft's stamps: the referenced set
		// was fixed before the loop started, so a reference added since then
		// exists nowhere else. Re-check the window is still ours and still open
		// before trusting it - an expired or replaced window makes the ledger
		// read back empty, which is indistinguishable from "nothing was added"
		// and has the opposite consequence. Refreshing here also keeps the window
		// alive for the rest of the drain, which `wp bb drafts cleanup` runs with
		// no budget at all.
		//
		// The cache token plays NO part in this loop. It is read in exactly two
		// places and both govern CACHING: once immediately before the union
		// above, and once at the set_site_transient() call. An earlier version of
		// this comment described the loop as stopping when the token moved, and
		// as abstaining on any attachment-bearing save. It never did either, and
		// the paragraph below is the reason stopping would be wrong.
		//
		// Fold in anything recorded since the set was fixed, then judge this
		// batch against the result. Breaking out here (the previous behaviour)
		// meant an ordinary save mid-drain ended the run, which on a busy site
		// is most runs - the same livelock as the scan, one level down.
		if ( ! bb_draft_reference_scan_refresh( $scan_token ) ) {
			$complete       = false;
			$abstained      = true;
			$abstain_reason = 'scan_window_lost';

			break;
		}

		$batch_pending = bb_draft_peek_pending_reference_ids();

		foreach ( $batch_pending['ids'] as $batch_pending_id ) {
			$referenced[ (int) $batch_pending_id ] = true;
		}

		if ( $batch_pending['overflow'] ) {
			$complete       = false;
			$abstained      = true;
			$abstain_reason = 'ledger_overflow';

			break;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- maintenance sweep over draft-stamped attachments, cursored.
		$candidate_ids = $wpdb->get_col(
			$wpdb->prepare(
				// Two kinds of draft attachment, each with its OWN set of markers.
				// bb_media_draft is written for photos, documents AND videos alike
				// ({@see bb_draft_protect_payload_attachments()}), but the "saved"
				// flag beside it is type-specific: bp_media_saved for a photo,
				// bp_document_saved for a document, bp_video_saved for a video. An
				// activity feature image is the separate pair
				// bb_activity_post_feature_image_draft +
				// bb_activity_post_feature_image_saved.
				//
				// Pairing bb_media_draft with bp_media_saved alone - as this did -
				// made every stamped document and video structurally unselectable,
				// so their stamps could never be released and their files could
				// never be reclaimed by bp_document_delete_orphaned_attachments() /
				// bp_video_delete_orphaned_attachments(), both of which require the
				// stamp to be absent. Those are the largest files a community
				// stores. The pairs stay matched explicitly so a marker is still
				// never read against another type's "saved" flag.
				//
				// Feature images were previously absent from this list, so their
				// marker was only ever cleared by the normal discard/replace/expiry
				// paths. Anything those missed - an interrupted request, a refused
				// save, an evicted row - kept its marker for ever, and no cleanup in
				// either plugin could reclaim the file. The release loop below
				// already clears BOTH markers, so only this selection was missing.
				"SELECT DISTINCT p.ID
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} d ON (
					d.post_id = p.ID
					AND d.meta_key IN ( 'bb_media_draft', 'bb_activity_post_feature_image_draft' )
				)
				INNER JOIN {$wpdb->postmeta} s ON (
					s.post_id = p.ID
					AND s.meta_value = '0'
					AND (
						( d.meta_key = 'bb_media_draft' AND s.meta_key IN ( 'bp_media_saved', 'bp_document_saved', 'bp_video_saved' ) )
						OR ( d.meta_key = 'bb_activity_post_feature_image_draft' AND s.meta_key = 'bb_activity_post_feature_image_saved' )
					)
				)
				WHERE p.post_type = 'attachment'
				AND p.post_date_gmt < %s
				AND p.ID > %d
				ORDER BY p.ID ASC
				LIMIT %d",
				$cutoff,
				$cursor,
				$batch_size
			)
		);

		if ( empty( $candidate_ids ) ) {
			break;
		}

		foreach ( $candidate_ids as $attachment_id ) {
			$attachment_id = (int) $attachment_id;

			// Advance the cursor over EVERY candidate, referenced or not, so a
			// referenced attachment never stalls the scan.
			$cursor = $attachment_id;

			if ( isset( $referenced[ $attachment_id ] ) ) {
				continue;
			}

			delete_post_meta( $attachment_id, 'bb_media_draft' );
			delete_post_meta( $attachment_id, 'bb_activity_post_feature_image_draft' );
			++$released;
		}

		update_option( 'bb_draft_stamp_sweep_cursor', $cursor, false );

		// Refresh the lock each window. `wp bb drafts cleanup` runs this with an
		// UNLIMITED budget and drains the whole candidate set in one call; on a
		// large library that drain exceeds the 5-minute TTL, and without this
		// refresh the lock would expire mid-drain, the daily cron would acquire
		// it, and both runs would advance the single bb_draft_stamp_sweep_cursor
		// over each other - the clobber the lock exists to prevent (M6, matching
		// bb_drafts_delete_expired). The network scan lock is refreshed with it
		// for the same reason: it is held for the life of this sweep, and an
		// unbudgeted drain outlives its TTL just as easily as the scan does.
		set_transient( 'bb_draft_stamp_sweep_lock', 1, 5 * MINUTE_IN_SECONDS );
		set_site_transient( 'bb_draft_reference_scan_lock', 1, 30 * MINUTE_IN_SECONDS );

		$batch_was_full = ( count( $candidate_ids ) === $batch_size );

		if ( 0 < $time_budget && ( time() - $started_at ) >= $time_budget ) {
			$complete = false;
			break;
		}
	} while ( $batch_was_full );

	// Close the recording window: the release decisions are made, so anything
	// written from here is the NEXT run's concern and the ledger must not carry
	// it over and grow.
	bb_draft_reference_scan_end();
	bb_draft_release_lock( 'bb_draft_reference_scan_lock', true );

	// A finished pass restarts from the top next time (attachments freshly
	// stamped since, and any that became unreferenced, get re-examined).
	if ( $complete ) {
		delete_option( 'bb_draft_stamp_sweep_cursor' );
	}

	delete_transient( 'bb_draft_stamp_sweep_lock' );

	return array(
		'released'  => $released,
		'complete'  => $complete,
		// True when this run could not PROVE a release was safe and therefore
		// judged nothing. Callers use it to tell "no progress, and retrying now
		// will not help" apart from "no progress, because there was nothing left
		// to do".
		//
		// It used to have exactly one cause (the pending-reference ledger
		// overflowing), and callers hard-coded that cause into their messages.
		// There are now four, and two of them - another blog's sweep holding the
		// network scan lock, and this run's own scan window expiring or being
		// replaced - call for different operator advice. `reason` carries which,
		// so nobody has to infer it.
		'abstained' => $abstained,
		'reason'    => $abstain_reason,
	);
}

// The cleanup runs inline in cron requests (see bb_drafts_delete_expired
// for why the background-process classes are not used here).
add_action( 'bb_draft_cleanup', 'bb_drafts_delete_expired' );
// The orphan-stamp sweep is NOT wired to this 60-second continuation hook - its
// full-table reference scan must not re-run every minute (M3 HIGH). It runs on
// its own per-site daily hook (bb_draft_stamp_release_hook) below only.

add_action( 'bb_draft_oneshot', 'bb_drafts_oneshot_batch' );

add_action( 'bb_draft_cleanup_hook', 'bb_drafts_delete_expired' );

// The orphan-stamp sweep queries per-site attachment tables ($wpdb->posts /
// $wpdb->postmeta), so it must run on EVERY blog - unlike the usermeta expiry
// above, which is network-global and correctly root-only. It therefore rides
// its OWN per-site daily hook (scheduled with no root guard, exactly like
// bp_media_delete_orphaned_attachments_hook), NOT bb_draft_cleanup_hook. Wiring
// it to the root-only hook meant every subsite's stamped orphans were never
// released, on any network, forever (D2).
add_action( 'bb_draft_stamp_release_hook', 'bb_drafts_release_orphaned_draft_stamps' );

/**
 * Schedule the daily draft cleanup events.
 *
 * Scheduled directly (the polls add-on precedent): bp_core_schedule_cron()
 * queues its wp_schedule_event() on the same bp_init priority that is
 * already running, which can silently skip scheduling.
 *
 * Two events with DIFFERENT multisite scopes (D2):
 *
 * - `bb_draft_stamp_release_hook` runs on EVERY blog. The orphan-stamp sweep it
 *   drives queries per-site attachment tables ($wpdb->posts / $wpdb->postmeta),
 *   which differ per blog, so a root-only event would leave every subsite's
 *   stamped orphans permanently uncollectable. Scheduled with no root guard,
 *   exactly like bp_media_delete_orphaned_attachments_hook and its document /
 *   video siblings.
 * - `bb_draft_cleanup_hook` runs on the root blog only. Its usermeta expiry
 *   sweep reads network-global usermeta, so one root-blog run covers every user
 *   network-wide; per-subsite events would duplicate the same sweep.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_drafts_schedule_cleanup() {
	// Per-site: the orphan-stamp release sweep touches per-blog attachment tables.
	if ( ! wp_next_scheduled( 'bb_draft_stamp_release_hook' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'bb_schedule_24hours', 'bb_draft_stamp_release_hook' );
	}

	// Root-only: the usermeta expiry sweep is network-global.
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
	 * Run or inspect the draft cleanup from the command line.
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
				$oneshot_done = (int) get_site_option( 'bb_draft_oneshot_done', 0 );
				$total_rows   = 0;
				$oversized    = 0;
				$last_id      = 0;

				do {
					$batch   = bb_draft_get_rows_batch( $last_id, 500, false );
					$last_id = $batch['last_id'];

					// Reporting a partial count as if it were the total is its own
					// small harm: an operator sizing the migration would read
					// "Draft rows: 0" off a failed query (BLOCKER-2).
					if ( ! empty( $batch['failed'] ) || ! empty( $batch['declined'] ) ) {
						WP_CLI::warning(
							! empty( $batch['declined'] )
								? 'Draft row scan declined: a bp_get_user_meta_key filter this code cannot invert is active, so stored keys cannot be matched to drafts. The counts below are PARTIAL.'
								: 'Draft row scan could not complete: a query failed. The counts below are PARTIAL.'
						);

						break;
					}

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

				// Another sweep holds the lock - do not spin. Same handling the
				// expiry drain below uses.
				if ( ! empty( $oneshot['locked'] ) ) {
					WP_CLI::warning( 'Healing pass skipped: another sweep holds the lock. Re-run once it finishes.' );
					break;
				}

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

			$stamp_abstentions     = 0;
			$stamp_max_abstentions = 3;

			do {
				$stamp_pass = bb_drafts_release_orphaned_draft_stamps( 0 );

				if ( ! empty( $stamp_pass['locked'] ) ) {
					WP_CLI::warning( 'Orphan-stamp sweep skipped: another sweep holds the lock. Re-run once it finishes.' );
					break;
				}

				WP_CLI::log( 'Orphaned draft stamps released: ' . $stamp_pass['released'] . '.' );

				// Three outcomes, and they need different handling.
				//
				// A pass that RELEASED something made real progress, so keep
				// going; the number of stamps is finite, which terminates.
				//
				// A pass that ABSTAINED could not prove a release safe. That is
				// transient, so retry a bounded number of times rather than
				// giving up: on a busy community there is no "quieter moment" to
				// wait for, and an operator who runs this command needs a way to
				// actually drain the stamps. This is the escape hatch; an earlier
				// revision broke out immediately here and left no way to make
				// progress at all.
				//
				// Report the actual cause. This message used to state the ledger
				// cap as the only possibility and tell the operator to raise it;
				// there are now four causes, and for two of them that advice is
				// simply wrong - another blog's sweep holding the network scan
				// lock needs no action at all, and a lost scan window is a
				// duration problem, not a capacity one.
				//
				// Anything else incomplete is budget/cursor work, and the loop
				// continues as before.
				if ( ! empty( $stamp_pass['abstained'] ) ) {
					++$stamp_abstentions;

					$abstain_reason = isset( $stamp_pass['reason'] ) ? $stamp_pass['reason'] : '';

					switch ( $abstain_reason ) {
						case 'scan_locked':
							$abstain_detail = 'another site on this network is already running the reference scan';
							$abstain_remedy = 'Nothing to do - re-run once it finishes.';
							break;
						case 'scan_window_lost':
							$abstain_detail = 'the reference-recording window closed before this run finished, so concurrent draft saves may not have been recorded';
							$abstain_remedy = 'Re-run; if it persists, the scan is outlasting its window and the draft table likely needs to be drained in smaller pieces.';
							break;
						case 'scan_incomplete':
							$abstain_detail = 'the reference scan could not be completed, so the referenced set was not authoritative';
							$abstain_remedy = 'Re-run; check the error log for a failed database query.';
							break;
						case 'ledger_overflow':
						default:
							$abstain_detail = 'more draft references arrived during the scan than the ledger could hold';
							$abstain_remedy = 'Re-run later, or raise the cap with the bb_draft_pending_reference_cap filter if this persists.';
							break;
					}

					if ( $stamp_abstentions >= $stamp_max_abstentions ) {
						WP_CLI::warning(
							sprintf(
								/* translators: 1: number of abstentions, 2: cause, 3: suggested remedy. */
								'Orphan-stamp sweep abstained %1$d times: %2$s, so no release could be proven safe. Nothing was released. %3$s',
								$stamp_abstentions,
								$abstain_detail,
								$abstain_remedy
							)
						);

						break;
					}

					WP_CLI::log(
						sprintf(
							/* translators: 1: cause, 2: attempt number, 3: maximum attempts. */
							'Orphan-stamp sweep abstained (%1$s); retrying (%2$d/%3$d).',
							$abstain_detail,
							$stamp_abstentions,
							$stamp_max_abstentions
						)
					);

					continue;
				}

				if ( empty( $stamp_pass['complete'] ) && 0 === (int) $stamp_pass['released'] ) {
					WP_CLI::warning( 'Orphan-stamp sweep made no progress and did not complete; stopping to avoid an unbounded loop. Re-run to continue.' );

					break;
				}
			} while ( empty( $stamp_pass['complete'] ) );

				WP_CLI::success( 'Draft cleanup complete.' );
		}
	);
}
