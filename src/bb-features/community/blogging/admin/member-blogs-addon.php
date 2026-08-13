<?php
/**
 * Member Blogging add-on state resolution for the "Member Blogs" upsell panel.
 *
 * The Member Blogs side panel is registered by Platform only while the
 * `buddyboss-member-blogging` add-on has NOT booted (see admin/settings.php).
 * In that window the panel renders a single `empty_state` field whose copy and
 * call-to-action depend on four things that Platform can only know at runtime:
 *
 *   1. Is the add-on on disk?
 *   2. Is it activated?
 *   3. Is the BuddyBoss license activated?
 *   4. Is the add-on included in the licensed plan?
 *
 * This mirrors how the Tools feature drives its Sample Data / Migration Tools
 * panels (`bp-core/admin/settings/tools/callbacks.php`), with one deliberate
 * difference: activation is handled by a Platform-owned AJAX handler that calls
 * `activate_plugin()` directly, NOT by the vendored Mothership
 * `mosh_addon_activate` action. Mothership's `setupAjaxRequest()` resolves the
 * product from the licensed add-ons API before doing anything, so it returns
 * "Add-on not found." on every site without an activated license — even when
 * the plugin is sitting on disk ready to be switched on. Activating an
 * already-installed plugin requires no license, so it must not be gated on one.
 *
 * Install DOES stay license-gated: the add-on is served from the BuddyBoss
 * add-on server, not wordpress.org, so the package URL can only be resolved
 * through an activated license.
 *
 * State is resolved from the `bb_admin_settings_format_field_data` filter
 * rather than at feature-registration time on purpose.
 * `BB_Addons_Manager::checkProductBySlug()` performs a blocking remote request
 * whenever its 12-hour transient is cold, and `bb_register_features` fires on
 * every admin request. The filter only runs inside the
 * `bb_admin_get_feature_settings` AJAX call, i.e. exactly when the panel is
 * actually being viewed.
 *
 * @since   BuddyBoss 3.3.0
 * @package BuddyBoss\Blogging
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plugin file (relative to the plugins directory) of the Member Blogging add-on.
 *
 * @since BuddyBoss 3.3.0
 *
 * @return string Plugin basename.
 */
function bb_member_blogging_plugin_file() {
	return 'buddyboss-member-blogging/buddyboss-member-blogging.php';
}

/**
 * Plugin folder slug of the Member Blogging add-on.
 *
 * Also the product slug used by the BuddyBoss add-on server.
 *
 * @since BuddyBoss 3.3.0
 *
 * @return string Plugin folder slug.
 */
function bb_member_blogging_plugin_slug() {
	return 'buddyboss-member-blogging';
}

/**
 * Whether the BuddyBoss Mothership license layer is available.
 *
 * @since BuddyBoss 3.3.0
 *
 * @return bool True when both Mothership helper classes are loaded.
 */
function bb_member_blogging_mothership_available() {
	return class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector' )
		&& class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager' );
}

/**
 * Whether the BuddyBoss license is currently activated.
 *
 * @since BuddyBoss 3.3.0
 *
 * @return bool True when the Mothership connector reports an active license.
 */
function bb_member_blogging_is_license_active() {
	if ( ! class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector' ) ) {
		return false;
	}

	$connector = new \BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector();

	return (bool) $connector->getLicenseActivationStatus();
}

/**
 * Admin URL of the BuddyBoss license activation page.
 *
 * @since BuddyBoss 3.3.0
 *
 * @return string Admin URL for the license activation screen.
 */
function bb_member_blogging_get_license_url() {
	return bp_get_admin_url( 'admin.php?page=buddyboss-license' );
}

