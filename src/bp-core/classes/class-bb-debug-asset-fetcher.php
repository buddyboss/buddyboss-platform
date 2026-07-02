<?php
/**
 * BB_Debug_Asset_Fetcher: download unminified pair files at runtime when
 * WP_DEBUG && SCRIPT_DEBUG are both true.
 *
 * The shipped zip ships only `.min.{js,css}` for paired assets, and offloads
 * images + woff2 fonts to S3 — all stripped to keep the download small.
 * Developers who toggle `SCRIPT_DEBUG` on want the install to behave exactly
 * like a dev checkout, so this class downloads every stripped file (unminified
 * JS/CSS + images + woff2) from the production branch on GitHub and restores
 * them to their natural paths INSIDE the plugin directory. WordPress then
 * serves them directly (no URL rewriting, no uploads staging), and relative
 * CSS `url()` refs resolve against their restored siblings just like in dev.
 * The S3 image offloader stands down while SCRIPT_DEBUG is active so nothing
 * is redirected away from the restored local copies.
 *
 * Security posture:
 *  - Fetches are pinned to a commit SHA captured at build time (manifest),
 *    so a customer running plugin v3.0.3 always fetches the exact v3.0.3
 *    source tree, never a later HEAD.
 *  - Every fetched blob is SHA-256-verified against the manifest before it
 *    is written to disk. Mismatch → write refused, error logged.
 *  - Files are restored INTO the plugin directory at their manifest-relative
 *    paths. WordPress wipes and re-extracts the plugin dir on update, so the
 *    restored files are transient by design — a version bump re-fetches them.
 *    This requires the plugin directory to be writable by the web server
 *    (the same requirement as WordPress's own plugin auto-update); when it
 *    isn't, the write fails and the page degrades to minified assets.
 *  - Relative paths from the manifest are whitelist-validated to block
 *    path traversal — the resolved target must stay inside the plugin dir.
 *  - Fetch loop is gated on `manage_options` capability — anonymous or
 *    low-privilege admin requests never trigger outbound network I/O.
 *  - A site-transient lock prevents concurrent runs from stampeding the
 *    GitHub raw endpoint.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss 3.0.3
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Runtime fetcher for unminified pair assets.
 *
 * @since BuddyBoss 3.0.3
 */
class BB_Debug_Asset_Fetcher {

	/**
	 * Manifest filename inside the plugin root.
	 */
	const MANIFEST_FILE = 'unminified-manifest.json';

	/**
	 * Schema version the runtime understands.
	 */
	const MANIFEST_SCHEMA = 1;

	/**
	 * Sentinel commit SHA written by local `grunt build_test` flows. Real
	 * production builds embed a 40-character hex SHA; anything else means
	 * the manifest doesn't point at a real GitHub commit, so the fetcher
	 * must refuse to run.
	 */
	const SENTINEL_SHA = 'LOCAL_TEST_BUILD';

	/**
	 * Required prefix for the manifest's `fetch_base_url`. Constrains outbound
	 * fetches to the BuddyBoss source repo on GitHub raw — a tampered manifest
	 * cannot point the fetcher at an arbitrary host (SSRF defense). Matches the
	 * REPO_URL_BASE the manifest generator writes.
	 */
	const FETCH_BASE_PREFIX = 'https://raw.githubusercontent.com/buddyboss/buddyboss-platform/';

	/**
	 * Extensions the fetcher is allowed to write into the plugin directory.
	 * Defense-in-depth: even a tampered manifest cannot drop executable or
	 * config files (.php, .phtml, .htaccess, …) — only the static asset types
	 * the manifest is ever expected to carry.
	 */
	const ALLOWED_EXTENSIONS = array( 'js', 'css', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'bmp', 'woff2' );

	/**
	 * Restore target: the fetched files are written back into the plugin
	 * directory at their manifest-relative paths, i.e.
	 *   {BP_PLUGIN_DIR}/<rel_path>
	 * so WordPress serves them straight from the plugin like a dev checkout.
	 */

	/**
	 * Site-transient key used as a concurrency lock around the fetch loop.
	 */
	const LOCK_TRANSIENT = 'bb_debug_assets_fetch_lock';

	/**
	 * Option storing the plugin version whose assets are currently staged.
	 * When the running plugin's version differs, the next admin_init triggers
	 * a fresh fetch.
	 */
	const VERSION_OPTION = 'bb_debug_assets_fetched_version';

