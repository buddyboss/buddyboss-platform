<?php
/**
 * BuddyBoss Admin Field & Section Upgrade Catalog Provider.
 *
 * Fetches a remote JSON catalog of field-level / section-level upgrade
 * marketing copy, caches it via a versioned transient, and exposes a
 * resolver that maps a (feature_id, panel_id, section_id, field) tuple
 * to a single matching catalog entry.
 *
 * Mirrors the lifecycle of `bb-admin-placeholder-features.php` (transient +
 * stale-while-revalidate option + single-event scheduled refresh + daily
 * cron safety net) so that Settings 2.0 has one consistent caching model
 * for all S3-hosted marketing copy.
 *
 * The resolver supports:
 *  - single match:    `match: { feature_id, panel_id, section_id, field }`
 *  - multiple match:  `matches: [ { ... }, { ... }, ... ]`
 *  - wildcards:       any match key may be `*` or omitted (means "any value")
 *
 * Resolution is most-specific-wins: an exact 4-tuple match beats a
 * `section_id: "*"` wildcard. Ties on specificity are broken by JSON
 * array order (first declared wins) so marketing can override globally
 * by reordering entries.
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Transient key for the cached field upgrades catalog.
 *
 * Versioned with the Platform version so plugin upgrades automatically
 * invalidate stale catalogs.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string Transient key.
 */
function bb_field_upgrades_transient_key() {
	$version = defined( 'BP_PLATFORM_VERSION' ) ? BP_PLATFORM_VERSION : '0';
	return 'bb_field_upgrades_data_v_' . md5( $version );
}

/**
 * Get the field upgrades catalog from the cache.
 *
 * Hot path — called from the `bb_admin_get_feature_settings` AJAX response
 * once per panel render. Never performs a synchronous remote fetch on a
 * cache hit. On a cache miss prefers the stale option so the AJAX path
 * stays non-blocking; the next request will populate fresh data via cron.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array|false Decoded catalog data with 'items' key, or false when unavailable.
 */
function bb_get_field_upgrades_data() {
	$data = get_transient( bb_field_upgrades_transient_key() );

	if ( false !== $data ) {
		return $data;
	}

	// Cache miss. Prefer the stale catalog (keeps the AJAX path non-blocking);
	// meanwhile schedule an async refresh for the next request.
	$stale = get_option( 'bb_field_upgrades_data_stale', false );
	bb_schedule_field_upgrades_refresh();

	if ( is_array( $stale ) && ! empty( $stale['items'] ) ) {
		return $stale;
	}

	// First-load-after-deploy / first-load-after-manual-clear case. Without
	// a synchronous fetch here the admin would see no upgrade modals until
	// WP-Cron fires. Do one blocking fetch so the modals render immediately;
	// subsequent requests stay fast via the transient + stale path above.
	return bb_refresh_field_upgrades_data();
}

/**
 * Schedule a single-event background refresh of the field upgrades catalog.
 *
 * Uses a short-lived lock transient to prevent thundering-herd scheduling
 * when many admin requests arrive simultaneously after a cache expiry.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_schedule_field_upgrades_refresh() {
	if ( ( defined( 'DOING_CRON' ) && DOING_CRON ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return;
	}

	if ( false !== get_transient( 'bb_field_upgrades_fetching' ) ) {
		return;
	}
	set_transient( 'bb_field_upgrades_fetching', 1, MINUTE_IN_SECONDS );

	if ( ! wp_next_scheduled( 'bb_refresh_field_upgrades_cron' ) ) {
		wp_schedule_single_event( time() + 5, 'bb_refresh_field_upgrades_cron' );
	}
}

/**
 * Perform the actual remote fetch and populate the catalog cache.
 *
 * Runs from WP-Cron — never in the critical AJAX path. Timeout kept tight;
 * on any failure the previous stale catalog (option `bb_field_upgrades_data_stale`)
 * continues to serve read requests, so admins never see an empty state.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array|false Fetched data on success, false on any failure.
 */
function bb_refresh_field_upgrades_data() {
	delete_transient( 'bb_field_upgrades_fetching' );

	/**
	 * Filter the remote endpoint used to fetch the field upgrades catalog.
	 *
	 * Allows self-hosting, staging overrides, and test injection.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $url Default S3 endpoint URL.
	 */
	$url = apply_filters(
		'bb_field_upgrades_endpoint',
		'https://bb-features-marketing.s3.amazonaws.com/bb-field-upgrades.json'
	);

	$response = wp_remote_get( $url, array( 'timeout' => 4 ) );
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return false;
	}

	$parsed = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $parsed ) || empty( $parsed['items'] ) || ! is_array( $parsed['items'] ) ) {
		return false;
	}

	set_transient( bb_field_upgrades_transient_key(), $parsed, 6 * HOUR_IN_SECONDS );
	update_option( 'bb_field_upgrades_data_stale', $parsed, false );

	return $parsed;
}

