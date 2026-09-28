<?php
/**
 * Appearance Feature — Sanitizers & Shape Normalizers.
 *
 * Stateless helpers shared by three entry points:
 *   1. Settings 2.0 admin save (`admin/callbacks.php :: bb_appearance_on_settings_saved`).
 *   2. Onboarding wizard save (`BB_ReadyLaunch_Onboarding::sanitize_final_settings`).
 *   3. One-shot version-update migration (`bb_rl_migrate_settings` in `bp-core-update.php`).
 *
 * This file is loaded unconditionally from `bb-feature-config.php` — NOT inside
 * the admin gate that wraps `admin/settings.php`. The migration runs on
 * `bp_admin_init` today which satisfies `is_admin()`, but any future code path
 * (WP-CLI, REST upgrader, etc.) that needs to normalize Appearance shapes must
 * also be able to call these helpers. Keeping them outside the admin gate
 * prevents a silent "helpers-not-defined" no-op.
 *
 * Each function is a standard WordPress field sanitize callback — accepts a
 * single value and returns the sanitized value. No side effects, no DB writes,
 * no admin-context assumptions.
 *
 * @package BuddyBoss\Features\Community\Appearance
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sanitize the Site Layout dropdown value to a boolean.
 *
 * The `bb_rl_enabled` option is a boolean end-to-end so `bb_is_readylaunch_enabled()`
 * (which casts via `(bool)`) keeps working. String values ("1"/"0"/"true") all coerce
 * correctly via the (int) round-trip.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed $value Submitted value.
 * @return bool Sanitized boolean.
 */
function bb_appearance_sanitize_layout( $value ) {
	if ( is_bool( $value ) ) {
		return $value;
	}
	if ( is_string( $value ) ) {
		$value = strtolower( trim( $value ) );
		if ( in_array( $value, array( 'readylaunch', 'true', '1', 'yes', 'on' ), true ) ) {
			return true;
		}
		if ( in_array( $value, array( 'wordpress_theme', 'false', '0', 'no', 'off', '' ), true ) ) {
			return false;
		}
	}
	return (bool) (int) $value;
}

/**
 * Sanitize the ReadyLaunch theme mode (light / dark / choice).
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed $value Submitted value.
 * @return string Sanitized mode ('light' on invalid input).
 */
function bb_appearance_sanitize_theme_mode( $value ) {
	$allowed = array( 'light', 'dark', 'choice' );
	return in_array( $value, $allowed, true ) ? $value : 'light';
}

/**
 * Sanitize a ReadyLaunch primary color with an optional fallback default.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed  $value         Submitted value.
 * @param string $default_value Default hex color if sanitization fails.
 * @return string Sanitized hex color.
 */
function bb_appearance_sanitize_color( $value, $default_value = '#3E34FF' ) {
	$sanitized = sanitize_hex_color( $value );
	return $sanitized ? $sanitized : $default_value;
}

/**
 * Sanitize a ReadyLaunch logo media field.
 *
 * Accepts either the current object shape (id/url/alt/title) or the legacy
 * integer ID. Returns null when the input is empty/invalid.
 *
 * Security posture (important):
 *   - If an attachment ID is supplied AND it resolves via
 *     `wp_get_attachment_url()` → persist the canonical URL. Trusts WP's
 *     resolver over whatever the client submitted — guards against a payload
 *     pairing a legitimate ID with an off-site URL.
 *   - If an ID is supplied but `wp_get_attachment_url()` returns false (i.e.
 *     the attachment doesn't exist on this site) → return null. Treating the
 *     submitted URL as authoritative here would let a nonce-bearing admin
 *     seed the site with arbitrary off-site `<img src>` content. Silent wipe
 *     is the safer failure mode; the admin re-picks media.
 *   - If NO ID is supplied (legacy "bare URL" call sites) → accept the URL
 *     ONLY when it's same-host as `home_url()`. External URLs here are
 *     likely crafted payloads and get rejected.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed $value Submitted value.
 * @return array|int|null Sanitized media object, legacy integer ID, or null.
 */
