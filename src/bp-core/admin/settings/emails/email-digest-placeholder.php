<?php
/**
 * Email Digest — the panel Platform shows when the add-on that implements it is absent.
 *
 * The digest itself ships as a module inside the `buddyboss-addons` plugin. Platform
 * registers this stand-in so the feature is visible, and its licensing story is legible,
 * on a site where that plugin is not installed, not activated, or installed on a plan
 * that does not include the digest. Without it the Emails tab simply has no Email Digest
 * entry and an admin has no way to discover the feature exists.
 *
 * Same shape as the Member Blogging upsell (bb-features/community/blogging/admin/) —
 * Platform owning the not-yet-available states for an add-on it does not ship.
 *
 * FIVE STATES, resolved from licence facts Platform can read on its own (the add-on's
 * licence manager is not available when the add-on is not loaded). Each one renders the
 * same shape — a single centred card naming the blocker and the one action that clears
 * it — and they differ only in that copy:
 *
 *   1. No activated licence      → activation is the only step that can resolve anything,
 *                                  so the card points at the licence screen.
 *   2. Licence, plan without it  → NOT a card. The whole digest form renders, locked and
 *                                  inert, so an admin can see what the upgrade buys; the
 *                                  upgrade path hangs off the section's UPGRADE START pill
 *                                  and the enable toggle's badge. (An earlier revision used
 *                                  a single upgrade card here — if you are looking for the
 *                                  `empty_state` + `upgrade_modal` pairing the AJAX
 *                                  formatter supports, nothing in Platform registers it
 *                                  today; that branch is an extension seam, not this
 *                                  panel's behavior.)
 *   3. Licence, plan with it,    → a card that fixes the plugin in place, because the
 *      add-on absent, off or        entitlement is fine and only the plugin is wrong.
 *      too old                      Telling this admin to upgrade would be wrong. Three
 *                                   variants, one per actual blocker: INSTALL when it is
 *                                   not on disk, ACTIVATE when it is but is switched off
 *                                   (both via the Mothership handlers the BuddyBoss
 *                                   Add-ons list screen uses), and UPDATE when it is
 *                                   running on a build that does not ship the digest
 *                                   module. The update runs through a Platform-owned
 *                                   handler, Mothership having no update action, and is
 *                                   withdrawn when nothing newer exists to install.
 *
 * A previous revision rendered state 2 as an inert replica of the add-on's settings form.
 * It read as a working panel — an admin on a plan without the digest saw a full set of
 * controls and had no way to tell they were scenery — and it committed Platform to
 * mirroring a field list it cannot see. One card, naming the blocker, replaced it.
 *
 * STANDING DOWN: whenever anything else has registered the real Email Digest panel — the
 * add-on module when entitled, the standalone QA plugin, or a future core-merged build —
 * this file registers nothing. The check asks the registry whether the panel exists
 * rather than testing for a particular plugin, so it holds for all three owners.
 *
 * NOTHING HERE IS WRITABLE. The card is the panel's only field, it is display-only, and it
 * sanitises to an empty string, so this panel can never write to state the add-on owns.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core\Administration
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plugin file (relative to the plugins directory) of the add-on that ships the digest.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string Plugin basename.
 */
function bb_email_digest_addon_plugin_file() {
	return 'buddyboss-addons/buddyboss-addons.php';
}

/**
 * Add-on slug, as the BuddyBoss add-on server knows it.
 *
 * This is what Mothership's install/activate handlers resolve the product by, so it has
 * to match the catalog entry rather than the folder on disk — they are the same string
 * today, and a repackaged build is free to change the folder without changing the slug.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string Plugin slug.
 */
function bb_email_digest_addon_plugin_slug() {
	return 'buddyboss-addons';
}

/**
 * Plan SKU prefixes whose licence includes the Email Digest.
 *
 * Mirrors the allowlist the add-on's licence manager declares for the module. Duplicated
 * deliberately: Platform has to answer "is this plan entitled?" on a site where the add-on
 * is not installed at all, so it cannot ask the add-on. Matched as PREFIXES, which is what
 * makes the list tolerant of per-site-count variants of the same plan.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array List of lowercase plan SKU prefixes.
 */