/**
 * Whether DRM would lock the Member Blogging add-on's features.
 *
 * Needed because activation is no longer implicitly license-gated (see the file
 * header): an unlicensed site can now switch the add-on on from this panel, so
 * the panel must not promise functionality that DRM will immediately deny.
 *
 * Two paths, because the registry is only half an answer:
 *
 * - Add-on ACTIVE — its `plugins_loaded` hook has run `BB_DRM_Registry::register_addon()`,
 *   so the registry is authoritative.
 * - Add-on INACTIVE — `register_addon()` never ran, and
 *   `should_lock_addon_features()` allows-by-default for unregistered slugs. It
 *   would return false regardless of a lockout that predates the deactivation
 *   (DRM events survive it; only a valid license clears them via
 *   `cleanup_addon_drm()`). Instantiate `BB_DRM_Addon` for the slug instead so
 *   the real policy — including the grace-period threshold — is reused rather
 *   than duplicated here.
 *
 * @since BuddyBoss 3.3.0
 *
 * @return bool True when the add-on's features are DRM-locked.
 */
function bb_member_blogging_addon_is_drm_locked() {
	if ( ! class_exists( '\BuddyBoss\Core\Admin\DRM\BB_DRM_Registry' ) ) {
		return false;
	}

	$slug = bb_member_blogging_plugin_slug();

	if ( \BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::is_addon_registered( $slug ) ) {
		return (bool) \BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::should_lock_addon_features( $slug );
	}

	if ( ! class_exists( '\BuddyBoss\Core\Admin\DRM\BB_DRM_Addon' ) ) {
		return false;
	}

	// Constructing this is cheap and side-effect-light — it only registers a
	// `bb_drm_addon_event_{slug}` callback, which fires from run(), never here.
	$drm = new \BuddyBoss\Core\Admin\DRM\BB_DRM_Addon( $slug, __( 'Member Blogging', 'buddyboss-platform' ) );

	return (bool) $drm->should_lock_features();
}

/**
 * Resolve the current state of the Member Blogging add-on.
 *
 * Possible states, in resolution order:
 *
 * - `active`             Plugin is installed and activated. Because this panel
 *                        is only registered while `BB_MEMBER_BLOG_VERSION` is
 *                        undefined, reaching this state means the installed
 *                        add-on is too old (or failed to boot) to register its
 *                        own settings — the admin needs to update it.
 * - `installed_locked`   On disk, not activated, and DRM would lock it on boot
 *                        (unlicensed past the grace period). Needs a license,
 *                        not an activation.
 * - `installed_inactive` On disk, not activated, not DRM-locked. Activatable
 *                        without a license (WordPress lets any admin do this
 *                        from Plugins anyway; DRM's grace period then governs
 *                        how long it keeps working).
 * - `not_installed`      Not on disk, license active, product in the plan.
 *                        Installable in place from the add-on server.
 * - `needs_license`      Not on disk, Mothership present, license not activated.
 *                        Plan membership is unknowable until the license is on.
 * - `not_in_plan`        Not on disk and either the license is active but the
 *                        product is absent from the plan, or the Mothership
 *                        layer is missing entirely (no supported install path).
 * - `api_unavailable`    Not on disk, license active, but the add-ons API did not
 *                        answer, so plan membership is genuinely unknown. Kept
 *                        distinct from `not_in_plan` because an empty product list
 *                        means the same thing in both cases, and telling a licensed
 *                        customer to upgrade over a network blip is the worse of
 *                        the two ways to be wrong.
 *
 * Memoized per request: none of the inputs can change within a single request
 * except inside the install/activate handlers below, which read the state
 * before they mutate anything.
 *
 * @since BuddyBoss 3.3.0
 *
 * @return string One of: 'active', 'installed_locked', 'installed_inactive',
 *                'not_installed', 'needs_license', 'not_in_plan',
 *                'api_unavailable'.
 */