function bb_appearance_sanitize_media( $value ) {
	if ( is_array( $value ) && isset( $value['id'] ) ) {
		$id = absint( $value['id'] );

		if ( $id > 0 ) {
			$canonical_url = wp_get_attachment_url( $id );
			if ( ! $canonical_url ) {
				// ID supplied but doesn't resolve — most likely a crafted
				// payload OR an attachment deleted out of band. Either way,
				// trusting the submitted URL opens an off-site `<img src>`
				// persistence vector.
				return null;
			}
			return array(
				'id'    => $id,
				'url'   => $canonical_url,
				'alt'   => isset( $value['alt'] ) ? sanitize_text_field( $value['alt'] ) : '',
				'title' => isset( $value['title'] ) ? sanitize_text_field( $value['title'] ) : '',
			);
		}

		// No ID — legacy "bare URL" payload. Accept only same-host URLs.
		$submitted_url = isset( $value['url'] ) ? esc_url_raw( $value['url'] ) : '';
		if ( '' === $submitted_url ) {
			return null;
		}
		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
		$url_host  = wp_parse_url( $submitted_url, PHP_URL_HOST );
		if ( ! $site_host || ! $url_host || strcasecmp( $site_host, $url_host ) !== 0 ) {
			return null;
		}
		return array(
			'id'    => 0,
			'url'   => $submitted_url,
			'alt'   => isset( $value['alt'] ) ? sanitize_text_field( $value['alt'] ) : '',
			'title' => isset( $value['title'] ) ? sanitize_text_field( $value['title'] ) : '',
		);
	}

	// Legacy integer-ID shape. Validate against `wp_get_attachment_url()` to
	// match the array-branch behavior — an ID that doesn't resolve is either
	// crafted or orphaned, and persisting it silently would let a later
	// render emit a broken `<img>` without a clear failure path.
	if ( is_numeric( $value ) ) {
		$legacy_id = absint( $value );
		if ( $legacy_id > 0 && wp_get_attachment_url( $legacy_id ) ) {
			return $legacy_id;
		}
		return null;
	}
	return null;
}

/**
 * Sanitize the Template Pages selector into the legacy `bb_rl_enabled_pages` shape.
 *
 * Accepts either:
 * - Sequential list of page keys (onboarding form shape): `array( 'registration', 'courses' )`
 * - Associative map (Settings 2.0 checkbox_group shape): `array( 'registration' => true, 'courses' => false )`
 *
 * Returns the canonical object-map shape consumed by ReadyLaunch templates
 * (e.g. the sidebar renderer at `right-sidebar.php` and the page-enabled
 * check in `BB_Readylaunch::bb_rl_is_page_enabled_for_integration()`).
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed $value Submitted value.
 * @return array{registration: bool, courses: bool, blog: bool} Sanitized map.
 */
function bb_appearance_sanitize_enabled_pages( $value ) {
	$map = array(
		'registration' => false,
		'courses'      => false,
		'blog'         => false,
	);

	if ( ! is_array( $value ) ) {
		return $map;
	}

	// Sequential list of selected keys.
	if ( array_values( $value ) === $value ) {
		foreach ( $map as $page_key => $unused ) {
			$map[ $page_key ] = in_array( $page_key, $value, true );
		}
		return $map;
	}

	// Associative map — respect each key's truthiness.
	foreach ( $map as $page_key => $unused ) {
		$map[ $page_key ] = ! empty( $value[ $page_key ] );
	}
	return $map;
}

/**
 * Sanitize a sidebar toggle map (activity / member profile / groups).
 *
 * Accepts either:
 * - Sequential list of widget items with `{id, enabled}` (onboarding shape)
 * - Associative map of `widget_id => bool` (Settings 2.0 + legacy admin + templates)
 *
 * Returns the canonical object-map shape consumed by ReadyLaunch's
 * `right-sidebar.php` template (keyed by widget slug, value = bool).
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed $value Submitted value.
 * @return array<string, bool> Sanitized map.
 */
function bb_appearance_sanitize_sidebar_map( $value ) {
	$map = array();

	if ( ! is_array( $value ) ) {
		return $map;
	}

	// Sequential list (onboarding `draggable` field shape).
	if ( array_values( $value ) === $value && isset( $value[0] ) && is_array( $value[0] ) ) {
		foreach ( $value as $item ) {
			if ( ! is_array( $item ) || empty( $item['id'] ) ) {
				continue;
			}
			$map[ sanitize_key( $item['id'] ) ] = ! empty( $item['enabled'] );
		}
		return $map;
	}

	// Associative map.
	foreach ( $value as $widget_id => $enabled ) {
		$map[ sanitize_key( $widget_id ) ] = ! empty( $enabled );
	}
	return $map;
}

/**
 * Sanitize the `bb_rl_side_menu` field.
 *
 * Accepts either:
 * - Sequential list of menu items with `{id, enabled, order, icon}` (onboarding shape)
 * - Associative map of `id => {enabled, order, icon}` (Settings 2.0 shape)
 *
 * Returns the canonical associative map consumed by ReadyLaunch templates.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed $value Submitted value.
 * @return array<string, array{enabled: bool, order: int, icon: string}> Sanitized map.
 */