function bb_email_digest_required_plans() {
	// SINGLE SOURCE OF TRUTH when the add-on is installed. The list below is a copy, and a
	// copy drifts: the moment a new plan SKU is added to the add-on's registry and not here,
	// this panel starts telling entitled customers to upgrade (or, in the other direction,
	// promises a digest that will never load). The add-on's accessor returns the PLAN
	// requirement only — not its entitlement verdict, which also requires the module folder
	// on disk and would send a customer on a valid plan with a stripped build to pricing.
	if ( function_exists( 'bb_addons_module_plans' ) ) {
		$from_addon = bb_addons_module_plans( 'notification-digest' );
		if ( ! empty( $from_addon ) ) {
			/** This filter is documented in src/bp-core/admin/settings/emails/email-digest-placeholder.php */
			return (array) apply_filters( 'bb_email_digest_required_plans', $from_addon );
		}
	}

	// Fallback copy, for the case this panel exists FOR: a site where the add-on is not
	// installed at all, so there is nothing to ask. Keep in sync with the `$bundled_plans`
	// list in the add-on's class-bb-addons-license-manager.php.
	$plans = array(
		'bb-web-start',
		'bb-plus-web',
		'bb-lifetime-deal-10-sites',
		'bb-lifetime-deal-5-sites',
		'bb-lifetime-deal-2-sites',
		'bb-lifetime-deal-1-site',
		'bb-platform-pro-10-sites',
		'bb-platform-pro-5-sites',
		'bb-platform-pro-2-sites',
		'bb-platform-pro-1-site',
		'bb-web-20-sites',
		'bb-web-10-sites',
		'bb-web-5-sites',
		'bb-web-2-sites',
		'bb-web-1-site',
		'bb-bundle',
		'bb-web',
		'bb-web-plus',
	);

	/**
	 * Filters the plan SKU prefixes that include the Email Digest.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $plans Lowercase plan SKU prefixes.
	 */
	return (array) apply_filters( 'bb_email_digest_required_plans', $plans );
}

/**
 * Whether the BuddyBoss license is currently activated.
 *
 * Split out from the state resolver so the licence fact and the plan fact can be
 * exercised independently — a QA site can reach the plan branches without holding a
 * matching licence, and reaching the unlicensed screen never means deactivating a real
 * one. Fails closed: an unreadable licence layer counts as not activated.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool True when a licence is activated.
 */
function bb_email_digest_is_license_active() {
	$active = false;

	if ( class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector' ) ) {
		try {
			$connector = new \BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector();
			$active    = (bool) $connector->getLicenseActivationStatus();
		} catch ( \Exception $e ) {
			$active = false;
		}
	}

	/**
	 * Filters whether the licence counts as activated for the Email Digest panel.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param bool $active Whether a licence is activated.
	 */
	return (bool) apply_filters( 'bb_email_digest_is_license_active', $active );
}

/**
 * The plan SKU the activated licence carries.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string Lowercase SKU, or an empty string when it cannot be determined.
 */
function bb_email_digest_licensed_plan_sku() {
	$sku = '';

	if ( class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector' ) ) {
		try {
			$connector = new \BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector();
			$sku       = strtolower( (string) $connector->getCurrentPluginId() );
		} catch ( \Exception $e ) {
			$sku = '';
		}
	}

	/**
	 * Filters the plan SKU used to decide Email Digest entitlement.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $sku Lowercase plan SKU.
	 */
	return (string) apply_filters( 'bb_email_digest_licensed_plan_sku', $sku );
}

/**
 * Resolve which of the three stand-in states applies.
 *
 * Fails closed on every ambiguity: an unreadable licence layer, an unactivated licence and
 * an undeterminable plan SKU all resolve to a state that does not claim entitlement. The
 * cost of being wrong in that direction is an upgrade prompt shown to someone who does not
 * need one; the other direction promises a feature that will not appear.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string One of 'needs_license', 'not_in_plan', 'addon_inactive',
 *                'addon_not_installed', 'addon_outdated'.
 */
function bb_email_digest_get_placeholder_state() {
	if ( ! bb_email_digest_is_license_active() ) {
		return 'needs_license';
	}

	$sku = bb_email_digest_licensed_plan_sku();

	if ( '' === $sku ) {
		return 'not_in_plan';
	}

	$in_plan = false;
	foreach ( bb_email_digest_required_plans() as $prefix ) {
		$prefix = strtolower( trim( (string) $prefix ) );
		if ( '' !== $prefix && 0 === strpos( $sku, $prefix ) ) {
			$in_plan = true;
			break;
		}
	}

	if ( ! $in_plan ) {
		return 'not_in_plan';
	}

	// Entitled by plan, yet the real panel is absent — the caller established that before
	// calling. Two explanations remain and they need different screens.
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( ! is_plugin_active( bb_email_digest_addon_plugin_file() ) ) {
		// The plugin that carries the digest is not running at all. Nothing about the
		// licence is wrong, so the fix is the plugin, not the plan — but WHICH fix
		// depends on whether it is on disk. A plugin sitting there switched off is one
		// click away; one that was never installed has to be fetched from the add-on
		// server first, which is a different button and a different handler.
		return file_exists( WP_PLUGIN_DIR . '/' . bb_email_digest_addon_plugin_file() )
			? 'addon_inactive'
			: 'addon_not_installed';
	}

	// The plugin IS running and still registered nothing, so this build of it does not
	// carry the digest module — the add-on ships module folders per build, and a build
	// without that folder is a build without the feature.
	//
	// This is NOT an upgrade case, and it used to be treated as one. The plan already
	// includes the digest; only the installed build is behind. Showing "Upgrade to Start"
	// to a Start customer reads as a billing error on our side, so this gets a state of
	// its own and a card that offers the update instead.
	return 'addon_outdated';
}