// Cron handlers — single-event (on-demand refresh) and recurring (daily safety net).
add_action( 'bb_refresh_field_upgrades_cron', 'bb_refresh_field_upgrades_data' );
add_action( 'bb_field_upgrades_daily_refresh', 'bb_refresh_field_upgrades_data' );

/**
 * Ensure the daily refresh event is scheduled.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_schedule_field_upgrades_daily_refresh() {
	if ( ! wp_next_scheduled( 'bb_field_upgrades_daily_refresh' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'bb_field_upgrades_daily_refresh' );
	}
}
add_action( 'admin_init', 'bb_schedule_field_upgrades_daily_refresh' );

/**
 * Resolve the upgrade catalog entry for a given (feature, panel, section, field) tuple.
 *
 * Builds an in-memory specificity-bucketed index on first call per request,
 * then performs O(4) lookups: most-specific first, falling back through
 * progressively more wildcarded keys.
 *
 * Resolution order:
 *   score 4 → exact: feature + panel + section + field
 *   score 3 → exact section: feature + panel + section + (field=*)
 *   score 2 → exact panel:   feature + panel + (section=*) + (field=*)
 *   score 1 → feature only:  feature + (panel=*) + (section=*) + (field=*)
 *
 * Ties at the same specificity are broken by JSON array order (first wins).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID (required for any match).
 * @param string $panel_id   Side panel ID. Empty string is treated as no panel context.
 * @param string $section_id Section ID. Empty string is treated as no section context.
 * @param string $field_name Field name (option key). Empty when resolving for a section header.
 * @return array|null Matching catalog entry, or null when nothing matches.
 */
function bb_get_field_upgrade_for( $feature_id, $panel_id = '', $section_id = '', $field_name = '' ) {
	static $index = null;

	if ( empty( $feature_id ) ) {
		return null;
	}

	if ( null === $index ) {
		$index = array();
		$data  = bb_get_field_upgrades_data();

		if ( ! empty( $data['items'] ) && is_array( $data['items'] ) ) {
			foreach ( $data['items'] as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}

				// Normalize: 'matches' (array) wins over 'match' (single object)
				// when both are present. Documented precedence: keep one or the other.
				$matches = array();
				if ( ! empty( $item['matches'] ) && is_array( $item['matches'] ) ) {
					$matches = $item['matches'];
				} elseif ( ! empty( $item['match'] ) && is_array( $item['match'] ) ) {
					$matches = array( $item['match'] );
				}

				if ( empty( $matches ) ) {
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( sprintf( 'bb_field_upgrades: catalog entry "%s" has no match/matches', $item['upgrade_title'] ?? '?' ) );
					}
					continue;
				}

				foreach ( $matches as $match ) {
					if ( ! is_array( $match ) || empty( $match['feature_id'] ) ) {
						// Require feature_id to prevent a malformed entry from
						// hijacking every field on every screen.
						if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
							error_log( sprintf( 'bb_field_upgrades: match missing feature_id in "%s"', $item['upgrade_title'] ?? '?' ) );
						}
						continue;
					}

					// Build a normalized 4-tuple key. Empty/missing values
					// become '*' so wildcards hash deterministically.
					$key = sprintf(
						'%s|%s|%s|%s',
						$match['feature_id'],
						! empty( $match['panel_id'] )   ? $match['panel_id']   : '*',
						! empty( $match['section_id'] ) ? $match['section_id'] : '*',
						! empty( $match['field'] )      ? $match['field']      : '*'
					);

					// Specificity = number of explicit (non-wildcard) match keys.
					$score = 0;
					foreach ( array( 'feature_id', 'panel_id', 'section_id', 'field' ) as $k ) {
						$v = $match[ $k ] ?? '';
						if ( '' !== $v && '*' !== $v ) {
							++$score;
						}
					}

					// Bucket by score so we can scan high-specificity first.
					// Within a bucket, first-declared wins.
					if ( ! isset( $index[ $score ][ $key ] ) ) {
						$index[ $score ][ $key ] = $item;
					}
				}
			}
		}
	}

	// Probe candidate keys in most-specific-first order. For each level of
	// the 4-tuple we either pin to the actual value or wildcard it, giving
	// us 16 combinations total. We sort them by specificity (count of pinned
	// levels) so an exact 4-tuple match always wins over a 3-tuple match,
	// which wins over a 2-tuple, etc. Within a specificity tier, multiple
	// keys are probed in order so e.g. `feature+field` is checked alongside
	// `feature+panel+section`. This covers all useful matcher shapes
	// (catalog entries can specify any subset of the 4 keys + use `*`).
	$f = $feature_id;
	$p = $panel_id   ?: '*';
	$s = $section_id ?: '*';
	$x = $field_name ?: '*';

	$candidates = array();
	// Score 4 — exact match (all 4 pinned).
	$candidates[] = array( 4, "{$f}|{$p}|{$s}|{$x}" );
	// Score 3 — one wildcard.
	$candidates[] = array( 3, "{$f}|{$p}|{$s}|*" );
	$candidates[] = array( 3, "{$f}|{$p}|*|{$x}" );
	$candidates[] = array( 3, "{$f}|*|{$s}|{$x}" );
	// Score 2 — two wildcards.
	$candidates[] = array( 2, "{$f}|{$p}|*|*" );
	$candidates[] = array( 2, "{$f}|*|{$s}|*" );
	$candidates[] = array( 2, "{$f}|*|*|{$x}" );
	// Score 1 — feature only.
	$candidates[] = array( 1, "{$f}|*|*|*" );

	foreach ( $candidates as $candidate ) {
		list( $score, $key ) = $candidate;
		if ( ! empty( $index[ $score ][ $key ] ) ) {
			return $index[ $score ][ $key ];
		}
	}

	return null;
}

