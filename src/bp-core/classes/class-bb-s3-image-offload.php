<?php
/**
 * Serve BuddyBoss Platform image assets from an external S3 bucket.
 *
 * Platform image assets (png/jpg/jpeg/gif/svg/webp/ico/bmp) and woff2 fonts are
 * uploaded to an S3 bucket that mirrors the plugin's source tree. At runtime the
 * final HTML output is buffered and every local plugin asset URL is rewritten to
 * its S3 counterpart, so the bytes are served from S3/CloudFront instead of the
 * WordPress host. woff2 fonts are referenced from CSS `@font-face`, so they are
 * rewritten primarily at build time by the Grunt CSS rewrite. This pairs with the
 * Grunt build excluding the offloaded assets from the shipped zip, keeping the
 * customer download small.
 *
 * Path mapping is anchored on {@see buddypress()->plugin_url} rather than the
 * plugin root so it works identically in both layouts:
 *
 *   - dev/trunk : .../buddyboss-platform/src/bp-core/images/foo.png
 *   - shipped   : .../buddyboss-platform/bp-core/images/foo.png
 *
 * In both cases the source-relative path is `bp-core/images/foo.png`, which is
 * the S3 object key. The bucket mirrors the *shipped* (flattened) plugin
 * layout at its root — there is no `src/` directory, since that exists only in
 * the dev/trunk checkout. An optional key prefix is available for buckets that
 * nest the tree under a sub-path.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Rewrites local Platform image URLs to an external S3 bucket.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_S3_Image_Offload {

	/**
	 * Default S3 bucket base URL (without trailing slash logic applied here).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const DEFAULT_BASE_URL = 'https://buddyboss-platform-assets.s3.us-east-1.amazonaws.com/';

	/**
	 * Default object-key prefix applied to source-relative paths.
	 *
	 * Empty by default: the bucket mirrors the shipped (flattened) plugin tree
	 * at its root, e.g. `bp-core/images/foo.png` — no `src/` directory, since
	 * that exists only in the dev checkout. Filterable for buckets that nest
	 * the tree under a sub-path (e.g. `assets/`).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const DEFAULT_KEY_PREFIX = '';

	/**
	 * Asset extensions eligible for offloading.
	 *
	 * Images plus `woff2` fonts. woff2 is referenced from CSS `@font-face`
	 * rather than HTML, so it is offloaded primarily by the Grunt build-time
	 * CSS rewrite; it is included here too so any HTML-side reference (e.g. a
	 * `<link rel="preload" as="font">`) is rewritten as well.
	 *
	 * @since BuddyBoss 3.0.5
	 *
	 * @var string[]
	 */
	const EXTENSIONS = array( 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'bmp', 'woff2' );

	/**
	 * Singleton instance.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var BB_S3_Image_Offload|null
	 */
	private static $instance = null;

	/**
	 * Cached, fully-built replacement regex. Built once per request.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string|null
	 */
	private $pattern = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return BB_S3_Image_Offload
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor — use {@see BB_S3_Image_Offload::instance()}.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function __construct() {}

	/**
	 * Register hooks.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function bootstrap() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		// Buffer the final front-end HTML and rewrite image URLs.
		add_action( 'template_redirect', array( $this, 'start_buffer' ), 1 );

		// Optionally do the same for wp-admin (DRM notice icons, admin logo, etc.).
		if ( $this->is_admin_enabled() ) {
			add_action( 'admin_init', array( $this, 'start_buffer' ), 1 );
		}

		// Cheap belt-and-suspenders: catch any image URL produced via plugins_url().
		add_filter( 'plugins_url', array( $this, 'filter_plugins_url' ), 20, 2 );

		// Rewrite image URLs returned by value-filters that feed REST/AJAX JSON
		// and client-side rendering (search-result icons, avatar/cover/forum
		// fallbacks, invite avatar). These never pass through the HTML output
		// buffer, so without this they would 404 once the local files are
		// stripped from the zip. Hooked late so the core value is built first.
		foreach ( $this->get_value_filters() as $filter ) {
			add_filter( $filter, array( $this, 'filter_value' ), 99 );
		}

		// Rewrite directory base URLs localized to JS. The emoji picker is given
		// a base directory (e.g. `.../bp-core/images/emojifilter/`) and builds
		// the category SVG URLs client-side by appending the filename. A bare
		// directory has no file extension, so the extension-anchored rewriters
		// above never match it, and the runtime-built URLs 404 once the local
		// files are stripped. Hooked late so core builds the value first.
		add_filter( 'bp_core_get_js_strings', array( $this, 'filter_js_strings' ), 99 );

		// ReadyLaunch onboarding wizard: its React app receives an `assetsUrl`
		// base dir and builds the welcome-popup / setup-wizard preview image URLs
		// client-side, plus a few full-URL image refs (logo). The wizard can mount
		// via AJAX (output buffer skipped), so rewrite both the full-URL images
		// and the bare base here. Hooked late so the wizard data is built first.
		add_filter( 'bb_rl_onboarding_localize_data', array( $this, 'filter_onboarding_assets' ), 99 );
	}

	/**
	 * Filters whose returned value carries Platform image URLs that may be
	 * surfaced outside server-rendered HTML (REST/AJAX responses).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string[]
	 */
	public function get_value_filters() {
		$filters = array(
			// Avatar / cover fallbacks (bp-core-functions.php).
			'bb_get_buddyboss_profile_avatar',
			'bb_get_legacy_profile_avatar',
			'bb_get_blank_profile_avatar',
			'bb_get_buddyboss_profile_cover',
			'bb_get_buddyboss_group_avatar',
			'bb_get_legacy_group_avatar',
			'bb_get_custom_buddyboss_group_cover',
			'bb_attachments_get_default_profile_group_avatar_image',
			// Notification + forum + core avatar defaults.
			'bb_get_buddyboss_avatar_avatar_url',
			'bb_get_forum_default_image',
			'bp_core_avatar_default',
			'bp_core_avatar_default_thumb',
			// Search-result thumbnails (array of post_type => url|icon-class).
			'bb_search_post_thumbnail_defaults',
			// REST invite avatar.
			'bp_sent_invite_email_avatar',
			// Settings 2.0 per-field data. Field image URLs (reCAPTCHA badge
			// previews, profile/group/appearance preview images, etc.) are built
			// by raw plugin_url concatenation and surfaced through the settings
			// AJAX response. AJAX skips the HTML output buffer, so without this
			// the field images 404 once the local files are stripped to S3.
			'bb_admin_settings_format_field_data',
		);

		/**
		 * Filters the list of value-returning filters whose image URLs are
		 * offloaded to S3 (for REST/AJAX/client-side rendered images).
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string[] $filters Filter hook names.
		 */
		return (array) apply_filters( 'bb_s3_image_offload_value_filters', $filters );
	}

	/**
	 * Rewrite any Platform image URL(s) inside a filtered value to S3.
	 *
	 * Handles plain string URLs and arrays/objects (e.g. the search thumbnail
	 * map) via map_deep(); non-string leaves and non-image strings pass through
	 * {@see BB_S3_Image_Offload::rewrite()} unchanged.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param mixed $value Filtered value (string, array, or object).
	 *
	 * @return mixed
	 */
	public function filter_value( $value ) {
		if ( '' === $value || null === $value || is_bool( $value ) || is_numeric( $value ) ) {
			return $value;
		}

		return map_deep( $value, array( $this, 'rewrite' ) );
	}

	/**
	 * Rewrite directory base URLs inside the localized JS strings array.
	 *
	 * Some scripts receive a base directory and assemble asset URLs client-side
	 * by appending a filename (the emoji picker appends `recent.svg`, etc.).
	 * Those bare directory URLs carry no file extension, so the extension-anchored
	 * {@see BB_S3_Image_Offload::rewrite()} skips them and the runtime-built URLs
	 * would 404 once local files are stripped. Rewrite each known base here.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $strings Localized JS strings (passed via bp_core_get_js_strings).
	 *
	 * @return array
	 */
	public function filter_js_strings( $strings ) {
		if ( ! is_array( $strings ) ) {
			return $strings;
		}

		/**
		 * Filters the localized JS string paths whose value is a Platform asset
		 * directory base URL that should be offloaded to S3. Each entry is the
		 * top-level group key paired with the inner key holding the base URL.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array[] $base_url_keys List of array( $group_key, $inner_key ) pairs.
		 */
		$base_url_keys = apply_filters(
			'bb_s3_image_offload_js_base_url_keys',
			array(
				array( 'media', 'emoji_filter_url' ),
			)
		);

		foreach ( $base_url_keys as $keys ) {
			if ( ! is_array( $keys ) || 2 !== count( $keys ) ) {
				continue;
			}

			list( $group, $inner ) = $keys;

			if ( isset( $strings[ $group ][ $inner ] ) && is_string( $strings[ $group ][ $inner ] ) ) {
				$strings[ $group ][ $inner ] = $this->rewrite_base_url( $strings[ $group ][ $inner ] );
			}
		}

		return $strings;
	}

	/**
	 * Rewrite ReadyLaunch onboarding asset URLs to S3.
	 *
	 * The wizard's localized data carries both full-URL image refs (logo, preview
	 * images) and an `assets.assetsUrl` base directory that the React app appends
	 * filenames to client-side. The base has no extension (skipped by the
	 * extension-anchored rewriter), and the wizard can mount via AJAX (output
	 * buffer skipped), so rewrite both here.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $data Localized wizard data (bb_rl_onboarding_localize_data).
	 *
	 * @return array
	 */
	public function filter_onboarding_assets( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		// Full-URL image refs (logo, wizard preview images).
		$data = map_deep( $data, array( $this, 'rewrite' ) );

		// Base directory the wizard appends filenames to client-side.
		if ( isset( $data['assets']['assetsUrl'] ) && is_string( $data['assets']['assetsUrl'] ) ) {
			$data['assets']['assetsUrl'] = $this->rewrite_base_url( $data['assets']['assetsUrl'] );
		}

		return $data;
	}

	/**
	 * Rewrite a single Platform directory/base URL to its S3 equivalent.
	 *
	 * Unlike {@see BB_S3_Image_Offload::rewrite()}, this does not require the URL
	 * to end in a file extension — it maps any URL under the plugin source path
	 * (including a bare directory) onto the S3 base, preserving the source-relative
	 * remainder so the 1:1 object-key mapping still holds.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $url A plugin-relative directory or asset URL.
	 *
	 * @return string The S3 URL, or the original URL when it is not under the plugin path.
	 */
	public function rewrite_base_url( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return $url;
		}

		if ( ! function_exists( 'buddypress' ) || empty( buddypress()->plugin_url ) ) {
			return $url;
		}

		$source_path = wp_parse_url( buddypress()->plugin_url, PHP_URL_PATH );
		if ( empty( $source_path ) ) {
			return $url;
		}
		$source_path = trailingslashit( $source_path );

		$pos = strpos( $url, $source_path );
		if ( false === $pos ) {
			return $url;
		}

		$relative = substr( $url, $pos + strlen( $source_path ) );

		// esc_url_raw() the final URL: unlike rewrite(), this method may be fed
		// future base-URL keys whose source-relative remainder is not strictly
		// server-derived, so sanitize the assembled URL before it is localized.
		return esc_url_raw( $this->get_base_url() . ltrim( $relative, '/' ) );
	}

	/**
	 * Whether offloading is enabled.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool
	 */
	public function is_enabled() {
		// Stand down while WP_DEBUG && SCRIPT_DEBUG are active so images are
		// served locally, exactly like a dev checkout.
		$enabled = ! ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG );

		/**
		 * Filters whether Platform images are served from the external S3 bucket.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param bool $enabled Default true. False while WP_DEBUG && SCRIPT_DEBUG are
		 *                       active (assets are restored and served locally), except
		 *                       on a sentinel `build_test` zip where the fetcher cannot
		 *                       restore and S3 remains the only source.
		 */
		return (bool) apply_filters( 'bb_s3_image_offload_enabled', $enabled );
	}

	/**
	 * Whether to also rewrite wp-admin output.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool
	 */
	public function is_admin_enabled() {
		/**
		 * Filters whether wp-admin image URLs are also offloaded to S3.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param bool $enabled Default true.
		 */
		return (bool) apply_filters( 'bb_s3_image_offload_admin', true );
	}

	/**
	 * The S3 base URL that source-relative paths are appended to.
	 *
	 * Includes the bucket URL and the object-key prefix, trailing-slashed.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string
	 */
	public function get_base_url() {
		/**
		 * Filters the S3 bucket base URL (no key prefix).
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $base_url Bucket base URL.
		 */
		// esc_url_raw() on the filtered base: the value is concatenated into the
		// rewritten URLs that land in HTML/CSS, so a malformed or filter-injected
		// base must be sanitized to a safe URL before use.
		$base_url = esc_url_raw( (string) apply_filters( 'bb_s3_image_offload_base_url', self::DEFAULT_BASE_URL ) );
		if ( '' === $base_url ) {
			$base_url = self::DEFAULT_BASE_URL;
		}

		/**
		 * Filters the object-key prefix prepended to source-relative paths.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $prefix Key prefix. Default '' (bucket root).
		 */
		$prefix = apply_filters( 'bb_s3_image_offload_key_prefix', self::DEFAULT_KEY_PREFIX );
		// Strip anything outside a conservative path allowlist so the prefix
		// can't inject characters into the constructed URL.
		$prefix = preg_replace( '#[^A-Za-z0-9_./\-]#', '', (string) $prefix );

		return trailingslashit( $base_url ) . ltrim( (string) $prefix, '/' );
	}

	/**
	 * Image extensions eligible for offloading.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string[]
	 */
	public function get_extensions() {
		/**
		 * Filters the list of image extensions served from S3.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string[] $extensions Extensions without leading dot.
		 */
		$extensions = apply_filters( 'bb_s3_image_offload_extensions', self::EXTENSIONS );

		return array_filter( array_map( 'strtolower', (array) $extensions ) );
	}

	/**
	 * Whether the current request should be skipped (no HTML rewriting).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool
	 */
	private function should_skip() {
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return true;
		}

		if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
			return true;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return true;
		}

		if ( function_exists( 'is_feed' ) && is_feed() ) {
			return true;
		}

		return false;
	}

	/**
	 * Start output buffering with the rewrite callback.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function start_buffer() {
		if ( $this->should_skip() ) {
			return;
		}

		ob_start( array( $this, 'rewrite' ) );
	}

	/**
	 * Filter callback for plugins_url(): rewrite a single image URL.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $url  The plugin URL.
	 * @param string $path Path relative to the plugin (unused).
	 *
	 * @return string
	 */
	public function filter_plugins_url( $url, $path = '' ) {
		unset( $path );

		if ( ! is_string( $url ) || false === strpos( $url, '/' . basename( constant( 'BP_PLUGIN_DIR' ) ) . '/' ) ) {
			return $url;
		}

		return $this->rewrite( $url );
	}

	/**
	 * Rewrite every local Platform image URL in a string to its S3 URL.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $content HTML (or a single URL).
	 *
	 * @return string
	 */
	public function rewrite( $content ) {
		if ( ! is_string( $content ) || '' === $content ) {
			return $content;
		}

		$pattern = $this->get_pattern();
		if ( '' === $pattern ) {
			return $content;
		}

		$base = $this->get_base_url();

		$result = preg_replace_callback(
			$pattern,
			static function ( $matches ) use ( $base ) {
				$relative = isset( $matches['path'] ) ? ltrim( $matches['path'], '/' ) : '';
				$query    = isset( $matches['query'] ) ? $matches['query'] : '';

				return $base . $relative . $query;
			},
			$content
		);

		// preg_replace_callback() returns null on PCRE failure (e.g. backtrack
		// limit on a very large page) — never return null in that case.
		return ( null === $result ) ? $content : $result;
	}

	/**
	 * Build (and cache) the URL-matching regex for this request.
	 *
	 * Anchors on the source URL path so it works in both the dev (`src/`) and
	 * shipped (flattened) layouts. The captured `path` group is source-relative
	 * and maps 1:1 to the S3 object key (after the configured prefix).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string Regex, or '' when it cannot be built.
	 */
	private function get_pattern() {
		if ( null !== $this->pattern ) {
			return $this->pattern;
		}

		$this->pattern = '';

		if ( ! function_exists( 'buddypress' ) || empty( buddypress()->plugin_url ) ) {
			return $this->pattern;
		}

		$source_path = wp_parse_url( buddypress()->plugin_url, PHP_URL_PATH );
		if ( empty( $source_path ) ) {
			return $this->pattern;
		}
		$source_path = trailingslashit( $source_path );

		$extensions = $this->get_extensions();
		if ( empty( $extensions ) ) {
			return $this->pattern;
		}

		$ext_group = implode(
			'|',
			array_map(
				static function ( $ext ) {
					return preg_quote( $ext, '#' );
				},
				$extensions
			)
		);

		// Optional scheme + host, the source path, then a source-relative file
		// path ending in an image extension, then an optional query string.
		$this->pattern = '#(?:(?:https?:)?//[^/"\'\s>()]+)?'
			. preg_quote( $source_path, '#' )
			. '(?P<path>[^"\'\s>()?]+\.(?:' . $ext_group . '))'
			. '(?P<query>\?[^"\'\s>()]*)?#i';

		return $this->pattern;
	}
}