/**
 * Version of the add-on currently on disk.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string Version string, or an empty string when it cannot be read.
 */
function bb_email_digest_addon_installed_version() {
	if ( ! function_exists( 'get_plugin_data' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$file = WP_PLUGIN_DIR . '/' . bb_email_digest_addon_plugin_file();

	if ( ! file_exists( $file ) ) {
		return '';
	}

	// No translation pass and no markup stripping: this value is only ever compared with
	// version_compare(), never displayed.
	$data = get_plugin_data( $file, false, false );

	return isset( $data['Version'] ) ? (string) $data['Version'] : '';
}

/**
 * The newest add-on release the licence can reach, and where to get it.
 *
 * REMOTE CALL. Never invoke this from feature registration, which runs on every admin
 * request — it belongs on the lazy `bb_admin_settings_format_field_data` path and inside
 * the update handler, both of which only run for this panel.
 *
 * The version lives at `_embedded.version-latest.number`; the product record carries no
 * top-level version field, and reaching for `->version` silently yields null.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array {
 *     @type string $version Release number, empty when unresolved.
 *     @type string $url     Package URL, empty when unresolved.
 * }
 */
function bb_email_digest_addon_latest_release() {
	$none = array(
		'version' => '',
		'url'     => '',
	);

	if ( ! class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager' ) ) {
		return $none;
	}

	try {
		$product = \BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager::checkProductBySlug( bb_email_digest_addon_plugin_slug() );
	} catch ( \Exception $e ) {
		return $none;
	}

	if ( empty( $product->_embedded->{'version-latest'} ) ) {
		return $none;
	}

	$release = $product->_embedded->{'version-latest'};

	return array(
		'version' => isset( $release->number ) ? (string) $release->number : '',
		'url'     => isset( $release->url ) ? (string) $release->url : '',
	);
}

/**
 * Whether a newer add-on build than the one on disk is available to this licence.
 *
 * REMOTE CALL — see bb_email_digest_addon_latest_release().
 *
 * Fails closed: an unreadable installed version or an unresolved release means "no",
 * because the alternative is offering an Update button that cannot change anything.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool True when an update is available.
 */
function bb_email_digest_addon_update_available() {
	$installed = bb_email_digest_addon_installed_version();
	$latest    = bb_email_digest_addon_latest_release();

	$available = '' !== $installed
		&& '' !== $latest['version']
		&& version_compare( $latest['version'], $installed, '>' );

	/**
	 * Filters whether an add-on update is available for the Email Digest panel.
	 *
	 * Same reason the resolved state is filterable: both branches of the update card have
	 * to be reachable on a site whose installed build already matches the catalog, and
	 * publishing a release is not a reasonable prerequisite for testing a settings panel.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param bool   $available Whether a newer build is available.
	 * @param string $installed Installed version, empty when unreadable.
	 * @param array  $latest    Latest release, with `version` and `url` keys.
	 */
	return (bool) apply_filters( 'bb_email_digest_addon_update_available', $available, $installed, $latest );
}

/**
 * Filterable wrapper around the resolved placeholder state.
 *
 * Kept separate from the resolver so QA and staging can exercise every branch without
 * holding a matching licence — reaching the unlicensed screen on a licensed site otherwise
 * means deactivating a real licence, which is not something to ask of anyone testing a
 * settings panel.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string Resolved placeholder state.
 */
function bb_email_digest_placeholder_state() {
	/**
	 * Filters the resolved Email Digest placeholder state.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $state One of 'needs_license', 'not_in_plan', 'addon_inactive',
	 *                      'addon_not_installed', 'addon_outdated'.
	 */
	return apply_filters( 'bb_email_digest_placeholder_state', bb_email_digest_get_placeholder_state() );
}

/**
 * Content for the Email Digest upgrade dialog.
 *
 * Uses the short key names a `pro_notice.modal` is registered with, not the field-upgrades
 * catalog's `upgrade_*` names. A catalog entry for this panel supersedes the whole payload,
 * which is what lets marketing retarget copy, art and URL without a release.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array Modal payload.
 */
function bb_email_digest_upgrade_modal_payload() {
	return array(
		'label'       => __( 'Email Digest', 'buddyboss' ),
		'title'       => __( 'Simplify Email Notifications', 'buddyboss' ),
		'description' => __( 'Combine multiple community notifications into one organized daily or weekly email while keeping in-app notifications unchanged.', 'buddyboss' ),
		'tier'        => 'start',
		'url'         => 'https://buddyboss.com/pricing?utm_source=product&utm_medium=platform-plugin&utm_campaign=email-digest-upgrade&utm_content=emails-settings',
		// Platform's own copy of the hero, at the design's native 600x337. The add-on
		// ships the same file, but this panel exists precisely for sites where the add-on
		// is not on disk, so it cannot borrow that one.
		'image_url'   => buddypress()->plugin_url . 'bp-core/images/email-digest-upgrade.png',
	);
}

/**
 * Register the stand-in Email Digest panel.
 *
 * Runs at priority 35, behind every real registration (the add-on registers at 30), so the
 * stand-down check below sees a settled registry.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_admin_settings_register_email_digest_placeholder() {
	if ( ! is_admin() || ! function_exists( 'bb_register_side_panel' ) || ! function_exists( 'bb_feature_registry' ) ) {
		return;
	}

	// Something already owns this panel — the entitled add-on module, the standalone QA
	// plugin, or a core-merged build. Asking the registry rather than testing for a
	// specific plugin keeps this correct for all three.
	$panels = bb_feature_registry()->bb_get_side_panels( 'emails' );
	if ( isset( $panels['email_digest'] ) ) {
		return;
	}

	$state  = bb_email_digest_placeholder_state();
	$locked = 'not_in_plan' === $state;

	bb_register_side_panel(
		'emails',
		'email_digest',
		array(
			'title' => __( 'Email Digest', 'buddyboss' ),
			'icon'  => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-envelope-simple',
			),
			'order' => 20,
		)
	);

	// The locked form is the one state that carries a section description and the header
	// pill. Every other state is a single centred card, and a description above one would
	// be a second, competing explanation of the same blocker.
	$section_args = array(
		'title'    => __( 'Email Digest', 'buddyboss' ),
		'order'    => 10,
		'help_url' => '659636',
	);

	if ( $locked ) {
		$section_args['description'] = __( 'Group several notification emails into one daily or weekly email instead of sending each one separately. You choose which notifications can be grouped. Each member chooses daily, weekly, or no digest in their own notification settings.', 'buddyboss' );
		$section_args['pro_notice']  = array(
			'show'       => true,
			'badge_text' => __( 'UPGRADE START', 'buddyboss' ),
			'badge_icon' => 'bb-icons-rl-crown-simple',
			'link_url'   => 'https://buddyboss.com/pricing?utm_source=product&utm_medium=platform-plugin&utm_campaign=email-digest-upgrade&utm_content=emails-settings',
			'modal'      => bb_email_digest_upgrade_modal_payload(),
		);
	}

	bb_register_feature_section( 'emails', 'email_digest', 'email_digest', $section_args );

	if ( $locked ) {
		// A licence holder on a plan without the digest gets the real form, locked, so
		// they can see what the upgrade buys. Every other state is a blocker they can
		// clear themselves, and a card naming that blocker serves them better.
		bb_admin_settings_register_email_digest_locked_form();
		return;
	}

	bb_admin_settings_register_email_digest_card( $state );
}
add_action( 'bb_register_features', 'bb_admin_settings_register_email_digest_placeholder', 35 );

/**
 * Register the card for the resolved state.
 *
 * Every state renders the same centred card and differs only in the copy and the one
 * action offered. Each heading names the BLOCKER rather than the feature: "Email Digest"
 * over a locked card on the Email Digest panel tells an admin nothing they do not already
 * know, while "Upgrade Required" tells them why the panel is empty.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $state Resolved placeholder state.
 * @return void
 */
function bb_admin_settings_register_email_digest_card( $state ) {
	switch ( $state ) {
		case 'addon_inactive':
			// Entitled and already on disk, so an upgrade prompt would be flatly wrong
			// and a trip to plugins.php is a detour. `addon_action` + `addon_slug` make
			// React render <AddonActivateButton>, which switches the plugin on over AJAX
			// and reloads — the panel then re-renders as the add-on's real settings,
			// because this whole file stands down once the real panel is registered.
			//
			// Mothership's own handler, the one the BuddyBoss Add-ons list screen uses,
			// so this button behaves identically to the one there. `addon_nonce_key` is
			// therefore left unset: AddonActivateButton defaults to `addonNonce`
			// (`mosh_addons`), which is what that handler verifies.
			//
			// `button_url` never renders while `addon_action` is set — the button branch
			// wins — but it is kept as the destination if the action is ever filtered
			// away, so the card degrades to a working link rather than dead text.
			$card = array(
				'empty_state_title'       => __( 'Add-on Not Active', 'buddyboss' ),
				'empty_state_description' => __( 'Email Digest is included in your plan. Activate the BuddyBoss Add-ons plugin to configure it.', 'buddyboss' ),
				'button_label'            => __( 'Activate', 'buddyboss' ),
				'button_url'              => admin_url( 'plugins.php' ),
				'addon_action'            => 'mosh_addon_activate',
				'addon_slug'              => bb_email_digest_addon_plugin_slug(),
				'addon_busy_label'        => __( 'Activating…', 'buddyboss' ),
			);
			break;

		case 'addon_outdated':
			// Entitled, add-on running, but this build does not ship the digest module.
			// The fix is a plugin update, so that is what the card offers — through a
			// Platform-owned handler rather than Mothership, which has activate,
			// deactivate and install actions but no update action.
			//
			// Whether an update actually EXISTS is not settled here: answering that
			// needs a remote lookup, and this function runs during feature registration
			// on every admin request. bb_email_digest_soften_stale_update_card() below
			// re-decides it lazily, on the AJAX path that only this panel triggers, and
			// replaces the button when there is nothing newer to install.
			$card = array(
				'empty_state_title'       => __( 'Update Required', 'buddyboss' ),
				'empty_state_description' => __( 'Email Digest is included in your plan, but the installed version of the BuddyBoss Add-ons plugin does not provide it. Update the add-on to continue.', 'buddyboss' ),
				'button_label'            => __( 'Update Add-on', 'buddyboss' ),
				'button_url'              => admin_url( 'update-core.php' ),
				'addon_action'            => 'bb_email_digest_update_addon',
				'addon_slug'              => bb_email_digest_addon_plugin_slug(),
				'addon_nonce_key'         => 'ajaxNonce',
				'addon_busy_label'        => __( 'Updating…', 'buddyboss' ),
			);
			break;

		case 'addon_not_installed':
			// Entitled, but the add-on was never installed. Mothership fetches the
			// authorized package from the add-on server and activates it in one step,
			// which is why this is a single "Install & Activate" rather than two
			// buttons — an installed-but-inactive add-on is no use to anyone.
			//
			// This path genuinely requires the licence Mothership resolves the download
			// URL with, and reaching this state already means the plan includes the
			// digest, so that requirement costs nothing here.
			$card = array(
				'empty_state_title'       => __( 'Add-on Not Installed', 'buddyboss' ),
				'empty_state_description' => __( 'Email Digest is included in your plan. Install the BuddyBoss Add-ons plugin to configure it.', 'buddyboss' ),
				'button_label'            => __( 'Install & Activate', 'buddyboss' ),
				'button_url'              => admin_url( 'plugins.php' ),
				'addon_action'            => 'mosh_addon_install',
				'addon_slug'              => bb_email_digest_addon_plugin_slug(),
				'addon_busy_label'        => __( 'Installing…', 'buddyboss' ),
			);
			break;

		case 'needs_license':
		default:
			// Plan membership cannot be resolved without an activated licence, so
			// activation is the only honest next step — naming a plan here would be a
			// guess, and guessing wrong sends a paying customer to buy what they hold.
			$card = array(
				'empty_state_title'       => __( 'License Activation Required', 'buddyboss' ),
				'empty_state_description' => __( 'Combine multiple notifications into a single daily or weekly email to reduce inbox clutter. Activate your license to unlock Email Digest.', 'buddyboss' ),
				'button_label'            => __( 'Activate License', 'buddyboss' ),
				'button_url'              => bp_get_admin_url( 'admin.php?page=buddyboss-license' ),
			);
			break;
	}

	bb_register_feature_field(
		'emails',
		'email_digest',
		'email_digest',
		array_merge(
			array(
				'name'              => '_bb_email_digest_placeholder_card',
				'label'             => '',
				'type'              => 'empty_state',
				'icon'              => 'bb-icons-rl bb-icons-rl-envelope-simple',
				'button_target'     => '_self',
				'sanitize_callback' => '__return_empty_string',
				'order'             => 10,
			),
			$card
		)
	);
}

/**
 * Register the Notifications-tab signpost pointing at the Email Digest panel.
 *
 * The digest's controls live under Emails, so the Notifications tab carries a cross-link
 * to them. Registered here only when nothing else has — the add-on registers its own from
 * teardown.php on any site where it is installed.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_admin_settings_register_email_digest_signpost() {
	if (
		! is_admin()
		|| ! function_exists( 'bb_register_side_panel' )
		|| ! function_exists( 'bb_get_feature_settings_url' )
		|| function_exists( 'bb_notification_digest_register_notifications_link' )
	) {
		return;
	}

	bb_register_side_panel(
		'notifications',
		'email_digest_link',
		array(
			'title'        => __( 'Email Digest', 'buddyboss' ),
			'icon'         => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-envelope-simple',
			),
			'order'        => 100,
			'divider'      => true,
			'external_url' => bb_get_feature_settings_url( 'emails', 'email_digest' ),
		)
	);
}
add_action( 'bb_notifications_after_register_settings_fields', 'bb_admin_settings_register_email_digest_signpost' );

/**
 * Replace the Update button when there is nothing newer to install.
 *
 * Runs on the field-formatting filter, which fires only while building the AJAX response
 * for a settings panel — the one place a remote version lookup is affordable. Feature
 * registration runs on every admin request and must stay local, so the card is registered
 * optimistically and corrected here.
 *
 * An admin already on the newest build cannot fix this by updating. Offering the button
 * anyway would produce a click that visibly fails, so the card drops to a plain link to
 * the add-ons screen and says what is actually true.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array  $field_data Formatted field data bound for React.
 * @param array  $field      Registered field args.
 * @param string $feature_id Feature the field belongs to.
 * @return array Field data.
 */
function bb_email_digest_soften_stale_update_card( $field_data, $field, $feature_id ) {
	if (
		'emails' !== $feature_id
		|| ! isset( $field['name'], $field_data['addon_action'] )
		|| '_bb_email_digest_placeholder_card' !== $field['name']
		|| 'bb_email_digest_update_addon' !== $field_data['addon_action']
	) {
		return $field_data;
	}

	if ( bb_email_digest_addon_update_available() ) {
		return $field_data;
	}

	$field_data['empty_state_title']       = __( 'Not Available in This Version', 'buddyboss' );
	$field_data['empty_state_description'] = __( 'Email Digest is included in your plan, but the installed version of the BuddyBoss Add-ons plugin does not provide it. Check for a plugin update.', 'buddyboss' );
	$field_data['button_label']            = __( 'Go to Updates', 'buddyboss' );
	// The WordPress updates screen rather than the add-ons screen: this state is reached
	// when no newer build is known, and that answer comes from an update check that may
	// simply be stale. "Check again" there re-runs it, which is the one action on this
	// site that can turn this card into an Update button.
	$field_data['button_url'] = admin_url( 'update-core.php' );

	// Clearing the action is what demotes the control from <AddonActivateButton> back to
	// a plain link — React picks the button branch purely on these two being present.
	$field_data['addon_action']     = null;
	$field_data['addon_slug']       = null;
	$field_data['addon_nonce_key']  = null;
	$field_data['addon_busy_label'] = null;

	return $field_data;
}
add_filter( 'bb_admin_settings_format_field_data', 'bb_email_digest_soften_stale_update_card', 10, 3 );

/**
 * AJAX: update the add-on that carries the digest to its newest licensed build.
 *
 * Mothership owns activate, deactivate and install but has no update action, so this is
 * Platform-owned and verifies the `bb_admin_settings` nonce (`ajaxNonce`) rather than
 * `mosh_addons` — which is why the field registers `addon_nonce_key`.
 *
 * Installs the package URL from the product record with `overwrite_package`, rather than
 * going through Plugin_Upgrader::upgrade(). upgrade() reads the `update_plugins` transient,
 * which on a Mothership-sourced add-on is frequently missing an entry altogether, and an
 * updater that quietly does nothing is worse than one that fails loudly. Overwriting in
 * place is what WordPress itself does when a zip is uploaded over an installed plugin;
 * the basename does not change, so the plugin stays active across it.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_email_digest_ajax_update_addon() {
	if ( ! current_user_can( 'update_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'buddyboss' ) ), 403 );
	}
	check_ajax_referer( 'bb_admin_settings', '_ajax_nonce' );

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/misc.php';
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

	$plugin_file = bb_email_digest_addon_plugin_file();

	if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
		wp_send_json_error( array( 'message' => __( 'The BuddyBoss Add-ons plugin is not installed.', 'buddyboss' ) ) );
	}

	$installed = bb_email_digest_addon_installed_version();
	$latest    = bb_email_digest_addon_latest_release();

	if ( '' === $latest['url'] ) {
		wp_send_json_error(
			array(
				'message'     => __( 'The BuddyBoss Add-ons plugin is not available under your current license.', 'buddyboss' ),
				'license_url' => bp_get_admin_url( 'admin.php?page=buddyboss-license' ),
			)
		);
	}

	// Refuse rather than reinstall the same build. The card is supposed to have been
	// softened before it got here, so reaching this means the catalog moved between the
	// page render and the click — and reinstalling an identical build would look like a
	// successful update that changed nothing.
	if ( '' !== $installed && '' !== $latest['version'] && ! version_compare( $latest['version'], $installed, '>' ) ) {
		wp_send_json_error(
			array(
				'message' => sprintf(
					/* translators: %s: installed plugin version. */
					__( 'The BuddyBoss Add-ons plugin is already up to date (version %s). Email Digest is not included in this version.', 'buddyboss' ),
					$installed
				),
			)
		);
	}

	$skin     = new WP_Ajax_Upgrader_Skin();
	$upgrader = new Plugin_Upgrader( $skin );
	$result   = $upgrader->install( $latest['url'], array( 'overwrite_package' => true ) );

	if ( is_wp_error( $result ) || ! $result ) {
		$detail = is_wp_error( $result ) ? $result->get_error_message() : '';
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Gated on WP_DEBUG; surfaces updater failures.
			error_log( sprintf( 'BB Email Digest: Plugin_Upgrader->install() failed updating the add-on: %s', $detail ) );
		}
		wp_send_json_error(
			array(
				'message' => __( 'Plugin update failed. Please try again.', 'buddyboss' ),
				'detail'  => defined( 'WP_DEBUG' ) && WP_DEBUG ? $detail : '',
			)
		);
	}

	// An overwrite leaves `active_plugins` untouched, so the add-on is normally still
	// active. Re-activate only if something dropped it, and treat an already-active
	// plugin as success rather than an error.
	if ( ! is_plugin_active( $plugin_file ) ) {
		$activate = activate_plugin( $plugin_file );

		if ( is_wp_error( $activate ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Gated on WP_DEBUG; surfaces post-update activation failures.
				error_log( sprintf( 'BB Email Digest: activate_plugin() failed after update: %s', $activate->get_error_message() ) );
			}
			wp_send_json_error(
				array(
					'message' => __( 'Plugin updated but activation failed. Please activate it from the Plugins screen.', 'buddyboss' ),
					'detail'  => defined( 'WP_DEBUG' ) && WP_DEBUG ? $activate->get_error_message() : '',
				)
			);
		}
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_bb_email_digest_update_addon', 'bb_email_digest_ajax_update_addon' );

/**
 * Register the inert replica of the digest settings form.
 *
 * DUPLICATION, KNOWINGLY: these mirror the add-on's real fields. Platform cannot read them
 * from the add-on — the whole point of this panel is the case where the add-on is not on
 * disk — so a copy is the only option. Two things keep the copy from mattering: it is
 * display-only, and it disappears the moment the real panel registers. If the add-on's form
 * changes, this replica should be updated to match, but a drift shows as stale copy on an
 * upgrade screen, never as a functional defect.
 *
 * Every field is inert twice over: `pro_only` dims the control and forces its off-state,
 * and `__return_empty_string` means a crafted POST writes nothing. The names are local to
 * this panel and deliberately do NOT match the add-on's option keys, so this screen cannot
 * write to anything the digest reads.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_admin_settings_register_email_digest_locked_form() {
	$modal = bb_email_digest_upgrade_modal_payload();

	/**
	 * Register one inert field.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args Field registration args.
	 * @return void
	 */
	$register = function ( $args ) {
		$args['pro_only'] = true;

		// Three separate jobs, and all three are needed.
		//
		// `pro_only` dims the row and forces the control's off-state, but it does that
		// through CSS `pointer-events: none` — which stops a mouse and nothing else. A
		// keyboard user can still Tab onto the control and operate it, and assistive
		// technology is never told the control is unavailable.
		//
		// `disabled` is what actually makes the control inert and announces it as such.
		//
		// `__return_empty_string` is the half that survives the browser entirely: a
		// crafted POST bypasses both of the above, so refusing the value server-side is
		// the only real guarantee that a locked panel cannot be written to.
		$args['disabled']          = true;
		$args['sanitize_callback'] = '__return_empty_string';

		bb_register_feature_field( 'emails', 'email_digest', 'email_digest', $args );
	};

	// FIELD 1 — the enable toggle, and the only row carrying the START badge. Set
	// explicitly rather than left to the formatter's auto-compute for `pro_only` fields:
	// that computes from Platform Pro's licence, which says nothing about whether this
	// plan includes the digest.
	$register(
		array(
			'name'        => '_bb_email_digest_placeholder_enabled',
			'label'       => __( 'Email Digest', 'buddyboss' ),
			'type'        => 'toggle',
			'description' => __( 'Enable Email Digest', 'buddyboss' ),
			'help_text'   => __( 'Group several notification emails into one daily or weekly email instead of sending each one separately. You choose which notifications can be grouped. Each member chooses daily, weekly, or no digest in their own notification settings.', 'buddyboss' ),
			'default'     => 0,
			'order'       => 10,
			'pro_notice'  => array(
				'show'       => true,
				'badge_text' => __( 'START', 'buddyboss' ),
				'badge_icon' => 'bb-icons-rl-crown-simple',
				'link_icon'  => 'bb-icons-rl bb-icons-rl-play',
				'link_url'   => 'https://www.buddyboss.com/pricing/',
				'modal'      => $modal,
			),
		)
	);

	// FIELD 2 — default cadence.
	$register(
		array(
			'name'      => '_bb_email_digest_placeholder_frequency',
			'label'     => __( 'Default Frequency', 'buddyboss' ),
			'type'      => 'select',
			'help_text' => __( 'New members start on this schedule. Existing members keep getting instant emails until they choose daily or weekly. Members can change their choice any time in their notification settings.', 'buddyboss' ),
			'default'   => 'daily',
			'options'   => array(
				array(
					'label' => __( 'Daily', 'buddyboss' ),
					'value' => 'daily',
				),
				array(
					'label' => __( 'Weekly', 'buddyboss' ),
					'value' => 'weekly',
				),
			),
			'order'     => 20,
		)
	);

	// FIELDS 3-4 — day and time are one decision, so they share a left-column label.
	// $wp_locale is not instantiated this early, so weekday names are our own strings.
	$day_options = array();
	foreach (
		array(
			__( 'Sunday', 'buddyboss' ),
			__( 'Monday', 'buddyboss' ),
			__( 'Tuesday', 'buddyboss' ),
			__( 'Wednesday', 'buddyboss' ),
			__( 'Thursday', 'buddyboss' ),
			__( 'Friday', 'buddyboss' ),
			__( 'Saturday', 'buddyboss' ),
		) as $index => $day_label
	) {
		$day_options[] = array(
			'label' => $day_label,
			'value' => (string) $index,
		);
	}

	$register(
		array(
			'name'      => '_bb_email_digest_placeholder_send_day',
			'label'     => __( 'Send Schedule', 'buddyboss' ),
			'type'      => 'select',
			'help_text' => __( 'The day weekly digests go out. Daily digests are not affected.', 'buddyboss' ),
			'default'   => '1',
			'options'   => $day_options,
			'order'     => 30,
			'group'     => array( 'key' => 'send_schedule' ),
		)
	);

	$time_options = array();
	for ( $hour = 0; $hour < 24; $hour++ ) {
		foreach ( array( '00', '30' ) as $minute ) {
			$stamp          = sprintf( '%02d:%s', $hour, $minute );
			$time_options[] = array(
				'label' => $stamp,
				'value' => $stamp,
			);
		}
	}

	$register(
		array(
			'name'      => '_bb_email_digest_placeholder_send_time',
			'label'     => __( 'Send Schedule', 'buddyboss' ),
			'type'      => 'select',
			'help_text' => __( 'The time digests start going out, in your site\'s timezone. They are sent in batches, so on a large site some members get theirs a little later.', 'buddyboss' ),
			'default'   => '09:00',
			'options'   => $time_options,
			'order'     => 40,
			'group'     => array( 'key' => 'send_schedule' ),
		)
	);

	// FIELDS 5-7 — which notification sources the digest may batch.
	$register(
		array(
			'name'        => '_bb_email_digest_placeholder_source_groups',
			'label'       => __( 'Group Updates', 'buddyboss' ),
			'type'        => 'toggle',
			'description' => __( 'Include group updates in the digest', 'buddyboss' ),
			'help_text'   => __( 'New posts in groups the member is subscribed to, and changes to group details. Turn this off to send these emails immediately instead.', 'buddyboss' ),
			'default'     => 0,
			'order'       => 50,
		)
	);

	$register(
		array(
			'name'        => '_bb_email_digest_placeholder_source_social',
			'label'       => __( 'Social', 'buddyboss' ),
			'type'        => 'toggle',
			'description' => __( 'Include social notifications in the digest', 'buddyboss' ),
			'help_text'   => __( 'Mentions, comments and replies on posts, new posts from people the member follows, and new followers. Turn this off to send these emails immediately instead.', 'buddyboss' ),
			'default'     => 0,
			'order'       => 60,
		)
	);

	$register(
		array(
			'name'        => '_bb_email_digest_placeholder_source_messages',
			'label'       => __( 'Private Messages', 'buddyboss' ),
			'type'        => 'toggle',
			'description' => __( 'Include private messages in the digest', 'buddyboss' ),
			'help_text'   => __( 'Unread private messages and group messages. Members will not hear about a new message until their next digest. Turn this off to send these emails immediately instead.', 'buddyboss' ),
			'default'     => 0,
			'order'       => 70,
		)
	);

	// FIELD 8 — the intro copy, sharing a label with the templates notice that follows it.
	$register(
		array(
			'name'        => '_bb_email_digest_placeholder_intro',
			'label'       => __( 'Digest Heading', 'buddyboss' ),
			'type'        => 'textarea',
			'placeholder' => __( 'Add a heading and a short intro for your digest emails', 'buddyboss' ),
			'help_text'   => __( 'A short welcome message shown at the top of every digest, above the list of notifications. Leave it empty to show no introduction.', 'buddyboss' ),
			'default'     => '',
			'order'       => 80,
			'group'       => array( 'key' => 'digest_heading' ),
		)
	);

	// FIELD 8 on the live panel — the "Customize the digest design and wording under
	// Emails" notice — is deliberately NOT registered here, for the same reason the
	// last-run row is omitted: it is advice about a mail this plan cannot send.
	//
	// It also could not be locked even if we wanted it. The $register closure marks every
	// field pro_only + disabled + unwritable, and a `notice` honours none of the three:
	// SettingsForm renders its description through dangerouslySetInnerHTML and never
	// consumes `disabled`, and `pro_only` is not read by the renderer at all. Its anchor
	// to the Emails screen therefore stayed fully clickable while every control beside it
	// was inert — the one operable thing on a panel whose whole point is that nothing on
	// it works yet. Omitting the row is the fix; do not re-add it "locked".

	// FIELD 9 — the test-send row. Rendered, but pointed nowhere: the handler that would
	// service it ships with the add-on, so on this panel it can only ever be scenery.
	$register(
		array(
			'name'         => '_bb_email_digest_placeholder_test',
			'label'        => __( 'Test Digest', 'buddyboss' ),
			'type'         => 'manage_link',
			'description'  => __( 'Sends a preview digest to your own email address, built from the notifications you have waiting. This does not affect members and does not clear your waiting notifications.', 'buddyboss' ),
			'manage_url'   => '',
			'manage_label' => __( 'Send Test Digest', 'buddyboss' ),
			'manage_icon'  => 'bb-icons-rl bb-icons-rl-paper-plane-tilt',
			'order'        => 110,
		)
	);
}