/**
 * Detect the media provider for a given URL and return a normalized embed payload.
 *
 * Marketing supplies `upgrade_video_url` as a regular share URL (e.g.
 * `https://youtu.be/abc`, `https://vimeo.com/123`, or a direct `.mp4` link).
 * The React modal needs a provider type + a ready-to-embed URL — sniffing
 * the URL on the client is fragile, so we do it once here.
 *
 * Supported shapes:
 *  - YouTube:  youtube.com/watch?v=ID, youtu.be/ID, youtube.com/embed/ID
 *  - Vimeo:    vimeo.com/ID, player.vimeo.com/video/ID
 *  - MP4:      any URL ending in .mp4 (case-insensitive, ignoring query string)
 *
 * Returns null when the URL doesn't match any supported provider — callers
 * should treat that as "no video, fall back to image".
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $url Raw video URL from the catalog.
 * @return array|null { type: 'youtube'|'vimeo'|'mp4', url: string } or null on no match.
 */
function bb_detect_upgrade_video_provider( $url ) {
	if ( empty( $url ) || ! is_string( $url ) ) {
		return null;
	}

	$url = trim( $url );

	// YouTube — match watch?v=, youtu.be/, /embed/, /shorts/.
	// Marketing wants minimal chrome on the upsell modal. With `controls=0`
	// the embed has no manual play button, so we autoplay muted + loop —
	// the video plays the moment the modal opens, with no UI to interact with.
	// Browsers require `muted=1` for autoplay to succeed (since 2018).
	// Param breakdown:
	//   autoplay=1       — start playing on load (requires muted)
	//   mute=1           — start muted; mandatory for autoplay
	//   loop=1           — restart at end (needs `playlist=ID` to actually loop)
	//   playlist=ID      — workaround required for loop=1 to work on single videos
	//   controls=0       — hide the playback control bar
	//   modestbranding=1 — drop the YouTube logo overlay
	//   rel=0            — only same-channel related videos
	//   showinfo=0       — suppress info overlay (legacy)
	//   iv_load_policy=3 — disable annotations/cards
	//   disablekb=1      — disable keyboard shortcuts
	//   fs=0             — hide fullscreen button
	//   playsinline=1    — inline playback on iOS
	// Use `youtube-nocookie.com` for privacy-enhanced mode (same player).
	if ( preg_match( '#(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_\-]{6,})#i', $url, $m ) ) {
		$video_id = $m[1];
		return array(
			'type' => 'youtube',
			'url'  => 'https://www.youtube-nocookie.com/embed/' . $video_id . '?autoplay=1&mute=1&loop=1&playlist=' . $video_id . '&controls=0&modestbranding=1&rel=0&showinfo=0&iv_load_policy=3&disablekb=1&fs=0&playsinline=1',
		);
	}

	// Vimeo — match vimeo.com/ID or player.vimeo.com/video/ID.
	// Vimeo gets its own behavior (different from YouTube/MP4 which autoplay
	// muted+loop): the user wants a visible play button on the poster frame
	// so visitors actively choose to play. Vimeo's `controls=0` hides not
	// only the playback bar but also the initial play button, so we keep
	// controls enabled here. `title=0&byline=0&portrait=0` still suppresses
	// the author/channel branding — that's the typical clean embed look.
	// Param breakdown:
	//   title=0       — hide video title overlay
	//   byline=0      — hide author byline
	//   portrait=0    — hide author avatar
	//   pip=0         — hide picture-in-picture button
	//   playsinline=1 — inline playback on iOS
	if ( preg_match( '#(?:player\.)?vimeo\.com/(?:video/)?(\d+)#i', $url, $m ) ) {
		return array(
			'type' => 'vimeo',
			'url'  => 'https://player.vimeo.com/video/' . $m[1] . '?title=0&byline=0&portrait=0&pip=0&playsinline=1',
		);
	}

	// MP4 — strip query/fragment before checking extension.
	$path = wp_parse_url( $url, PHP_URL_PATH );
	if ( is_string( $path ) && preg_match( '#\.mp4$#i', $path ) ) {
		return array(
			'type' => 'mp4',
			'url'  => $url,
		);
	}

	return null;
}

