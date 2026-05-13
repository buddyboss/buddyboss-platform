<?php
/**
 * BuddyBoss Platform 3.0 — addon version compatibility admin notice.
 *
 * Surfaces a non-dismissible warning to admins when Platform is on the
 * 3.0 backend-settings architecture but one or more installed-and-active
 * BuddyBoss addons are still on a pre-3.0-compatible version. The
 * 3.0 release reshapes the feature registry, REST proxy surface, and
 * settings-storage contracts — every addon listed below was updated in
 * lockstep, and running a 2.x addon against a 3.0 Platform produces
 * silent breakage (missing settings UI, stale caches, fatal errors in
 * extension hooks).
 *
 * Companion to the per-addon notices that live inside each addon plugin
 * (Pro, Gamification, Sharing, Offload Media, LearnDash) — those fire
 * the OPPOSITE direction: when an addon is at its 3.x/2.x/1.x line and
 * Platform is still on the 2.19 line. Both notices live until the user
 * resolves the version mismatch — there is deliberately no dismissal
 * affordance because acknowledging a broken integration without fixing
 * it does the user no favors.
 *
 * @package BuddyBoss\Core\Administration
 *
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Required-addon-version map for Platform 3.0.
 *
 * Keyed by plugin file (the same key WP uses in `active_plugins`) so we
 * can run a single `is_plugin_active()` check per row without splitting
 * the slug from the directory. The order is the order admins will see
 * them: Pro first because it ships to the largest paid base, then
 * alphabetical. Adding a new addon to the 3.0 family is a one-line
 * append here.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array<string, array{name: string, version: string}>
 */
function bb_platform_3_required_addons() {
	return array(
		'buddyboss-platform-pro/buddyboss-platform-pro.php'   => array(
			'name'    => __( 'BuddyBoss Platform Pro', 'buddyboss' ),
			'version' => '3.0.0',
		),
		'buddyboss-gamification/buddyboss-gamification.php'   => array(
			'name'    => __( 'BuddyBoss Gamification', 'buddyboss' ),
			'version' => '2.0.0',
		),
		'buddyboss-learndash/buddyboss-learndash.php'         => array(
			'name'    => __( 'BuddyBoss LearnDash', 'buddyboss' ),
			'version' => '1.0.0',
		),
		'buddyboss-offload-media/buddyboss-offload-media.php' => array(
			'name'    => __( 'BuddyBoss Offload Media', 'buddyboss' ),
			'version' => '2.0.0',
		),
		'buddyboss-sharing/buddyboss-sharing.php'             => array(
			'name'    => __( 'BuddyBoss Sharing', 'buddyboss' ),
			'version' => '2.0.0',
		),
	);
}

/**
 * Resolve the installed version of an addon plugin by file path.
 *
 * Reads from the WP plugins cache (populated by `get_plugins()`) so we
 * pay one filesystem walk per request even when checking five addons.
 * Returns an empty string when the plugin file is missing — the caller
 * uses that as the "not installed" signal and skips the row.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $plugin_file Plugin file relative to WP_PLUGIN_DIR.
 *
 * @return string Installed version (`''` when not installed).
 */
function bb_platform_3_get_addon_version( $plugin_file ) {
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	static $all_plugins = null;
	if ( null === $all_plugins ) {
		$all_plugins = get_plugins();
	}
	if ( isset( $all_plugins[ $plugin_file ]['Version'] ) ) {
		return (string) $all_plugins[ $plugin_file ]['Version'];
	}
	return '';
}

/**
 * Collect every active addon whose installed version is below the 3.0
 * compatibility floor.
 *
 * "Active" follows the same rule WP core uses for `is_plugin_active()`
 * — the plugin file appears in either the per-site `active_plugins`
 * option or, on multisite, the network-wide `active_sitewide_plugins`
 * option. Network-active addons trip the notice on every site in the
 * network, which is the right behaviour because the version mismatch
 * is global to the install, not per-site.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array<int, array{name: string, current: string, required: string}>
 */
function bb_platform_3_get_lagging_addons() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$lagging = array();
	foreach ( bb_platform_3_required_addons() as $plugin_file => $row ) {
		if ( ! is_plugin_active( $plugin_file ) ) {
			continue;
		}
		$installed = bb_platform_3_get_addon_version( $plugin_file );
		if ( '' === $installed ) {
			// Active but the plugin file vanished from disk — WP will
			// emit its own broken-plugin notice for this case, no
			// reason to pile on.
			continue;
		}
		if ( version_compare( $installed, $row['version'], '>=' ) ) {
			continue;
		}
		$lagging[] = array(
			'name'     => $row['name'],
			'current'  => $installed,
			'required' => $row['version'],
		);
	}
	return $lagging;
}

/**
 * Render the addon-update admin notice on every WP-admin page.
 *
 * Lives on the global `admin_notices` hook so the notice surfaces on
 * any admin screen — version mismatches break Settings 2.0 specifically
 * but the symptom (missing UI, fatals in extension hooks) can manifest
 * anywhere the admin works. Skipping this on non-Platform screens would
 * hide the warning from the people most likely to encounter the
 * downstream breakage.
 *
 * Notice is non-dismissible by design: an acknowledged-but-unresolved
 * version mismatch is a worse state than an unread one, because the
 * admin could go weeks before noticing the silent failures the notice
 * was warning them about. The notice disappears the instant every
 * lagging addon hits its required version — the check runs on every
 * page load, so there's no stale-state window.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_platform_3_render_addon_compat_notice() {
	if ( ! current_user_can( 'update_plugins' ) ) {
		return;
	}

	$lagging = bb_platform_3_get_lagging_addons();
	if ( empty( $lagging ) ) {
		return;
	}

	// `BP_PLATFORM_VERSION` is defined by the time `admin_notices` fires
	// because the constant is set in `bp-loader.php` at plugin boot — but
	// the fallback keeps the notice from fataling on any odd boot order
	// where it might not be available yet (e.g. during a partial upgrade).
	$platform_version = defined( 'BP_PLATFORM_VERSION' ) ? BP_PLATFORM_VERSION : '3.0.0';

	// Update-Core is the right destination — it's the screen with the
	// per-plugin update checkboxes WP populates from the plugin update
	// transient. plugins.php shows updates only as a passive banner.
	$update_url = self_admin_url( 'update-core.php' );
	?>
	<div class="notice notice-error">
		<p>
			<strong>
				<?php
				printf(
					/* translators: %s: Platform version */
					esc_html__( 'BuddyBoss Platform %s requires updates to the following plugins:', 'buddyboss' ),
					esc_html( $platform_version )
				);
				?>
			</strong>
		</p>
		<ul style="list-style: disc; padding-left: 22px; margin: 4px 0 12px;">
			<?php foreach ( $lagging as $row ) : ?>
				<li>
					<?php
					printf(
						/* translators: 1: Plugin name. 2: Required version. 3: Currently installed version. */
						esc_html__( '%1$s (%2$s or higher) — currently %3$s', 'buddyboss' ),
						'<strong>' . esc_html( $row['name'] ) . '</strong>',
						esc_html( $row['required'] ),
						esc_html( $row['current'] )
					);
					?>
				</li>
			<?php endforeach; ?>
		</ul>
		<p>
			<a href="<?php echo esc_url( $update_url ); ?>" class="button button-primary">
				<?php esc_html_e( 'Update plugins now', 'buddyboss' ); ?>
			</a>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'bb_platform_3_render_addon_compat_notice' );
add_action( 'network_admin_notices', 'bb_platform_3_render_addon_compat_notice' );
