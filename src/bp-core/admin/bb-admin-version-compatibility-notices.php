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
 * @since BuddyBoss 3.0.0
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
 * @since BuddyBoss 3.0.0
 *
 * @return array<string, array{name: string, version: string}>
 */
function bb_platform_3_required_addons() {
	return array(
		'buddyboss-platform-pro/buddyboss-platform-pro.php'   => array(
			'name'    => __( 'BuddyBoss Platform Pro', 'buddyboss' ),
			'version' => bb_platform_3_pro_required_version(),
		),
		'buddyboss-addons/buddyboss-addons.php'               => array(
			'name'    => __( 'BuddyBoss Add-ons', 'buddyboss' ),
			'version' => bb_platform_3_addons_required_version(),
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
			'version' => bb_platform_3_offload_media_required_version(),
		),
		'buddyboss-sharing/buddyboss-sharing.php'           => array(
			'name'    => __( 'BuddyBoss Sharing', 'buddyboss' ),
			'version' => '2.0.0',
		),
	);
}

/**
 * Minimum BuddyBoss Platform Pro version required by this Platform.
 *
 * Pro builds before PROD-9995 still register the Upload Videos and Upload
 * Documents access-control fields when those components are inactive.
 * They do not fatal, because the field callbacks only run from the media
 * settings screen, so this floor only keeps the settings screen accurate.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string Minimum Platform Pro version.
 */
function bb_platform_3_pro_required_version() {
	return '3.4.0';
}

/**
 * Minimum BuddyBoss Add-ons version required by this Platform.
 *
 * Platform no longer ships Video and Documents, so the Add-ons copies are
 * the only live code. 1.3.0 is the first Add-ons release with the fixes that
 * shipped in Platform's own copies: the Private Website download guard
 * (bb_private_network_restrict_download()), the REST document-folder
 * permission rules and the group peer-folder upload rule. It also stops
 * locking these modules on sites without an activated licence. The
 * released 1.2.3 has none of these.
 *
 * Platform 3.7.0 also moved the Video and Documents template pack code,
 * settings panels and group screens into Add-ons 1.3.0, so an
 * older Add-ons build cannot run them: bb_is_component_directory_available()
 * keeps both components off (their data is kept) until Add-ons is updated.
 *
 * This is the single source of the floor: bb_get_addons_required_version()
 * reads it for the post-update Add-ons update and the Media settings panels.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string Minimum Add-ons version.
 */
function bb_platform_3_addons_required_version() {
	return '1.3.0';
}

/**
 * Minimum BuddyBoss Offload Media version required by this Platform.
 *
 * Video and Documents now ship in BuddyBoss Add-ons, so BP_Video and
 * BP_Document can be missing. Offload Media 2.2.0 carries the
 * class_exists() guards from Offload Media #25 around its BP_Video and
 * BP_Document use (video store handler, bucket helper); 2.1.4 and older
 * fatal while processing existing videos or documents without Add-ons.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string Minimum Offload Media version.
 */
function bb_platform_3_offload_media_required_version() {
	return '2.2.0';
}

/**
 * Required-theme-version map for this Platform.
 *
 * Keyed by theme directory (template). A row is checked only when that
 * theme is active, or is the parent of the active child theme.
 * The floor comes from bb_platform_3_theme_required_version()
 * (bp-incompatible-plugins-helper.php), which also gates the document
 * meta shim for older theme builds.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array<string, array{name: string, version: string}>
 */
function bb_platform_3_required_themes() {
	return array(
		'buddyboss-theme' => array(
			'name'    => __( 'BuddyBoss Theme', 'buddyboss' ),
			'version' => bb_platform_3_theme_required_version(),
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
 * @since BuddyBoss 3.0.0
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
 * Collect every active addon, and the active BuddyBoss theme, whose
 * installed version is below the 3.0 compatibility floor.
 *
 * "Active" follows the same rule WP core uses for `is_plugin_active()`
 * — the plugin file appears in either the per-site `active_plugins`
 * option or, on multisite, the network-wide `active_sitewide_plugins`
 * option. Network-active addons trip the notice on every site in the
 * network, which is the right behaviour because the version mismatch
 * is global to the install, not per-site.
 *
 * @since BuddyBoss 3.0.0
 *
 * @since BuddyBoss [BBVERSION] Also checks bb_platform_3_required_themes().
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

	// Themes: checked only when active, or the parent of the active child theme.
	foreach ( bb_platform_3_required_themes() as $stylesheet => $row ) {
		if ( get_template() !== $stylesheet ) {
			continue;
		}
		$installed = 'buddyboss-theme' === $stylesheet ? bb_get_active_buddyboss_theme_version() : (string) wp_get_theme( $stylesheet )->get( 'Version' );
		if ( '' === $installed || version_compare( $installed, $row['version'], '>=' ) ) {
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
 * @since BuddyBoss 3.0.0
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