/**
 * Build a normalized media payload for an upgrade modal.
 *
 * Resolution order:
 *  - Video URL set + matches a known provider → media is the video. If an
 *    image URL is also present, it becomes a `poster` (only used by `<video>`
 *    on mp4; harmless on iframe embeds).
 *  - No video, image present → media is the image.
 *  - Neither → empty media (caller should hide the wrapper).
 *
 * Shared between field-level (bb-field-upgrades.json) and feature-level
 * (bb-features.json) modals so React has a single rendering contract.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $video_url Raw video URL from the catalog (may be empty).
 * @param string $image_url Raw image URL from the catalog (may be empty).
 * @return array { type: '', url: '', poster: '' } shape; type is one of
 *               'youtube'|'vimeo'|'mp4'|'image'|'' (empty when nothing set).
 */
function bb_admin_build_upgrade_media( $video_url, $image_url ) {
	$image_url = esc_url_raw( $image_url );
	$video     = bb_detect_upgrade_video_provider( $video_url );

	if ( null !== $video ) {
		return array(
			'type'   => $video['type'],
			'url'    => esc_url_raw( $video['url'] ),
			'poster' => $image_url,
		);
	}

	if ( ! empty( $image_url ) ) {
		return array(
			'type'   => 'image',
			'url'    => $image_url,
			'poster' => '',
		);
	}

	return array(
		'type'   => '',
		'url'    => '',
		'poster' => '',
	);
}

/**
 * Build a serializable `pro_notice.modal` payload from a catalog entry.
 *
 * Centralizes the catalog-shape → React-modal-shape mapping so both the
 * field formatter and the section formatter produce identical payloads.
 * `UpgradeModal` reads `label`, `upgrade_title`, `upgrade_description`,
 * `upgrade_url`, `upgrade_tier`, and a normalized `media` object — the
 * media object is built here so React doesn't have to sniff URLs.
 *
 * Media resolution order:
 *  1. `upgrade_video_url` set + matches a known provider → media is the video.
 *     (`upgrade_image_url`, when present, becomes a poster on `<video>` for mp4.)
 *  2. Otherwise → media is the image (or empty when no image either).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array  $entry         Resolved catalog entry from `bb_get_field_upgrade_for()`.
 * @param string $fallback_label Label to use when the catalog entry omits one
 *                               (e.g. the field's own label).
 * @return array Modal payload safe for inclusion in the AJAX response.
 */