function bb_member_blogging_get_addon_state() {
	static $state = null;

	if ( null !== $state ) {
		return $state;
	}

	$plugin_file = bb_member_blogging_plugin_file();

	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( is_plugin_active( $plugin_file ) ) {
		$state = 'active';

		return $state;
	}

	if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
		// On disk but switched off. If DRM would lock it the moment it boots
		// (unlicensed past the grace period), offering a bare "Activate Plugin"
		// promises a feature the add-on will refuse to load — surface the
		// licence blocker instead.
		$state = bb_member_blogging_addon_is_drm_locked() ? 'installed_locked' : 'installed_inactive';

		return $state;
	}

	// Not on disk from here down — an install is the only path forward, and the
	// add-on is not on wordpress.org, so it needs the licensed add-on server.
	if ( ! bb_member_blogging_mothership_available() ) {
		$state = 'not_in_plan';

		return $state;
	}

	if ( ! bb_member_blogging_is_license_active() ) {
		$state = 'needs_license';

		return $state;
	}

	$product = \BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager::checkProductBySlug( bb_member_blogging_plugin_slug() );

	if ( ! empty( $product ) ) {
		$state = 'not_installed';

		return $state;
	}

	/*
	 * An empty product is ambiguous: it means either "not in this customer's plan" or
	 * "the add-ons API did not answer". Telling a licensed customer to upgrade because
	 * of a network blip is the worse failure, so only claim not_in_plan when the API
	 * actually reported back.
	 */
	$state = \BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager::productsApiErrored()
		? 'api_unavailable'
		: 'not_in_plan';

	return $state;
}

/**
 * Filterable wrapper around the resolved Member Blogging add-on state.
 *
 * Kept separate from the resolver so the filter cannot poison the memoized
 * value, and so staging/QA can exercise every empty-state branch without a
 * matching license.
 *
 * @since BuddyBoss 3.3.0
 *
 * @return string Resolved add-on state.
 */
function bb_member_blogging_addon_state() {
	/**
	 * Filters the resolved Member Blogging add-on state.
	 *
	 * @since BuddyBoss 3.3.0
	 *
	 * @param string $state One of: 'active', 'installed_locked',
	 *                      'installed_inactive', 'not_installed',
	 *                      'needs_license', 'not_in_plan', 'api_unavailable'.
	 */
	return apply_filters( 'bb_member_blogging_addon_state', bb_member_blogging_get_addon_state() );
}

/**
 * Build the `empty_state` field payload for the current add-on state.
 *
 * Returns only the keys that vary by state; the caller merges them over the
 * field data formatted from the static registration in admin/settings.php.
 *
 * `addon_action` + `addon_slug` make the React `<AddonActivateButton>` fire an
 * AJAX call instead of following a link. `addon_nonce_key` names the
 * `window.bbAdminData` property holding the nonce for that call — Platform's
 * own handlers below verify the `bb_admin_settings` nonce (`ajaxNonce`), while
 * Mothership handlers verify `mosh_addons` (`addonNonce`).
 *
 * @since BuddyBoss 3.3.0
 *
 * @return array Field data overrides.
 */