	/**
	 * Option storing the most recent fetch-failure summary, surfaced via
	 * the admin notice. Cleared on a successful subsequent run.
	 */
	const FAILURES_OPTION = 'bb_debug_assets_failures';

	/**
	 * Cron hook scheduled after a plugin update so the fetch kicks off
	 * without waiting for an admin page-load.
	 */
	const REFETCH_CRON_HOOK = 'bb_debug_assets_refetch';

	/**
	 * Per-request cache for {@see get_override_url}/{@see get_override_path}
	 * so each enqueue lookup doesn't restat the filesystem. Keyed by manifest
	 * relative path; values are absolute file paths or false (no override).
	 *
	 * @var array
	 */
	private static $override_cache = array();

	/**
	 * Lazily-loaded manifest, decoded once per request.
	 *
	 * @var array|null|false
	 */
	private static $manifest_cache = null;

	/**
	 * Singleton handle.
	 *
	 * @var BB_Debug_Asset_Fetcher|null
	 */
	private static $instance = null;

	/**
	 * Boolean cache of the active() check for the current request.
	 *
	 * @var bool|null
	 */
	private static $active_cache = null;

	/**
	 * Boolean cache of the sentinel-build check for the current request.
	 *
	 * @var bool|null
	 */
	private static $sentinel_cache = null;

	/**
	 * Singleton accessor.
	 *
	 * @since BuddyBoss 3.0.3
	 *
	 * @return BB_Debug_Asset_Fetcher
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register WordPress hooks. Called once from the bootstrap function.
	 *
	 * Hooks are registered unconditionally so that toggling SCRIPT_DEBUG
	 * at runtime works without a re-init; the handlers themselves call
	 * {@see is_active()} and bail when the gate is off.
	 *
	 * @since BuddyBoss 3.0.3
	 *
	 * @return void
	 */
	public function bootstrap() {
		add_action( 'admin_init', array( $this, 'maybe_fetch' ), 5 );
		add_action( 'upgrader_process_complete', array( $this, 'on_plugin_upgraded' ), 10, 2 );
		add_action( self::REFETCH_CRON_HOOK, array( $this, 'maybe_fetch' ) );

		// Filter every enqueued asset URL through {@see filter_loader_src()}.
		// Late priority (100) so other plugins that mangle handles or paths
		// have already run. Skip during AJAX/REST where enqueues seldom
		// matter and the filter cost is wasted.
		add_filter( 'script_loader_src', array( $this, 'filter_loader_src' ), 100, 2 );
		add_filter( 'style_loader_src', array( $this, 'filter_loader_src' ), 100, 2 );

		// Admin notices: only surface when active AND failures are recorded.
		// Healthy debug installs never see a notice (the fetch is invisible
		// background plumbing — silence is the success state).
		add_action( 'admin_notices', array( $this, 'maybe_render_admin_notice' ) );
	}

	/**
	 * Whether the fetcher is currently allowed to run.
	 *
	 * Cached per request — the underlying constants don't change inside a
	 * single PHP request, so the answer is stable.
	 *
	 * @since BuddyBoss 3.0.3
	 *
	 * @return bool
	 */
	public static function is_active() {
		if ( null !== self::$active_cache ) {
			return self::$active_cache;
		}
		self::$active_cache = (
			defined( 'WP_DEBUG' ) && WP_DEBUG
			&& defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG
		);
		return self::$active_cache;
	}

	/**
	 * Whether the running plugin is a local `build_test` zip whose manifest
	 * carries the {@see SENTINEL_SHA} sentinel commit.
	 *
	 * Such builds strip images/woff2 from the zip (offloaded to S3) but cannot
	 * be restored by the fetcher — the sentinel means there is no real GitHub
	 * commit to fetch the originals from. A dev checkout (no manifest shipped)
	 * and a production build (real 40-hex commit SHA -> restorable) both return
	 * false. Consumers use this to decide whether stripped assets have any local
	 * restore path at all.
	 *
	 * Reads the manifest file directly rather than via {@see load_manifest()}
	 * so a sentinel build is distinguished from a missing/invalid manifest.
	 * Cached per request — the shipped manifest doesn't change mid-request.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool True on a sentinel `build_test` zip, false otherwise.
	 */
	public static function is_sentinel_build() {
		if ( null !== self::$sentinel_cache ) {
			return self::$sentinel_cache;
		}

		self::$sentinel_cache = false;

		$path = trailingslashit( BP_PLUGIN_DIR ) . self::MANIFEST_FILE;
		if ( ! is_readable( $path ) ) {
			return self::$sentinel_cache;
		}

		$raw = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading a bundled plugin file, not a remote resource.
		if ( false === $raw ) {
			return self::$sentinel_cache;
		}

		$manifest = json_decode( $raw, true );
		if ( is_array( $manifest ) && isset( $manifest['commit_sha'] ) && self::SENTINEL_SHA === $manifest['commit_sha'] ) {
			self::$sentinel_cache = true;
		}

		return self::$sentinel_cache;
	}