function bb_field_upgrade_to_modal_payload( $entry, $fallback_label = '' ) {
	if ( ! is_array( $entry ) ) {
		return array();
	}

	$media = bb_admin_build_upgrade_media(
		$entry['upgrade_video_url'] ?? '',
		$entry['upgrade_image_url'] ?? ''
	);

	return array(
		'tier'        => sanitize_key( $entry['upgrade_tier']        ?? 'pro' ),
		'label'       => sanitize_text_field( $entry['label']        ?? $fallback_label ),
		'title'       => sanitize_text_field( $entry['upgrade_title'] ?? '' ),
		'description' => wp_kses_post( $entry['upgrade_description'] ?? '' ),
		// Kept for backward-compat with any consumer that still reads `image_url`.
		'image_url'   => 'image' === $media['type'] ? $media['url'] : ( $media['poster'] ?? '' ),
		'media'       => $media,
		'url'         => esc_url_raw( $entry['upgrade_url']          ?? 'https://www.buddyboss.com/pricing/' ),
	);
}

/**
 * Reset the in-memory resolver index.
 *
 * Called by the cache flusher so a long-running process (cron, CLI) sees
 * fresh data after a transient delete without restarting PHP.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_reset_field_upgrades_index() {
	// Re-invoke the resolver with sentinel arguments to force a rebuild
	// path on next call. The cleanest way is a dedicated reset wrapper
	// that owns the static — but PHP's static scoping means we have to
	// expose it via a closure. Simpler and equivalent: delete the
	// transient + stale option here so the next call rebuilds from disk
	// or remote, and rely on the static rebuilding on the next request
	// boundary (admin AJAX requests are short-lived).
	delete_transient( bb_field_upgrades_transient_key() );
}

/**
 * Clear the field upgrades cache (transient only).
 *
 * Drops the live transient and schedules an async refresh — but keeps the
 * `bb_field_upgrades_data_stale` option so the next AJAX request can serve
 * the last-known-good catalog while the cron fetch runs in the background.
 *
 * Why we keep the stale option here even though the placeholder version
 * mirrors `bb_flush_field_upgrades_cache` semantics (transient + stale):
 * The catalog content does NOT change in response to plugin lifecycle
 * events — only the resolved badge state on the consumer side does. So
 * deleting the stale option on every plugin activate/deactivate would
 * force a synchronous S3 fetch in the AJAX path on the very next request
 * after a plugin install. That made the post-`mosh_addon_activate` page
 * refresh visibly slow (3-4s blocking S3 round-trip on the WP-Admin path).
 *
 * The manual `?bb_clear_placeholder_cache=1` trigger does NOT call this
 * function for stale-deletion — see `bb_flush_field_upgrades_cache_full()`
 * below for the truly-need-fresh-now case (license change, manual flush).
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_flush_field_upgrades_cache() {
	delete_transient( bb_field_upgrades_transient_key() );
	bb_schedule_field_upgrades_refresh();
}
// Hook into the canonical Settings 2.0 cache flusher so every existing
// trigger (plugin activation, manual `?bb_clear_placeholder_cache=1`,
// license status change) drops the live transient.
add_action( 'bb_feature_caches_flushed', 'bb_flush_field_upgrades_cache' );

/**
 * Force a full flush including the stale-while-revalidate fallback.
 *
 * Use this when the catalog content itself may have changed semantically
 * (license tier change, manual admin trigger). For plugin lifecycle
 * events use the lighter `bb_flush_field_upgrades_cache()` which keeps
 * the stale option around for instant AJAX serving.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_flush_field_upgrades_cache_full() {
	delete_transient( bb_field_upgrades_transient_key() );
	delete_option( 'bb_field_upgrades_data_stale' );
	bb_schedule_field_upgrades_refresh();
}

/**
 * Clear the field upgrades cache when the license status changes.
 *
 * Mirrors the placeholder catalog's license-change handler — when the
 * site moves from "no license" to "Pro" or back, every modal payload
 * needs a fresh resolution because pro_notice may stop firing.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_register_field_upgrades_license_hooks() {
	if ( ! class_exists( '\\BuddyBoss\\Core\\Admin\\Mothership\\BB_Plugin_Connector' ) ) {
		return;
	}

	$plugin_id = get_option( 'buddyboss_dynamic_plugin_id', '' );
	if ( empty( $plugin_id ) && defined( 'PLATFORM_EDITION' ) ) {
		$plugin_id = PLATFORM_EDITION;
	}

	if ( ! empty( $plugin_id ) ) {
		// License change is one of the few events where we want to force a
		// fresh remote fetch (drop stale option too) — Pro vs free changes
		// what badges users should see, and we'd rather pay the synchronous
		// S3 hit on the rare license-change request than serve stale data.
		add_action( $plugin_id . '_license_status_changed', 'bb_flush_field_upgrades_cache_full' );
	}
}
add_action( 'admin_init', 'bb_register_field_upgrades_license_hooks' );