function bb_member_blogging_get_upsell_field_data() {
	$defaults = array(
		'empty_state_title'       => __( 'Member Blogging', 'buddyboss-platform' ),
		'empty_state_description' => '',
		'button_label'            => null,
		'button_url'              => null,
		// Marks a state whose button is a marketing upsell rather than a link to a
		// screen in this install. Only those states take the campaign-tagged URL from
		// the field-upgrades catalog; the license and add-ons screens must keep theirs.
		'button_url_from_catalog' => false,
		'button_target'           => null,
		'addon_action'            => null,
		'addon_slug'              => null,
		'addon_nonce_key'         => null,
		'addon_busy_label'        => null,
	);

	$state = bb_member_blogging_addon_state();

	switch ( $state ) {

		case 'active':
			// Active but this panel still exists, so the add-on never defined
			// BB_MEMBER_BLOG_VERSION — an outdated build. Point at the add-ons
			// screen where the update is offered.
			$data = array(
				'empty_state_description' => __( 'The Member Blogging add-on is active but this version does not provide these settings. Update the add-on to continue.', 'buddyboss-platform' ),
				'button_label'            => __( 'Manage Add-ons', 'buddyboss-platform' ),
				'button_url'              => bp_get_admin_url( 'admin.php?page=buddyboss-addons' ),
			);
			break;

		case 'installed_locked':
			// Installed, but DRM has already locked it (unlicensed past the
			// grace period). Activating would boot straight into the add-on's
			// locked mode, so lead with the actual blocker.
			$data = array(
				'empty_state_description' => __( 'The Member Blogging add-on is installed, but your BuddyBoss license is not active. Activate your license to use Member Blogging.', 'buddyboss-platform' ),
				'button_label'            => __( 'Activate License', 'buddyboss-platform' ),
				'button_url'              => bb_member_blogging_get_license_url(),
			);
			break;

		case 'installed_inactive':
			$data = array(
				'empty_state_description' => __( 'The Member Blogging add-on is installed but not activated. Activate it to let your community members create blog posts from the frontend.', 'buddyboss-platform' ),
				'button_label'            => __( 'Activate Plugin', 'buddyboss-platform' ),
				'addon_action'            => 'bb_member_blogging_activate_plugin',
				'addon_slug'              => bb_member_blogging_plugin_slug(),
				'addon_nonce_key'         => 'ajaxNonce',
				'addon_busy_label'        => __( 'Activating…', 'buddyboss-platform' ),
			);
			break;

		case 'not_installed':
			$data = array(
				'empty_state_description' => __( 'Member Blogging is included in your plan. Install the add-on to let your community members create blog posts from the frontend.', 'buddyboss-platform' ),
				'button_label'            => __( 'Install & Activate', 'buddyboss-platform' ),
				'addon_action'            => 'bb_member_blogging_install_plugin',
				'addon_slug'              => bb_member_blogging_plugin_slug(),
				'addon_nonce_key'         => 'ajaxNonce',
				'addon_busy_label'        => __( 'Installing…', 'buddyboss-platform' ),
			);
			break;

		case 'needs_license':
			// Plan membership cannot be resolved without an activated license,
			// so the license screen is the only honest next step: a Plus
			// customer completes activation there, and everyone else sees their
			// options on it. The description names the required plan so a site
			// without Plus is not sent chasing a license it does not hold.
			$data = array(
				'empty_state_description' => __( 'Member Blogging is available with the Member Blogging add-on on the Plus plan. Activate your BuddyBoss license to install it.', 'buddyboss-platform' ),
				'button_label'            => __( 'Activate License', 'buddyboss-platform' ),
				'button_url'              => bb_member_blogging_get_license_url(),
			);
			break;

		case 'api_unavailable':
			// The license is active but the add-ons API did not answer, so plan
			// membership is genuinely unknown. Say so instead of guessing — an
			// "Upgrade to Scale" button shown to a customer who already has Scale
			// reads as a billing error on our side.
			$data = array(
				'empty_state_description' => __( 'We could not reach BuddyBoss to check which add-ons are in your plan. Check your connection and reload this page to try again.', 'buddyboss-platform' ),
				'button_label'            => __( 'Manage Add-ons', 'buddyboss-platform' ),
				'button_url'              => bp_get_admin_url( 'admin.php?page=buddyboss-addons' ),
			);
			break;

		case 'not_in_plan':
		default:
			$data = array(
				'empty_state_description' => __( 'Allow your community members to contribute by creating blogs for your site via the frontend blog creator form. Available with the Member Blogging add-on on the Plus plan.', 'buddyboss-platform' ),
				'button_label'            => __( 'Upgrade to Scale', 'buddyboss-platform' ),
				// Fallback for when the catalog has no entry for this panel — see
				// `button_url_from_catalog` in the defaults above.
				'button_url'              => 'https://www.buddyboss.com/pricing/',
				'button_url_from_catalog' => true,
				'button_target'           => '_blank',
			);
			break;
	}

	$data = array_merge( $defaults, $data );

	/**
	 * Filters the Member Blogs upsell empty-state payload.
	 *
	 * Lets sites re-point the call to action per add-on state — for example to
	 * send unlicensed installs to the pricing page instead of the license
	 * screen.
	 *
	 * @since BuddyBoss 3.3.0
	 *
	 * @param array  $data  Field data overrides.
	 * @param string $state Resolved add-on state.
	 */
	return apply_filters( 'bb_member_blogging_upsell_field_data', $data, $state );
}