	/**
	 * Public override-url lookup used by {@see bb_asset_url()}.
	 *
	 * Returns the plugin URL of the unminified file when the fetcher has
	 * restored a verified copy into the plugin directory for the running
	 * version, or `false` to signal the caller to fall back to the minified
	 * URL. Because the file is restored to its natural path, the override URL
	 * is simply the plugin URL for that relative path.
	 *
	 * @since BuddyBoss 3.0.3
	 *
	 * @param string $rel_path Manifest-relative unminified path, e.g.
	 *                         `bp-core/css/foo.css`.
	 * @return string|false
	 */
	public static function get_override_url( $rel_path ) {
		if ( ! self::resolve_override( $rel_path ) ) {
			return false;
		}
		return trailingslashit( BP_PLUGIN_URL ) . $rel_path;
	}

	/**
	 * Filesystem-path counterpart to {@see get_override_url()}.
	 *
	 * @since BuddyBoss 3.0.3
	 *
	 * @param string $rel_path Manifest-relative unminified path.
	 * @return string|false
	 */
	public static function get_override_path( $rel_path ) {
		$abs = self::resolve_override( $rel_path );
		return $abs ? $abs : false;
	}

	/**
	 * Schedule a re-fetch on the next admin_init (or sooner via wp-cron).
	 *
	 * Called by {@see on_plugin_upgraded()} after the plugin directory is
	 * replaced with a new version. Clears the version-tracking option so
	 * the manifest's new version is detected as drift.
	 *
	 * @since BuddyBoss 3.0.3
	 *
	 * @return void
	 */
	public static function schedule_fetch() {
		delete_option( self::VERSION_OPTION );

		// Self-cache invalidation — fresh request will pick up the new
		// manifest. Wp-cron event runs the fetch within a minute regardless
		// of whether an admin actually loads a page.
		if ( ! wp_next_scheduled( self::REFETCH_CRON_HOOK ) ) {
			wp_schedule_single_event( time() + 30, self::REFETCH_CRON_HOOK );
		}
	}

