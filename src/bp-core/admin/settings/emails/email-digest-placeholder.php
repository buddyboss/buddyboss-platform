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
 * THREE STATES, resolved from licence facts Platform can read on its own (the add-on's
 * licence manager is not available when the add-on is not loaded):
 *
 *   1. No activated licence      → a card explaining the blocker, linking to activation.
 *   2. Licence, plan without it  → the whole settings form, rendered and inert, with the
 *                                  upgrade path attached. An admin who has already paid
 *                                  for something deserves to see what the upgrade buys
 *                                  rather than a one-line teaser.
 *   3. Licence, plan with it,    → a card pointing at the plugin, because the entitlement
 *      add-on not active           is fine and only the switch is off. Telling this admin
 *                                  to upgrade would be wrong.
 *
 * STANDING DOWN: whenever anything else has registered the real Email Digest panel — the
 * add-on module when entitled, the standalone QA plugin, or a future core-merged build —
 * this file registers nothing. The check asks the registry whether the panel exists
 * rather than testing for a particular plugin, so it holds for all three owners.
 *
 * THE FIELDS ARE FAKE. They render the design and nothing else: every one is display-only,
 * sanitises to an empty string, and carries a name of its own that no real digest option
 * is keyed on, so this panel can never write to state the add-on owns. They necessarily
 * duplicate the add-on's field list; see the note above the field registration.
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
 * @return string One of 'needs_license', 'not_in_plan', 'addon_inactive'.
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
		// licence is wrong, so the fix is the plugin, not the plan.
		return 'addon_inactive';
	}

	// The plugin IS running and still registered nothing, so this build of it does not
	// carry the digest module — the add-on ships module folders per build, and a build
	// without that folder is a build without the feature. An admin cannot switch on what
	// is not there, and the route to it is a plan whose build includes it, so this lands
	// on the upgrade screen rather than a card telling them to activate a plugin that is
	// already active.
	return 'not_in_plan';
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
	 * @param string $state One of 'needs_license', 'not_in_plan', 'addon_inactive'.
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

	$section_args = array(
		'title'    => __( 'Email Digest', 'buddyboss' ),
		'order'    => 10,
		'help_url' => '659636',
	);

	if ( $locked ) {
		// Only the locked form carries the section description and the header pill: the
		// two card states are a single centred message, and a description above them
		// would be a second, competing explanation.
		$section_args['description'] = __( 'Combine notification emails into a single daily or weekly digest for members who choose it. Reliable inbox delivery requires DKIM-aligned sending from your domain — see the documentation.', 'buddyboss' );
		$section_args['pro_notice']  = array(
			'show'       => true,
			'badge_text' => __( 'UPGRADE START', 'buddyboss' ),
			'badge_icon' => 'bb-icons-rl-crown-simple',
			'link_url'   => 'https://buddyboss.com/pricing?utm_source=product&utm_medium=platform-plugin&utm_campaign=email-digest-upgrade&utm_content=emails-settings',
			'modal'      => bb_email_digest_upgrade_modal_payload(),
		);
	}

	bb_register_feature_section( 'emails', 'email_digest', 'email_digest', $section_args );

	if ( ! $locked ) {
		bb_admin_settings_register_email_digest_card( $state );
		return;
	}

	bb_admin_settings_register_email_digest_locked_form();
}
add_action( 'bb_register_features', 'bb_admin_settings_register_email_digest_placeholder', 35 );

/**
 * Register the single-card state (no licence, or entitled with the add-on switched off).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $state Resolved placeholder state.
 * @return void
 */
function bb_admin_settings_register_email_digest_card( $state ) {
	if ( 'addon_inactive' === $state ) {
		// Entitled, so an upgrade prompt would be flatly wrong. Point at the plugins
		// screen, which is the one action that actually unblocks this admin.
		$card = array(
			'empty_state_title'       => __( 'Add-on Not Active', 'buddyboss' ),
			'empty_state_description' => __( 'Email Digest is included in your plan. Activate the BuddyBoss Add-ons plugin to configure it.', 'buddyboss' ),
			'button_label'            => __( 'Go to Plugins', 'buddyboss' ),
			'button_url'              => admin_url( 'plugins.php' ),
		);
	} else {
		// Plan membership cannot be resolved without an activated licence, so activation
		// is the only honest next step. The heading names the blocker rather than the
		// feature: "Email Digest" over a locked card on the Email Digest panel tells an
		// admin nothing they do not already know.
		$card = array(
			'empty_state_title'       => __( 'License Activation Required', 'buddyboss' ),
			'empty_state_description' => __( 'Combine multiple notifications into a single daily or weekly email to reduce inbox clutter. Activate your license to unlock Email Digest.', 'buddyboss' ),
			'button_label'            => __( 'Activate License', 'buddyboss' ),
			'button_url'              => bp_get_admin_url( 'admin.php?page=buddyboss-license' ),
		);
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
			'help_text'   => __( 'Combine notification emails into a single daily or weekly digest for members who choose it. When disabled, all notification emails send immediately.', 'buddyboss' ),
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
			'help_text' => __( 'New members inherit this cadence. Existing members keep immediate delivery until they choose a digest.', 'buddyboss' ),
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
			'help_text' => __( 'Weekly digests send on this day. Ignored for daily digests.', 'buddyboss' ),
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
			'help_text' => __( 'Digests begin sending at this time (site timezone). Delivery is staggered — most members receive theirs shortly after.', 'buddyboss' ),
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
			'help_text'   => __( 'Unchecked types always send immediately.', 'buddyboss' ),
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
			'help_text'   => __( 'Social notifications include mentions, replies, and follows.', 'buddyboss' ),
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
			'help_text'   => __( 'Members must also opt in to message digests in their own notification preferences.', 'buddyboss' ),
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
			'placeholder' => __( 'Type digest heading & introduction text', 'buddyboss' ),
			'help_text'   => __( 'Shown at the top of every digest email — use it like a short newsletter intro.', 'buddyboss' ),
			'default'     => '',
			'order'       => 80,
			'group'       => array( 'key' => 'digest_heading' ),
		)
	);

	$register(
		array(
			'name'        => '_bb_email_digest_placeholder_templates_notice',
			'label'       => __( 'Digest Heading', 'buddyboss' ),
			'type'        => 'notice',
			'notice_type' => 'info',
			'description' => sprintf(
				/* translators: %s: link to the Emails admin screen. */
				__( 'Customize the digest design and wording under %s.', 'buddyboss' ),
				'<a href="' . esc_url( admin_url( 'edit.php?post_type=bp-email' ) ) . '">' . esc_html__( 'Emails', 'buddyboss' ) . '</a>'
			),
			'order'       => 90,
			'group'       => array( 'key' => 'digest_heading' ),
		)
	);

	// FIELD 9 — the test-send row. Rendered, but pointed nowhere: the handler that would
	// service it ships with the add-on, so on this panel it can only ever be scenery.
	$register(
		array(
			'name'         => '_bb_email_digest_placeholder_test',
			'label'        => __( 'Test Digest', 'buddyboss' ),
			'type'         => 'manage_link',
			'description'  => __( 'Sends a sample digest to your own email using your pending notifications. Does not affect member digests.', 'buddyboss' ),
			'manage_url'   => '',
			'manage_label' => __( 'Send Test Digest', 'buddyboss' ),
			'manage_icon'  => 'bb-icons-rl bb-icons-rl-paper-plane-tilt',
			'order'        => 110,
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