/**
 * Apply the resolved add-on state to the Member Blogs upsell field.
 *
 * Runs inside `bb_admin_get_feature_settings` only, so the license/plan lookup
 * never touches ordinary admin page loads.
 *
 * @since BuddyBoss 3.3.0
 *
 * @param array  $field_data Formatted field data.
 * @param array  $field      Original registered field.
 * @param string $feature_id Feature ID.
 *
 * @return array Field data.
 */
function bb_member_blogging_format_upsell_field_data( $field_data, $field, $feature_id ) {
	if ( 'blogging' !== $feature_id ) {
		return $field_data;
	}

	$name = isset( $field['name'] ) ? $field['name'] : '';
	if ( 'bb_member_blogging_upsell' !== $name ) {
		return $field_data;
	}

	$overrides = bb_member_blogging_get_upsell_field_data();

	// The formatter sanitizes its own output before this filter runs, so these
	// late-added values are sanitized here instead.
	$field_data['empty_state_title']       = sanitize_text_field( $overrides['empty_state_title'] );
	$field_data['empty_state_description'] = sanitize_text_field( $overrides['empty_state_description'] );
	$field_data['button_label']            = ! empty( $overrides['button_label'] ) ? sanitize_text_field( $overrides['button_label'] ) : null;

	/*
	 * This filter runs after the formatter, so assigning `button_url` here would
	 * discard the catalog URL the formatter resolved. Take it back for the states
	 * whose button is a marketing upsell, and only those — `needs_license` and the
	 * add-ons-screen states must keep pointing inside this install.
	 */
	$button_url = ! empty( $overrides['button_url'] ) ? esc_url_raw( $overrides['button_url'] ) : null;

	if ( ! empty( $overrides['button_url_from_catalog'] ) && ! empty( $field_data['upgrade_catalog_url'] ) ) {
		$button_url = esc_url_raw( $field_data['upgrade_catalog_url'] );
	}

	$field_data['button_url']       = $button_url;
	$field_data['button_target']           = ! empty( $overrides['button_target'] ) ? sanitize_text_field( $overrides['button_target'] ) : null;
	$field_data['addon_action']            = ! empty( $overrides['addon_action'] ) ? sanitize_key( $overrides['addon_action'] ) : null;
	$field_data['addon_slug']              = ! empty( $overrides['addon_slug'] ) ? sanitize_key( $overrides['addon_slug'] ) : null;
	$field_data['addon_nonce_key']         = ! empty( $overrides['addon_nonce_key'] ) ? sanitize_text_field( $overrides['addon_nonce_key'] ) : null;
	$field_data['addon_busy_label']        = ! empty( $overrides['addon_busy_label'] ) ? sanitize_text_field( $overrides['addon_busy_label'] ) : null;

	return $field_data;
}
add_filter( 'bb_admin_settings_format_field_data', 'bb_member_blogging_format_upsell_field_data', 10, 3 );

/**
 * AJAX: activate the Member Blogging add-on.
 *
 * Deliberately not routed through Mothership's `mosh_addon_activate`: switching
 * on a plugin that already exists on disk is a pure WordPress operation and
 * must keep working when the license is not activated.
 *
 * @since BuddyBoss 3.3.0
 *
 * @return void
 */