	/**
	 * Filter `script_loader_src` / `style_loader_src` to retrofit paired-asset
	 * resolution onto existing enqueues without rewriting every callsite.
	 *
	 * Behaviour matrix (only applies when the URL points inside the plugin
	 * directory and the file is in the manifest's pair set):
	 *
	 *   SCRIPT_DEBUG | URL form              | Restored on disk | Result
	 *   ------------ | --------------------- | ---------------- | -------------------------------------
	 *   off          | `foo.min.{ext}`       | n/a              | unchanged
	 *   off          | `foo.{ext}`           | n/a              | rewritten to `foo.min.{ext}` (zip doesn't ship the unminified)
	 *   on           | `foo.min.{ext}`       | yes              | rewritten to the restored unminified plugin URL (readable source)
	 *   on           | `foo.min.{ext}`       | no               | unchanged (cleanest fallback)
	 *   on           | `foo.{ext}`           | yes              | the restored unminified URL (== src) — effectively unchanged
	 *   on           | `foo.{ext}`           | no               | rewritten to `foo.min.{ext}` so the page still loads
	 *
	 * Non-paired assets, third-party assets, and admin-AJAX/REST contexts
	 * fall straight through without touching the manifest.
	 *
	 * Manifest-unavailable fallback: when the manifest can't be loaded at all
	 * (missing, or a `grunt build_test` sentinel build whose commit_sha is
	 * LOCAL_TEST_BUILD), we can't look up the pair set — but a stripped
	 * unminified asset would still 404. So as a last resort we check disk: an
	 * unminified plugin URL whose file is absent while its `.min` twin exists
	 * is rewritten to `.min`. This keeps a debug-toggled build_test install
	 * loading (minified) instead of breaking.
	 *
	 * @since BuddyBoss 3.0.3
	 *
	 * @param string $src    The asset URL as it stands after earlier filters.
	 * @param string $handle Enqueue handle (unused, kept for filter signature).
	 * @return string Possibly-rewritten URL.
	 */
	public function filter_loader_src( $src, $handle = '' ) {
		unset( $handle );

		if ( ! is_string( $src ) || '' === $src ) {
			return $src;
		}

		$prefix = trailingslashit( BP_PLUGIN_URL );
		if ( 0 !== strpos( $src, $prefix ) ) {
			return $src;
		}

		// Split off any query string (cache-buster ?ver=...) so the extension
		// matcher sees a clean path.
		$query    = '';
		$path_url = $src;
		$qpos     = strpos( $src, '?' );
		if ( false !== $qpos ) {
			$path_url = substr( $src, 0, $qpos );
			$query    = substr( $src, $qpos );
		}

		$rel = substr( $path_url, strlen( $prefix ) );

		// Resolve `<base>` and `<ext>` for either pair member.
		if ( preg_match( '#^(.+)\.min\.(js|css)$#', $rel, $m ) ) {
			$base        = $m[1];
			$ext         = $m[2];
			$is_minified = true;
		} elseif ( preg_match( '#^(.+)\.(js|css)$#', $rel, $m ) ) {
			$base        = $m[1];
			$ext         = $m[2];
			$is_minified = false;
		} else {
			return $src;
		}

		$unmin_rel = $base . '.' . $ext;
		$manifest  = self::load_manifest();

		if ( $manifest && ! empty( $manifest['files'][ $unmin_rel ] ) ) {
			// Known paired asset. Prefer the staged unminified override while
			// the debug gate is on; otherwise fall back to the minified twin
			// (the unminified file is stripped from the shipped zip).
			if ( self::is_active() ) {
				$override = self::get_override_url( $unmin_rel );
				if ( $override ) {
					return $override . $query;
				}
			}

			if ( $is_minified ) {
				// URL already points at the .min — nothing better to do.
				return $src;
			}

			return $prefix . $base . '.min.' . $ext . $query;
		}

		// Manifest missing/invalid (e.g. a `grunt build_test` sentinel build),
		// or the asset isn't a known pair. We can't consult the manifest, but
		// we can still avoid a hard 404: if this URL points at an unminified
		// file that ISN'T on disk while its `.min` twin IS, the unminified twin
		// was stripped from the zip — rewrite to `.min` so the page still
		// loads. Manifest-free and precise: a genuine lone unminified asset
		// (no `.min` sibling) is left untouched.
		if ( ! $is_minified ) {
			$plugin_dir = trailingslashit( BP_PLUGIN_DIR );
			if (
				! file_exists( $plugin_dir . $unmin_rel )
				&& file_exists( $plugin_dir . $base . '.min.' . $ext )
			) {
				return $prefix . $base . '.min.' . $ext . $query;
			}
		}

		return $src;
	}

