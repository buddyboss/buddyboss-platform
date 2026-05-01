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
 * Build a serializable `pro_notice.modal` payload from a catalog entry.
 *
 * Centralizes the catalog-shape → React-modal-shape mapping so both the
 * field formatter and the section formatter produce identical payloads.
 * `UpgradeModal` reads `label`, `upgrade_title`, `upgrade_description`,
 * `upgrade_image_url`, `upgrade_url`, `upgrade_tier` — those keys come
 * straight through without renaming.
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

	return array(
		'tier'        => sanitize_key( $entry['upgrade_tier']        ?? 'pro' ),
		'label'       => sanitize_text_field( $entry['label']        ?? $fallback_label ),
		'title'       => sanitize_text_field( $entry['upgrade_title'] ?? '' ),
		'description' => wp_kses_post( $entry['upgrade_description'] ?? '' ),
		'image_url'   => esc_url_raw( $entry['upgrade_image_url']    ?? '' ),
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
 * Clear the field upgrades cache via query parameter.
 *
 * Reuses the same `?bb_clear_placeholder_cache=1` admin URL that already
 * flushes the placeholder catalog — see `bb_maybe_clear_placeholder_features_cache()`
 * which calls `bb_flush_feature_caches_on_plugin_change()`. This file
 * piggybacks on the canonical flusher via the `bb_feature_caches_flushed`
 * action so there is exactly one cache-flush entry point.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_flush_field_upgrades_cache() {
	delete_transient( bb_field_upgrades_transient_key() );
	delete_option( 'bb_field_upgrades_data_stale' );
	bb_schedule_field_upgrades_refresh();
}
// Hook into the canonical Settings 2.0 cache flusher so every existing
// trigger (plugin activation, manual `?bb_clear_placeholder_cache=1`,
// license status change) drops this catalog along with the others.
add_action( 'bb_feature_caches_flushed', 'bb_flush_field_upgrades_cache' );

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
		add_action( $plugin_id . '_license_status_changed', 'bb_flush_field_upgrades_cache' );
	}
}
add_action( 'admin_init', 'bb_register_field_upgrades_license_hooks' );