function bb_appearance_sanitize_side_menu( $value ) {
	$map = array();

	if ( ! is_array( $value ) ) {
		return $map;
	}

	// Sequential list shape from the onboarding draggable control.
	if ( array_values( $value ) === $value && isset( $value[0] ) && is_array( $value[0] ) ) {
		foreach ( $value as $item ) {
			if ( ! is_array( $item ) || empty( $item['id'] ) ) {
				continue;
			}
			$map[ sanitize_key( $item['id'] ) ] = array(
				'enabled' => isset( $item['enabled'] ) ? (bool) $item['enabled'] : true,
				'order'   => isset( $item['order'] ) ? (int) $item['order'] : 0,
				'icon'    => isset( $item['icon'] ) ? sanitize_text_field( $item['icon'] ) : '',
			);
		}
		return $map;
	}

	// Associative-map shape.
	foreach ( $value as $id => $config ) {
		if ( ! is_array( $config ) ) {
			continue;
		}
		$map[ sanitize_key( $id ) ] = array(
			'enabled' => isset( $config['enabled'] ) ? (bool) $config['enabled'] : true,
			'order'   => isset( $config['order'] ) ? (int) $config['order'] : 0,
			'icon'    => isset( $config['icon'] ) ? sanitize_text_field( $config['icon'] ) : '',
		);
	}
	return $map;
}

/**
 * Sanitize the `bb_rl_custom_links` field.
 *
 * Always returns a sequential list of link objects with `id`, `title`, `url`, and
 * `isEditing => false` (matches legacy format).
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed $value Submitted value.
 * @return array<int, array{id: string, title: string, url: string, isEditing: false}> Sanitized links.
 */
function bb_appearance_sanitize_custom_links( $value ) {
	$links = array();

	if ( ! is_array( $value ) ) {
		return $links;
	}

	foreach ( $value as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$links[] = array(
			'id'        => isset( $item['id'] ) ? sanitize_text_field( $item['id'] ) : '',
			'title'     => isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '',
			'url'       => isset( $item['url'] ) ? esc_url_raw( $item['url'] ) : '',
			'isEditing' => false,
		);
	}

	return $links;
}

/**
 * Convert a sequential list of enabled keys (legacy onboarding shape) to an
 * associative `{key: 1}` map (Settings 2.0 shape). Pass-through for values
 * that are already map-shaped.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed $value Value to normalize.
 * @return array|mixed Associative map, or the original value if not an array.
 */
function bb_appearance_normalize_list_to_map( $value ) {
	if ( ! is_array( $value ) || empty( $value ) ) {
		return is_array( $value ) ? $value : array();
	}

	// Already associative — pass through unchanged.
	if ( array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) {
		return $value;
	}

	// Sequential list of enabled keys — flatten to { key => 1 } map.
	$map = array();
	foreach ( $value as $enabled_key ) {
		if ( is_string( $enabled_key ) || is_numeric( $enabled_key ) ) {
			$map[ (string) $enabled_key ] = 1;
		}
	}
	return $map;
}

/**
 * Normalize `bb_rl_side_menu` to the canonical map-of-items shape.
 *
 * Legacy onboarding stored this as a sequential list of items —
 * `[ { id: 'activity_feed', enabled: true, order: 0, icon: 'pulse' }, ... ]`.
 * Settings 2.0's `SortableToggleList` component expects a map keyed by id —
 * `{ activity_feed: { enabled, order, icon }, ... }`. Pass-through when the
 * value is already map-shaped.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed $value Stored value (either shape).
 * @return array Map-of-items shape.
 */
function bb_appearance_normalize_side_menu_shape( $value ) {
	if ( ! is_array( $value ) || empty( $value ) ) {
		return is_array( $value ) ? $value : array();
	}

	// Sequential list of item objects — flip to a map keyed by `id`. Detect a
	// sequential list by the numeric keys alone; don't require the *first*
	// element to have an `id` (legacy data might start with a malformed row,
	// in which case we'd otherwise fall through to pass-through and leak the
	// sequential shape to the frontend).
	if ( array_keys( $value ) === range( 0, count( $value ) - 1 ) ) {
		$map = array();
		foreach ( $value as $index => $item ) {
			if ( ! is_array( $item ) || empty( $item['id'] ) ) {
				continue;
			}
			$map[ (string) $item['id'] ] = array(
				'enabled' => isset( $item['enabled'] ) ? (bool) $item['enabled'] : true,
				'order'   => isset( $item['order'] ) ? (int) $item['order'] : (int) $index,
				'icon'    => isset( $item['icon'] ) ? (string) $item['icon'] : '',
			);
		}
		return $map;
	}

	// Already map-shaped — pass through unchanged.
	return $value;
}