	/**
	 * Ensure the running plugin's unminified assets are staged on disk.
	 *
	 * Wired to `admin_init` and to the post-update cron event so a fresh
	 * fetch follows every plugin upgrade without waiting for an admin to
	 * load a page.
	 *
	 * Hot path bail-outs are ordered cheapest-first to keep the no-op
	 * case (every normal admin pageload) under a few microseconds.
	 *
	 * @since BuddyBoss 3.0.3
	 *
	 * @return void
	 */
	public function maybe_fetch() {
		if ( ! self::is_active() ) {
			return;
		}

		// During cron there is no current_user — capability check would
		// always fail. Allow cron-triggered fetches; admin-triggered ones
		// still need a manager.
		if ( ! wp_doing_cron() && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$manifest = self::load_manifest();
		if ( ! $manifest ) {
			return;
		}

		$current_version = isset( $manifest['plugin_version'] ) ? $manifest['plugin_version'] : '';
		$cached_version  = get_option( self::VERSION_OPTION, '' );
		// Skip only when the DB says this version is staged AND the restored
		// files are actually still on disk. The restore lives inside the plugin
		// dir, which WordPress wipes and re-extracts on every update/reinstall,
		// so the version option alone can claim "staged" while the files are
		// gone. The disk check makes a plugin-dir replacement (hook fired or
		// not, version changed or not) always re-trigger a fresh download.
		if ( $current_version && $current_version === $cached_version && self::restore_present( $manifest ) ) {
			return;
		}

		// Lock so concurrent admin_init runs don't all hammer GitHub.
		if ( get_site_transient( self::LOCK_TRANSIENT ) ) {
			return;
		}
		set_site_transient( self::LOCK_TRANSIENT, time(), 30 * MINUTE_IN_SECONDS );

		try {
			$result = $this->run_fetch( $manifest );
		} catch ( Exception $e ) {
			$result = array(
				'fetched'  => 0,
				'skipped'  => 0,
				'failures' => array(
					array(
						'rel'    => '(internal)',
						'reason' => $e->getMessage(),
					),
				),
			);
		}

		delete_site_transient( self::LOCK_TRANSIENT );

		if ( empty( $result['failures'] ) ) {
			update_option( self::VERSION_OPTION, $current_version, false );
			delete_option( self::FAILURES_OPTION );
		} else {
			// Partial success still updates the version so we don't retry
			// hopeless failures on every admin pageload — admin must clear
			// the failures option to retry. But only mark as fetched if
			// at least one file landed, otherwise leave the version blank
			// so a transient network blip retries on the next admin_init.
			if ( $result['fetched'] > 0 ) {
				update_option( self::VERSION_OPTION, $current_version, false );
			}
			update_option(
				self::FAILURES_OPTION,
				array(
					'version'  => $current_version,
					'failures' => $result['failures'],
					'at'       => time(),
				),
				false
			);
		}
	}

	/**
	 * Cheap check that a previously-recorded restore is still physically present
	 * in the plugin directory.
	 *
	 * Restored files live inside the plugin dir, which WordPress wipes and
	 * re-extracts on every update/reinstall, so the DB version option can claim
	 * "staged" while the files are actually gone. We confirm by stat-ing a
	 * single representative manifest entry: every manifest file is stripped from
	 * the shipped zip, so if even one is present the restore ran; if it's
	 * missing the plugin dir was replaced and a fresh fetch is needed.
	 *
	 * @since BuddyBoss 3.0.3
	 *
	 * @param array $manifest Validated manifest.
	 * @return bool True when the restore is present on disk.
	 */
	private static function restore_present( $manifest ) {
		if ( empty( $manifest['files'] ) || ! is_array( $manifest['files'] ) ) {
			return false;
		}

		$rel    = (string) array_key_first( $manifest['files'] );
		$target = self::build_target_path( $rel );

		return $target && is_readable( $target );
	}

	/**
	 * Hook handler: when our plugin is updated, schedule a re-fetch so the
	 * restored plugin files track the new version (WP wipes them on update).
	 *
	 * Fires from `upgrader_process_complete` once WordPress has already
	 * swapped in the new plugin files (and therefore the new manifest).
	 *
	 * @since BuddyBoss 3.0.3
	 *
	 * @param WP_Upgrader $upgrader Unused.
	 * @param array       $hook_extra Upgrader payload describing what was updated.
	 * @return void
	 */
	public function on_plugin_upgraded( $upgrader, $hook_extra ) {
		unset( $upgrader );

		if ( ! self::is_active() ) {
			return;
		}
		if ( empty( $hook_extra['type'] ) || 'plugin' !== $hook_extra['type'] ) {
			return;
		}
		if ( empty( $hook_extra['plugins'] ) || ! is_array( $hook_extra['plugins'] ) ) {
			return;
		}

		$plugin_basename = plugin_basename(
			trailingslashit( BP_PLUGIN_DIR ) . 'bp-loader.php'
		);

		foreach ( $hook_extra['plugins'] as $updated ) {
			if ( $updated === $plugin_basename ) {
				self::schedule_fetch();
				return;
			}
		}
	}

	/**
	 * Render admin notices about the fetcher state.
	 *
	 * Three scenarios are surfaced, all gated on {@see is_active()} +
	 * `manage_options`:
	 *
	 *   1. Manifest missing/invalid in a debug-active install — most likely
	 *      a developer running directly from a non-built `src/` checkout.
	 *      Informational only.
	 *   2. Fetch lock currently held — a fetch is in progress; suggests the
	 *      admin reload the page in a moment.
	 *   3. Recorded failures — listed with the offending files and reasons,
	 *      so the admin can debug network/firewall problems.
	 *
	 * @since BuddyBoss 3.0.3
	 *
	 * @return void
	 */
	public function maybe_render_admin_notice() {
		if ( ! self::is_active() ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$manifest_path = trailingslashit( BP_PLUGIN_DIR ) . self::MANIFEST_FILE;
		if ( ! is_readable( $manifest_path ) ) {
			printf(
				'<div class="notice notice-info"><p><strong>%s</strong> %s</p></div>',
				esc_html__( 'BuddyBoss debug assets:', 'buddyboss-platform' ),
				esc_html__( 'Unminified-asset manifest not found. The shipped plugin zip ships this file; running from a source checkout will skip the runtime override and load minified assets only.', 'buddyboss-platform' )
			);
			return;
		}

		// Fetch in progress.
		if ( get_site_transient( self::LOCK_TRANSIENT ) ) {
			printf(
				'<div class="notice notice-info"><p><strong>%s</strong> %s</p></div>',
				esc_html__( 'BuddyBoss debug assets:', 'buddyboss-platform' ),
				esc_html__( 'Downloading unminified asset bundle for this version… reload in a few seconds.', 'buddyboss-platform' )
			);
			return;
		}

		// Recorded failures from the last run, if any.
		$failures = get_option( self::FAILURES_OPTION );
		if ( empty( $failures ) || empty( $failures['failures'] ) ) {
			return;
		}

		$count = count( $failures['failures'] );
		echo '<div class="notice notice-error"><p><strong>';
		printf(
			/* translators: %d: number of files. */
			esc_html( _n( 'BuddyBoss debug assets: %d file failed to download.', 'BuddyBoss debug assets: %d files failed to download.', $count, 'buddyboss-platform' ) ),
			(int) $count
		);
		echo '</strong> ';
		esc_html_e( 'Pages will fall back to minified bundles. Verify outbound HTTPS to raw.githubusercontent.com, then reload to retry.', 'buddyboss-platform' );
		echo '</p><ul style="margin-left:1.5em;">';
		// Cap to the first 10 to avoid a wall of red on widespread failure.
		$shown = array_slice( $failures['failures'], 0, 10 );
		foreach ( $shown as $entry ) {
			printf(
				'<li><code>%s</code> — %s</li>',
				esc_html( isset( $entry['rel'] ) ? $entry['rel'] : '?' ),
				esc_html( isset( $entry['reason'] ) ? $entry['reason'] : '?' )
			);
		}
		if ( $count > count( $shown ) ) {
			printf(
				'<li>%s</li>',
				esc_html(
					sprintf(
						/* translators: %d: remaining failure count. */
						_n( '…and %d more failure.', '…and %d more failures.', $count - count( $shown ), 'buddyboss-platform' ),
						$count - count( $shown )
					)
				)
			);
		}
		echo '</ul></div>';
	}

	/**
	 * Read, validate, and cache the manifest from the plugin root.
	 *
	 * @since BuddyBoss 3.0.3
	 *
	 * @return array|false Validated manifest array, or false on any error.
	 */
	private static function load_manifest() {
		if ( null !== self::$manifest_cache ) {
			return self::$manifest_cache;
		}

		$path = trailingslashit( BP_PLUGIN_DIR ) . self::MANIFEST_FILE;
		if ( ! is_readable( $path ) ) {
			self::$manifest_cache = false;
			return false;
		}

		$raw = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $raw ) {
			self::$manifest_cache = false;
			return false;
		}

		$manifest = json_decode( $raw, true );
		if ( ! is_array( $manifest ) ) {
			self::$manifest_cache = false;
			return false;
		}

		// Schema gate — refuse manifests from future versions of the build
		// pipeline that may have introduced incompatible field semantics.
		if ( empty( $manifest['schema'] ) || self::MANIFEST_SCHEMA !== (int) $manifest['schema'] ) {
			self::$manifest_cache = false;
			return false;
		}

		// Commit SHA must be a real 40-char hex value, not the local-test
		// sentinel. Without a real SHA the fetch_base_url is meaningless
		// and we'd write whatever the user happened to put in the manifest.
		if (
			empty( $manifest['commit_sha'] )
			|| self::SENTINEL_SHA === $manifest['commit_sha']
			|| ! preg_match( '/^[0-9a-f]{40}$/', $manifest['commit_sha'] )
		) {
			self::$manifest_cache = false;
			return false;
		}

		// fetch_base_url must point at the BuddyBoss source repo on GitHub raw —
		// not merely be https. Constrains outbound I/O to a single known host so
		// a tampered manifest can't turn the fetcher into an SSRF primitive.
		if ( empty( $manifest['fetch_base_url'] ) || 0 !== strpos( $manifest['fetch_base_url'], self::FETCH_BASE_PREFIX ) ) {
			self::$manifest_cache = false;
			return false;
		}

		if ( empty( $manifest['files'] ) || ! is_array( $manifest['files'] ) ) {
			self::$manifest_cache = false;
			return false;
		}

		self::$manifest_cache = $manifest;
		return $manifest;
	}

	/**
	 * Validate that a manifest-supplied relative path is safe to use under
	 * the plugin directory. Rejects empty strings, absolute paths, parent
	 * traversal, NUL bytes, and any byte outside a conservative allowlist.
	 *
	 * @since BuddyBoss 3.0.3
	 *
	 * @param string $rel Manifest-relative path candidate.
	 * @return bool True when the path is safe for use under the plugin tree.
	 */
	private static function validate_rel_path( $rel ) {
		if ( ! is_string( $rel ) || '' === $rel ) {
			return false;
		}
		if ( strpos( $rel, "\0" ) !== false ) {
			return false;
		}
		if ( 0 === strpos( $rel, '/' ) ) {
			return false;
		}
		if ( false !== strpos( $rel, '..' ) ) {
			return false;
		}
		// Conservative allowlist: ASCII letters, digits, dot, dash, underscore, forward slash.
		if ( ! preg_match( '#^[A-Za-z0-9_./\-]+$#', $rel ) ) {
			return false;
		}
		// Defense-in-depth extension allowlist: only static asset types the
		// manifest is ever expected to carry may be written into the plugin
		// directory. Blocks executable/config drops (.php, .phtml, .htaccess)
		// even if the manifest were tampered with.
		$dot = strrpos( $rel, '.' );
		$ext = ( false === $dot ) ? '' : strtolower( substr( $rel, $dot + 1 ) );
		if ( ! in_array( $ext, self::ALLOWED_EXTENSIONS, true ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Resolve a manifest-relative path to an on-disk override file, if the
	 * file exists and matches the manifest hash. Returns the absolute path
	 * for a verified file, or false otherwise.
	 *
	 * Cached per request: a typical page loads dozens of assets via
	 * bb_asset_url(), each of which calls this lookup. Caching keeps the
	 * filesystem traffic at "one stat per asset per request" worst-case.
	 *
	 * @since BuddyBoss 3.0.3
	 *
	 * @param string $rel_path Manifest-relative unminified path.
	 * @return string|false Absolute path on success, false on miss.
	 */
	private static function resolve_override( $rel_path ) {
		if ( ! self::is_active() ) {
			return false;
		}
		if ( ! self::validate_rel_path( $rel_path ) ) {
			return false;
		}
		if ( array_key_exists( $rel_path, self::$override_cache ) ) {
			return self::$override_cache[ $rel_path ];
		}

		$manifest = self::load_manifest();
		if ( ! $manifest || empty( $manifest['files'][ $rel_path ] ) ) {
			self::$override_cache[ $rel_path ] = false;
			return false;
		}

		$abs = self::build_target_path( $rel_path );
		if ( false === $abs || ! is_readable( $abs ) ) {
			self::$override_cache[ $rel_path ] = false;
			return false;
		}

		self::$override_cache[ $rel_path ] = $abs;
		return $abs;
	}

	/**
	 * Build the absolute restore path for a manifest entry inside the plugin
	 * directory, enforcing that the resolved location stays under it.
	 *
	 * @since BuddyBoss 3.0.3
	 *
	 * @param string $rel_path Manifest-relative path.
	 * @return string|false Absolute path, or false on validation failure.
	 */
	private static function build_target_path( $rel_path ) {
		if ( ! self::validate_rel_path( $rel_path ) ) {
			return false;
		}

		$base   = trailingslashit( BP_PLUGIN_DIR );
		$target = $base . $rel_path;

		// Belt and suspenders: even after the regex screen, sanity-check
		// that the resolved path is still under the plugin dir. wp_normalize_path
		// makes the prefix comparison cross-platform.
		$norm_target = wp_normalize_path( $target );
		$norm_base   = wp_normalize_path( $base );
		if ( 0 !== strpos( $norm_target, $norm_base ) ) {
			return false;
		}

		return $target;
	}

	/**
	 * Walk the manifest and fetch any missing or stale files.
	 *
	 * @since BuddyBoss 3.0.3
	 *
	 * @param array $manifest Validated manifest.
	 * @return array {
	 *     @type int   $fetched  Number of files newly written this run.
	 *     @type int   $skipped  Files already present with matching hash.
	 *     @type array $failures Failure descriptors: { rel, reason }.
	 * }
	 */
	private function run_fetch( $manifest ) {
		$fetched  = 0;
		$skipped  = 0;
		$failures = array();

		foreach ( $manifest['files'] as $rel => $expected_hash ) {
			if ( ! self::validate_rel_path( $rel ) ) {
				$failures[] = array(
					'rel'    => (string) $rel,
					'reason' => 'invalid path',
				);
				continue;
			}
			if ( ! is_string( $expected_hash ) || 0 !== strpos( $expected_hash, 'sha256:' ) ) {
				$failures[] = array(
					'rel'    => $rel,
					'reason' => 'invalid hash format',
				);
				continue;
			}

			$target = self::build_target_path( $rel );
			if ( false === $target ) {
				$failures[] = array(
					'rel'    => $rel,
					'reason' => 'path resolution failed',
				);
				continue;
			}

			// Skip if already on disk with a matching hash. Cheap fast-path
			// for upgrades where most files are unchanged version-to-version.
			if ( is_readable( $target ) && self::hash_file( $target ) === $expected_hash ) {
				++$skipped;
				continue;
			}

			$body = $this->http_get( trailingslashit( $manifest['fetch_base_url'] ) . $rel );
			if ( is_wp_error( $body ) ) {
				$failures[] = array(
					'rel'    => $rel,
					'reason' => $body->get_error_message(),
				);
				continue;
			}

			$got = 'sha256:' . hash( 'sha256', $body );
			if ( ! hash_equals( $expected_hash, $got ) ) {
				$failures[] = array(
					'rel'    => $rel,
					'reason' => 'hash mismatch',
				);
				continue;
			}

			if ( ! $this->write_file( $target, $body ) ) {
				$failures[] = array(
					'rel'    => $rel,
					'reason' => 'write failed',
				);
				continue;
			}

			++$fetched;
		}

		return array(
			'fetched'  => $fetched,
			'skipped'  => $skipped,
			'failures' => $failures,
		);
	}

	/**
	 * Compute the manifest-style SHA-256 of a file on disk.
	 *
	 * @since BuddyBoss 3.0.3
	 *
	 * @param string $path Absolute path.
	 * @return string 'sha256:<hex>'
	 */
	private static function hash_file( $path ) {
		return 'sha256:' . hash_file( 'sha256', $path );
	}

	/**
	 * Fetch a URL and return the raw response body, or WP_Error.
	 *
	 * @since BuddyBoss 3.0.3
	 *
	 * @param string $url Absolute https:// URL to fetch.
	 * @return string|WP_Error Body on success, WP_Error on transport or HTTP error.
	 */
	private function http_get( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 15,
				'redirection' => 3,
				'user-agent'  => 'BuddyBoss-Platform-Debug-Fetcher/1.0',
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== (int) $code ) {
			return new WP_Error( 'bb_debug_fetch_http', sprintf( 'HTTP %d', (int) $code ) );
		}

		$body = wp_remote_retrieve_body( $response );
		if ( '' === $body ) {
			return new WP_Error( 'bb_debug_fetch_empty', 'empty response body' );
		}

		return $body;
	}

	/**
	 * Write a verified blob to disk via WP_Filesystem.
	 *
	 * @since BuddyBoss 3.0.3
	 *
	 * @param string $target Absolute path.
	 * @param string $body   File contents (already SHA-verified).
	 * @return bool
	 */
	private function write_file( $target, $body ) {
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( empty( $wp_filesystem ) ) {
			// Direct method only — we never prompt for FTP credentials
			// because there's no UI here. Sites whose plugin directory isn't
			// directly writable will surface the failure in the admin notice.
			WP_Filesystem();
		}
		if ( empty( $wp_filesystem ) ) {
			return false;
		}

		$dir = dirname( $target );
		if ( ! wp_mkdir_p( $dir ) ) {
			return false;
		}

		return (bool) $wp_filesystem->put_contents( $target, $body, FS_CHMOD_FILE );
	}
}