function bb_member_blogging_ajax_activate_plugin() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'buddyboss-platform' ) ), 403 );
	}
	check_ajax_referer( 'bb_admin_settings', '_ajax_nonce' );

	if ( ! function_exists( 'activate_plugin' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$plugin_file = bb_member_blogging_plugin_file();

	if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
		wp_send_json_error( array( 'message' => __( 'The Member Blogging add-on is not installed.', 'buddyboss-platform' ) ) );
	}

	$result = activate_plugin( $plugin_file );

	if ( is_wp_error( $result ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Gated on WP_DEBUG; surfaces activation failures.
			error_log( sprintf( 'BB Member Blogging: failed to activate the add-on: %s', $result->get_error_message() ) );
		}
		wp_send_json_error(
			array(
				'message' => __( 'Plugin activation failed. Please try again.', 'buddyboss-platform' ),
				'detail'  => defined( 'WP_DEBUG' ) && WP_DEBUG ? $result->get_error_message() : '',
			)
		);
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_bb_member_blogging_activate_plugin', 'bb_member_blogging_ajax_activate_plugin' );

/**
 * AJAX: install and activate the Member Blogging add-on.
 *
 * The add-on ships from the BuddyBoss add-on server rather than wordpress.org,
 * so the authorized package URL can only be resolved through an activated
 * license. There is no generic `plugins_api()` fallback for that reason — a
 * wordpress.org lookup would only ever fail, and failing with a license message
 * is far more actionable.
 *
 * @since BuddyBoss 3.3.0
 *
 * @return void
 */
function bb_member_blogging_ajax_install_plugin() {
	if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'buddyboss-platform' ) ), 403 );
	}
	check_ajax_referer( 'bb_admin_settings', '_ajax_nonce' );

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/misc.php';
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

	if ( ! bb_member_blogging_mothership_available() ) {
		wp_send_json_error( array( 'message' => __( 'The Member Blogging add-on cannot be installed automatically on this site. Please install it manually.', 'buddyboss-platform' ) ) );
	}

	if ( ! bb_member_blogging_is_license_active() ) {
		wp_send_json_error(
			array(
				'message'     => __( 'Please activate your BuddyBoss license to install this add-on.', 'buddyboss-platform' ),
				'license_url' => bb_member_blogging_get_license_url(),
			)
		);
	}

	$product = \BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager::checkProductBySlug( bb_member_blogging_plugin_slug() );

	if ( empty( $product ) || empty( $product->_embedded->{'version-latest'}->url ) ) {
		wp_send_json_error(
			array(
				'message'     => __( 'The Member Blogging add-on is not available under your current license.', 'buddyboss-platform' ),
				'license_url' => bb_member_blogging_get_license_url(),
			)
		);
	}

	$skin     = new WP_Ajax_Upgrader_Skin();
	$upgrader = new Plugin_Upgrader( $skin );
	$result   = $upgrader->install( $product->_embedded->{'version-latest'}->url );

	if ( is_wp_error( $result ) || ! $result ) {
		$detail = is_wp_error( $result ) ? $result->get_error_message() : '';
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Gated on WP_DEBUG; surfaces installer failures.
			error_log( sprintf( 'BB Member Blogging: Plugin_Upgrader->install() failed: %s', $detail ) );
		}
		wp_send_json_error(
			array(
				'message' => __( 'Plugin installation failed. Please try again.', 'buddyboss-platform' ),
				'detail'  => defined( 'WP_DEBUG' ) && WP_DEBUG ? $detail : '',
			)
		);
	}

	// Prefer the basename the installer actually wrote — a repackaged zip can
	// unpack to a different folder than the product slug implies.
	$plugin_file = $upgrader->plugin_info();
	if ( empty( $plugin_file ) ) {
		$plugin_file = bb_member_blogging_plugin_file();
	}

	$activate = activate_plugin( $plugin_file );

	if ( is_wp_error( $activate ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Gated on WP_DEBUG; surfaces post-install activation failures.
			error_log( sprintf( 'BB Member Blogging: activate_plugin() failed after install: %s', $activate->get_error_message() ) );
		}
		wp_send_json_error(
			array(
				'message' => __( 'Plugin installed but activation failed. Please try again.', 'buddyboss-platform' ),
				'detail'  => defined( 'WP_DEBUG' ) && WP_DEBUG ? $activate->get_error_message() : '',
			)
		);
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_bb_member_blogging_install_plugin', 'bb_member_blogging_ajax_install_plugin' );
