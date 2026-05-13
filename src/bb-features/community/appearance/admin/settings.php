<?php
/**
 * BuddyBoss Admin Settings — Appearance Feature Settings Registration.
 *
 * Registers the Appearance feature's Settings 2.0 hierarchy:
 * Feature → Side Panels → Sections → Fields
 *
 * Three Platform-owned panels are registered here: General, Branding, Menus.
 * The Site SEO panel shell is registered in Phase 4 (Sharing plugin hook).
 *
 * Conditional visibility for Branding / Menus panels (hide when Site Layout is
 * "WordPress Theme") is wired up in Phase 5 via a registry-level `conditional`
 * arg. Until then the panels are always visible in the side nav.
 *
 * @package BuddyBoss\Features\Community\Appearance
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/callbacks.php';
require_once __DIR__ . '/pages-panel.php';

/**
 * Site SEO section title.
 *
 * Single source of truth for the section title used across the three section
 * registration branches in this file (new Sharing, old Sharing, no Sharing
 * placeholder). The side-panel title is intentionally NOT routed through this
 * helper — panel and section titles are conceptually distinct and a future
 * customization might want them to differ.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return string Translated section title.
 */
function bb_appearance_site_seo_section_title() {
	return __( 'Site SEO', 'buddyboss' );
}

/**
 * Site SEO help article ID.
 *
 * Single source of truth for the SEO section's `help_url`. Mirrors the
 * Activity Sharing pattern (`bb_activity_sharing_section_help_article()`).
 *
 * @since BuddyBoss 3.0.0
 *
 * @return string Help article ID.
 */
function bb_appearance_site_seo_section_help_article() {
	return '638202';
}

/**
 * Base args for the Site SEO section, shared across registration paths.
 *
 * State-specific extensions (e.g. UPGRADE PRO badge when Sharing is not
 * installed) are layered on top via the `bb_appearance_site_seo_section_args`
 * filter — see `bb_appearance_site_seo_get_section_args()` and
 * `bb_appearance_site_seo_add_pro_badge_when_no_sharing()` below.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return array Section args (title, order, help_url).
 */
function bb_appearance_site_seo_section_base_args() {
	return array(
		'title'    => bb_appearance_site_seo_section_title(),
		'order'    => 10,
		'help_url' => bb_appearance_site_seo_section_help_article(),
	);
}

/**
 * Build the final section args for `seo`, after extensions filter.
 *
 * Plugins that need to mutate the Site SEO section attributes (add a
 * `pro_notice` badge, change `status`, override `description`, etc.) should
 * hook the `bb_appearance_site_seo_section_args` filter rather than calling
 * `bb_register_feature_section()` a second time. Re-registering the same
 * section ID without `merge => true` would trigger the registry's
 * duplicate-detection auto-suffix path (`seo_1`) and render two sections.
 *
 * The filter is the canonical extension point for boot-time state. For
 * runtime state changes that only resolve at AJAX time (Sharing's license
 * lock — see the same constraint documented in the Activity Sharing panel),
 * use `merge => true` on a follow-up `bb_register_feature_section()` call —
 * see `bb_appearance_register_site_seo_pro_placeholder_fields()`.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return array Section args after filter.
 */
function bb_appearance_site_seo_get_section_args() {
	/**
	 * Filter the Site SEO section args.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param array $args Default section args (title, order, help_url).
	 */
	return apply_filters( 'bb_appearance_site_seo_section_args', bb_appearance_site_seo_section_base_args() );
}

/**
 * Filter callback: add UPGRADE PRO badge when the BuddyBoss Sharing plugin
 * is not installed at all (state 4).
 *
 * @since BuddyBoss 3.0.0
 *
 * @param array $args Default section args.
 * @return array Possibly-modified section args.
 */
function bb_appearance_site_seo_add_pro_badge_when_no_sharing( $args ) {
	$has_new_sharing = class_exists( '\\BuddyBoss\\Sharing\\Admin\\Site_SEO_Settings' )
		&& method_exists( '\\BuddyBoss\\Sharing\\Admin\\Site_SEO_Settings', 'register_site_seo' );
	$has_old_sharing = ! $has_new_sharing && class_exists( 'BuddyBoss_Sharing' );

	if ( $has_new_sharing || $has_old_sharing ) {
		return $args;
	}

	$args['pro_notice'] = array(
		'show'       => true,
		'badge_text' => __( 'UPGRADE PRO', 'buddyboss' ),
		'badge_icon' => 'bb-icons-rl-crown-simple',
		'link_url'   => 'https://www.buddyboss.com/pricing/',
	);

	return $args;
}
add_filter( 'bb_appearance_site_seo_section_args', 'bb_appearance_site_seo_add_pro_badge_when_no_sharing' );

/**
 * Register Appearance feature settings in the Feature Registry.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_admin_settings_register_appearance_settings() {

	// Batch-prime the WP object cache for every bb_rl_* option this function
	// reads below — one IN-list query beats 13 individual SELECT cache-miss
	// round-trips. Guarded on `function_exists()` because `wp_prime_option_caches()`
	// ships in WP 6.4 and BuddyBoss supports 6.0+.
	if ( function_exists( 'wp_prime_option_caches' ) ) {
		wp_prime_option_caches(
			array(
				'blogname',
				'bb_rl_enabled',
				'bb_rl_enabled_pages',
				'bb_rl_activity_sidebars',
				'bb_rl_member_profile_sidebars',
				'bb_rl_groups_sidebars',
				'bb_rl_theme_mode',
				'bb_rl_light_logo',
				'bb_rl_dark_logo',
				'bb_rl_color_light',
				'bb_rl_color_dark',
				'bb_rl_header_menu',
				'bb_rl_side_menu',
				'bb_rl_custom_links',
			)
		);
	}

	// =========================================================================
	// SIDE PANEL: GENERAL (always visible)
	// =========================================================================
	bb_register_side_panel(
		'appearance',
		'general',
		array(
			'title'      => __( 'General', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-toggle-right',
			),
			'order'      => 10,
			'is_default' => true,
		)
	);

	// =========================================================================
	// SIDE PANEL: BRANDING (visible only when Site Layout = ReadyLaunch)
	// =========================================================================
	bb_register_side_panel(
		'appearance',
		'branding',
		array(
			'title'       => __( 'Branding', 'buddyboss' ),
			'icon'        => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-palette',
			),
			'order'       => 20,
			'conditional' => array(
				'field' => 'bb_rl_enabled',
				'value' => true,
			),
		)
	);

	// =========================================================================
	// SIDE PANEL: MENUS (visible only when Site Layout = ReadyLaunch)
	// =========================================================================
	bb_register_side_panel(
		'appearance',
		'menus',
		array(
			'title'       => __( 'Menus', 'buddyboss' ),
			'icon'        => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-list-bullets',
			),
			'order'       => 30,
			'conditional' => array(
				'field' => 'bb_rl_enabled',
				'value' => true,
			),
		)
	);

	// =========================================================================
	// SIDE PANEL: PAGES (always visible — directory & registration page mapping)
	// =========================================================================
	// Divider lives on this panel (not on Site SEO) to visually separate the
	// page-mapping / SEO group from the ReadyLaunch-scoped panels above
	// (General / Branding / Menus) per Figma.
	bb_register_side_panel(
		'appearance',
		'pages',
		array(
			'title'   => __( 'Pages', 'buddyboss' ),
			'icon'    => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-file-text',
			),
			'order'   => 40,
			'divider' => true,
		)
	);

	// =========================================================================
	// SIDE PANEL: SITE SEO (always visible — Platform owns the shell, Sharing fills fields)
	// =========================================================================
	bb_register_side_panel(
		'appearance',
		'site_seo',
		array(
			'title' => __( 'Site SEO', 'buddyboss' ),
			'icon'  => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-list-magnifying-glass',
			),
			'order' => 50,
		)
	);

	// Single section registration — args go through the
	// `bb_appearance_site_seo_section_args` filter, which is the canonical
	// extension point for mutating section attributes from this or any
	// other plugin. State-appropriate badges (e.g. UPGRADE PRO when Sharing
	// is absent) are added via filter callbacks, not by re-registering.
	bb_register_feature_section(
		'appearance',
		'site_seo',
		'seo',
		bb_appearance_site_seo_get_section_args()
	);

	// Four possible states for the Site SEO panel (mirrors Activity Sharing):
	// 1. NEW Sharing + license valid — `Site_SEO_Settings` registers real
	//    fields via its lazy AJAX hook. Platform registers nothing here.
	// 2. NEW Sharing + license locked — same as state 1 at boot. Sharing's
	//    lazy hook calls `bb_appearance_register_site_seo_pro_placeholder_fields()`
	//    at AJAX time, which adds the UPGRADE PRO badge via merge mode and
	//    registers placeholder fields.
	// 3. OLD Sharing — `BuddyBoss_Sharing` exists but predates Settings 2.0.
	//    Show an Update-Required empty state card. No UPGRADE PRO badge.
	// 4. Sharing NOT installed — the `bb_appearance_site_seo_section_args`
	//    filter has already added the UPGRADE PRO badge to the section.
	//    Register the placeholder fields.
	//
	// Detection: require both the class AND the Settings 2.0 registration
	// method so a partial Sharing build that ships the class namespace
	// without the 2.0 hook still falls through to the "Update Required"
	// card instead of silently no-oping.
	$has_new_sharing = class_exists( '\\BuddyBoss\\Sharing\\Admin\\Site_SEO_Settings' )
		&& method_exists( '\\BuddyBoss\\Sharing\\Admin\\Site_SEO_Settings', 'register_site_seo' );
	$has_old_sharing = ! $has_new_sharing && class_exists( 'BuddyBoss_Sharing' );

	if ( $has_new_sharing ) {
		// States 1 and 2 — Sharing's lazy AJAX hook handles fields.
		// (State 2 also runs the merge to add the badge at AJAX time.)
	} elseif ( $has_old_sharing ) {
		// State 3 — OLD Sharing: show Update Required notice.
		bb_register_feature_field(
			'appearance',
			'site_seo',
			'seo',
			array(
				'name'                    => 'bb_appearance_site_seo_update_notice',
				'label'                   => '',
				'type'                    => 'empty_state',
				'icon'                    => 'bb-icons-rl bb-icons-rl-warning-circle',
				'empty_state_title'       => __( 'BuddyBoss Sharing Update Required', 'buddyboss' ),
				'empty_state_description' => __( 'Please update to the latest version of BuddyBoss Sharing to manage your site SEO and Open Graph settings here.', 'buddyboss' ),
				'button_label'            => __( 'Update Now', 'buddyboss' ),
				'button_url'              => admin_url( 'update-core.php' ),
				'sanitize_callback'       => '__return_empty_string',
				'order'                   => 10,
			)
		);
	} else {
		// State 4 — Sharing NOT installed: section already carries the
		// UPGRADE PRO badge from the filter callback above. Register
		// placeholder fields.
		bb_appearance_register_site_seo_pro_placeholder_fields();
	}

	// =========================================================================
	// GENERAL → SECTION: Site Name
	// =========================================================================
	// NOTE: Section `help_url` values are the BuddyBoss knowledge-base article
	// IDs used by the retired legacy ReadyLaunch admin (see the deleted
	// `ReadyLaunchSettings.js`'s `handleHelpClick('459xxx')` call sites).
	// Preserved verbatim so the `?` help icon links to the same KB article the
	// customer is used to.
	bb_register_feature_section(
		'appearance',
		'general',
		'site_name',
		array(
			'title'    => __( 'Site Name', 'buddyboss' ),
			'order'    => 10,
			'help_url' => '637134',
		)
	);

	bb_register_feature_field(
		'appearance',
		'general',
		'site_name',
		array(
			'name'              => 'blogname',
			'label'             => __( 'Site Name', 'buddyboss' ),
			'type'              => 'text',
			'label_description' => __( 'Displays in the browser title, search engine results and site header.', 'buddyboss' ),
			'description'       => __( 'This matches the WordPress Site Title. Updating it here will update it site-wide.', 'buddyboss' ),
			'default'           => get_option( 'blogname', '' ),
			'sanitize_callback' => 'sanitize_text_field',
			'field_class'       => 'bb-admin-settings-form__field--custom-full-width',
			'order'             => 10,
		)
	);

	// =========================================================================
	// GENERAL → SECTION: Site Layout
	// =========================================================================
	bb_register_feature_section(
		'appearance',
		'general',
		'site_layout',
		array(
			'title'    => __( 'Site Layout', 'buddyboss' ),
			'order'    => 20,
			'help_url' => '637133',
		)
	);

	// NOTE: Rendered as a dropdown (type=select) to match Figma. Option values are
	// strings "1"/"0" because SelectControl compares via String(value). The
	// `bb_appearance_sanitize_layout` callback coerces back to boolean so the
	// `bb_rl_enabled` option stays a bool end-to-end and `bb_is_readylaunch_enabled()`
	// keeps working unchanged.
	bb_register_feature_field(
		'appearance',
		'general',
		'site_layout',
		array(
			'name'                => 'bb_rl_enabled',
			'label'               => __( 'Layout', 'buddyboss' ),
			'type'                => 'select',
			'label_description'   => __( 'Choose between ReadyLaunch or WordPress Theme', 'buddyboss' ),
			// Fallback description used when `option_descriptions` doesn't
			// cover the current value — kept for backward compat with the
			// ReadyLaunch default.
			'description'         => __( 'ReadyLaunch overrides your theme\'s styles on BuddyBoss pages.', 'buddyboss' ),
			'options'             => array(
				array(
					'label' => __( 'ReadyLaunch', 'buddyboss' ),
					'value' => '1',
				),
				array(
					'label' => __( 'WordPress Theme', 'buddyboss' ),
					'value' => '0',
				),
			),
			// Per-option descriptions — swap live as the user changes the
			// dropdown. SettingsForm keys into this map by the current value
			// string so option labels and values must match verbatim.
			'option_descriptions' => array(
				'1' => __( 'ReadyLaunch overrides your theme\'s styles on BuddyBoss pages.', 'buddyboss' ),
				'0' => __( 'BuddyBoss pages will use your active theme\'s templates. Any templates not overridden will fall back to the platform default layouts.', 'buddyboss' ),
			),
			'default'             => bp_get_option( 'bb_rl_enabled', false ) ? '1' : '0',
			'sanitize_callback'   => 'bb_appearance_sanitize_layout',
			'field_class'         => 'bb-admin-settings-form__field--custom-full-width',
			'order'               => 10,
		)
	);

	// =========================================================================
	// GENERAL → SECTION: Template Pages (ReadyLaunch-only content)
	// =========================================================================
	bb_register_feature_section(
		'appearance',
		'general',
		'template_pages',
		array(
			'title'       => __( 'Template Pages', 'buddyboss' ),
			'order'       => 30,
			'help_url'    => '637139',
			'conditional' => array(
				'field' => 'bb_rl_enabled',
				'value' => true,
			),
		)
	);

	$enabled_pages_default = bp_get_option(
		'bb_rl_enabled_pages',
		array(
			'registration' => true,
			'courses'      => false,
		)
	);

	// Build Template Pages options dynamically — only show options whose backing
	// integration is active (matches legacy `class-bp-admin-tab.php` localize logic).
	$template_page_options = array();

	if ( function_exists( 'bp_enable_site_registration' ) && bp_enable_site_registration() && function_exists( 'bp_allow_custom_registration' ) && ! bp_allow_custom_registration() ) {
		$template_page_options[] = array(
			'label' => __( 'Login & Registration', 'buddyboss' ),
			'value' => 'registration',
		);
	}

	// Use the singleton accessor: `new BB_Readylaunch()` bypasses the singleton
	// guard and runs the constructor a second time, which re-registers
	// `login_header` / `login_footer` / `login_form` hooks and duplicates
	// the wp-login.php UI (PROD-9859).
	if ( class_exists( 'BB_Readylaunch' ) ) {
		$readylaunch_helper = BB_Readylaunch::instance();
		if ( $readylaunch_helper && method_exists( $readylaunch_helper, 'bb_is_sidebar_enabled_for_courses' ) && $readylaunch_helper->bb_is_sidebar_enabled_for_courses() ) {
			$template_page_options[] = array(
				'label' => __( 'Courses', 'buddyboss' ),
				'value' => 'courses',
			);
		}
	}

	if ( ! empty( $template_page_options ) ) {
		bb_register_feature_field(
			'appearance',
			'general',
			'template_pages',
			array(
				'name'              => 'bb_rl_enabled_pages',
				'label'             => __( 'Enable Pages', 'buddyboss' ),
				'type'              => 'toggle_list',
				'label_description' => __( 'Enable pages that should have styles from the template.', 'buddyboss' ),
				'options'           => $template_page_options,
				'default'           => $enabled_pages_default,
				'sanitize_callback' => 'bb_appearance_sanitize_enabled_pages',
				'order'             => 10,
			)
		);
	} else {
		// No active integration offers a template page — render an empty-state
		// card so the section never appears as a blank header/body.
		bb_register_feature_field(
			'appearance',
			'general',
			'template_pages',
			array(
				'name'                    => 'bb_rl_enabled_pages_empty',
				'label'                   => '',
				'type'                    => 'empty_state',
				'icon'                    => 'bb-icons-rl bb-icons-rl-warning-circle',
				'empty_state_title'       => __( 'No template pages available', 'buddyboss' ),
				'empty_state_description' => __( 'Template pages appear here once features like Login & Registration, Courses, or other compatible modules are enabled.', 'buddyboss' ),
				'sanitize_callback'       => '__return_empty_string',
				'order'                   => 10,
			)
		);
	}

	// =========================================================================
	// GENERAL → SECTION: Template Sidebar Widgets
	// =========================================================================
	bb_register_feature_section(
		'appearance',
		'general',
		'template_sidebars',
		array(
			'title'       => __( 'Template Sidebar Widgets', 'buddyboss' ),
			'order'       => 40,
			'help_url'    => '637141',
			'conditional' => array(
				'field' => 'bb_rl_enabled',
				'value' => true,
			),
		)
	);

	// Activity Feed widgets — only register when the Activity component is active
	// (matches legacy `ReadyLaunchSettings.js` BP_ADMIN.components.activity guard).
	//
	// The plain unlinked copy is registered here; the inline anchor tag is
	// injected at AJAX format time in `bb_appearance_build_sidebar_description()`
	// because BP component globals (members slug, groups table_name, loggedin
	// user) aren't populated yet at `bb_register_features` (`bp_loaded@5`).
	if ( bp_is_active( 'activity' ) ) {
		bb_register_feature_field(
			'appearance',
			'general',
			'template_sidebars',
			array(
				'name'              => 'bb_rl_activity_sidebars',
				'label'             => __( 'Activity Feed', 'buddyboss' ),
				'type'              => 'toggle_list',
				'label_description' => bb_appearance_render_sidebar_description( 'bb_rl_activity_sidebars' ),
				'options'           => array(
					array(
						'label' => __( 'Complete Profile', 'buddyboss' ),
						'value' => 'complete_profile',
					),
					array(
						'label' => __( 'Latest Updates', 'buddyboss' ),
						'value' => 'latest_updates',
					),
					array(
						'label' => __( 'Recent Blog Posts', 'buddyboss' ),
						'value' => 'recent_blog_posts',
					),
					array(
						'label' => __( 'Active Members', 'buddyboss' ),
						'value' => 'active_members',
					),
				),
				'default'           => bp_get_option(
					'bb_rl_activity_sidebars',
					array(
						'complete_profile'  => true,
						'latest_updates'    => true,
						'recent_blog_posts' => true,
						'active_members'    => true,
					)
				),
				'sanitize_callback' => 'bb_appearance_sanitize_sidebar_map',
				'order'             => 10,
			)
		);
	}

	// Member Profile widgets — build option list dynamically so Connections / My
	// Network only appear when their underlying components/features are active
	// (matches legacy `ReadyLaunchSettings.js` BP_ADMIN.components.friends and
	// bp_enable_activity_follow guards). Inline link injected at AJAX format
	// time — see the Activity Feed block above.
	$member_profile_options = array(
		array(
			'label' => __( 'Complete Profile', 'buddyboss' ),
			'value' => 'complete_profile',
		),
	);

	if ( bp_is_active( 'friends' ) ) {
		$member_profile_options[] = array(
			'label' => __( 'Connections', 'buddyboss' ),
			'value' => 'connections',
		);
	}

	if ( bp_is_active( 'activity' ) && function_exists( 'bp_is_activity_follow_active' ) && bp_is_activity_follow_active() ) {
		$member_profile_options[] = array(
			'label' => __( 'My Network (Follow, Followers)', 'buddyboss' ),
			'value' => 'my_network',
		);
	}

	bb_register_feature_field(
		'appearance',
		'general',
		'template_sidebars',
		array(
			'name'              => 'bb_rl_member_profile_sidebars',
			'label'             => __( 'Member Profile', 'buddyboss' ),
			'type'              => 'toggle_list',
			'label_description' => bb_appearance_render_sidebar_description( 'bb_rl_member_profile_sidebars' ),
			'options'           => $member_profile_options,
			'default'           => bp_get_option(
				'bb_rl_member_profile_sidebars',
				array(
					'complete_profile' => true,
					'connections'      => true,
					'my_network'       => true,
				)
			),
			'sanitize_callback' => 'bb_appearance_sanitize_sidebar_map',
			'order'             => 20,
		)
	);

	// Group widget block is gated on the Groups component being active —
	// mirrors the legacy ReadyLaunchSettings.js:1046-1047 check
	// (`BP_ADMIN.components.groups === 1`). When Groups is deactivated the
	// "About Group" / "Group Members" toggles have no surface to attach to,
	// so the field is omitted from the React payload entirely instead of
	// rendered-but-disabled. Same reasoning the legacy admin used.
	//
	// Inline "group single" link is injected at AJAX format time. Do NOT
	// call `groups_get_groups()` here — the Groups component's `table_name`
	// global is undefined at `bb_register_features` (`bp_loaded@5`), so any
	// DB query against it emits a WP DB error.
	if ( bp_is_active( 'groups' ) ) {
		bb_register_feature_field(
			'appearance',
			'general',
			'template_sidebars',
			array(
				'name'              => 'bb_rl_groups_sidebars',
				'label'             => __( 'Group', 'buddyboss' ),
				'type'              => 'toggle_list',
				'label_description' => bb_appearance_render_sidebar_description( 'bb_rl_groups_sidebars' ),
				'options'           => array(
					array(
						'label' => __( 'About Group', 'buddyboss' ),
						'value' => 'about_group',
					),
					array(
						'label' => __( 'Group Members', 'buddyboss' ),
						'value' => 'group_members',
					),
				),
				'default'           => bp_get_option(
					'bb_rl_groups_sidebars',
					array(
						'about_group'   => true,
						'group_members' => true,
					)
				),
				'sanitize_callback' => 'bb_appearance_sanitize_sidebar_map',
				'order'             => 30,
			)
		);
	}

	// =========================================================================
	// BRANDING → SECTION: Branding
	// =========================================================================
	bb_register_feature_section(
		'appearance',
		'branding',
		'branding',
		array(
			'title'    => __( 'Branding', 'buddyboss' ),
			'order'    => 10,
			'help_url' => '637143',
		)
	);

	$theme_mode_default = bp_get_option( 'bb_rl_theme_mode', 'light' );

	bb_register_feature_field(
		'appearance',
		'branding',
		'branding',
		array(
			'name'                => 'bb_rl_theme_mode',
			'label'               => __( 'Appearance', 'buddyboss' ),
			'type'                => 'radio',
			'description'         => __( 'This site will be shown in the selected mode.', 'buddyboss' ),
			'options'             => array(
				array(
					'label' => __( 'Light Mode', 'buddyboss' ),
					'value' => 'light',
				),
				array(
					'label' => __( 'Dark Mode', 'buddyboss' ),
					'value' => 'dark',
				),
				array(
					'label' => __( 'Both', 'buddyboss' ),
					'value' => 'choice',
				),
			),
			// Per-option description — swaps live as the user changes the radio.
			'option_descriptions' => array(
				'light'  => __( 'This site will be shown in light mode.', 'buddyboss' ),
				'dark'   => __( 'This site will be shown in dark mode.', 'buddyboss' ),
				'choice' => __( 'Users will be able switch between the modes.', 'buddyboss' ),
			),
			'default'             => $theme_mode_default,
			'sanitize_callback'   => 'bb_appearance_sanitize_theme_mode',
			'order'               => 10,
		)
	);

	// Logo group — both Light and Dark logos share the left-column "Logo" label (Figma).
	// First field carries the group label "Logo"; second field has an empty label so the
	// label column is hidden on the second row. Each field uses `group.label` to render
	// its own inline sub-header ("Logo (Light mode)" / "Logo (Dark mode)") above the control.
	bb_register_feature_field(
		'appearance',
		'branding',
		'branding',
		array(
			'name'                => 'bb_rl_light_logo',
			'label'               => __( 'Logo', 'buddyboss' ),
			'type'                => 'media_picker',
			'description'         => __( 'Recommended to use a dark-colored logo, 280x80 px, in JPG or PNG format.', 'buddyboss' ),
			'default'             => bp_get_option( 'bb_rl_light_logo', array() ),
			'sanitize_callback'   => 'bb_appearance_sanitize_media',
			'media_picker_config' => array(
				'library_type' => 'image',
				'multiple'     => false,
			),
			'group'               => array(
				'key'   => 'logo',
				'label' => __( 'Logo (Light mode)', 'buddyboss' ),
			),
			'conditional'         => array(
				'field'    => 'bb_rl_theme_mode',
				'value'    => 'dark',
				'operator' => '!=',
			),
			'order'               => 20,
		)
	);

	bb_register_feature_field(
		'appearance',
		'branding',
		'branding',
		array(
			'name'                => 'bb_rl_dark_logo',
			'label'               => __( 'Logo', 'buddyboss' ),
			'type'                => 'media_picker',
			'description'         => __( 'Recommended to use a white-colored logo, 280x80 px, in JPG or PNG format.', 'buddyboss' ),
			'default'             => bp_get_option( 'bb_rl_dark_logo', array() ),
			'sanitize_callback'   => 'bb_appearance_sanitize_media',
			'media_picker_config' => array(
				'library_type' => 'image',
				'multiple'     => false,
			),
			'group'               => array(
				'key'   => 'logo',
				'label' => __( 'Logo (Dark mode)', 'buddyboss' ),
			),
			'conditional'         => array(
				'field'    => 'bb_rl_theme_mode',
				'value'    => 'light',
				'operator' => '!=',
			),
			'order'               => 30,
		)
	);

	// Primary Color group — shares a single left-column label (Figma "Heading").
	bb_register_feature_field(
		'appearance',
		'branding',
		'branding',
		array(
			'name'              => 'bb_rl_color_light',
			'label'             => __( 'Theme Color', 'buddyboss' ),
			'type'              => 'color',
			'label_description' => __( 'Select the primary color of your community. This is used across buttons, links and secondary elements.', 'buddyboss' ),
			'default'           => bp_get_option( 'bb_rl_color_light', '#3E34FF' ),
			'sanitize_callback' => 'bb_appearance_sanitize_color',
			'group'             => array(
				'key'   => 'primary_color',
				'label' => __( 'Primary Color (Light mode)', 'buddyboss' ),
			),
			'conditional'       => array(
				'field'    => 'bb_rl_theme_mode',
				'value'    => 'dark',
				'operator' => '!=',
			),
			'order'             => 40,
		)
	);

	bb_register_feature_field(
		'appearance',
		'branding',
		'branding',
		array(
			'name'              => 'bb_rl_color_dark',
			'label'             => __( 'Theme Color', 'buddyboss' ),
			'label_description' => __( 'Select the primary color of your community. This is used across buttons, links and secondary elements.', 'buddyboss' ),
			'type'              => 'color',
			'default'           => bp_get_option( 'bb_rl_color_dark', '#A347FF' ),
			'sanitize_callback' => 'bb_appearance_sanitize_color',
			'group'             => array(
				'key'   => 'primary_color',
				'label' => __( 'Primary Color (Dark mode)', 'buddyboss' ),
			),
			'conditional'       => array(
				'field'    => 'bb_rl_theme_mode',
				'value'    => 'light',
				'operator' => '!=',
			),
			'order'             => 50,
		)
	);

	// =========================================================================
	// MENUS → SECTION: Menus
	// =========================================================================
	bb_register_feature_section(
		'appearance',
		'menus',
		'menus',
		array(
			'title'    => __( 'Menus', 'buddyboss' ),
			'order'    => 10,
			'help_url' => '637144',
		)
	);

	// Header Menu — simple WP nav-menu dropdown. Matches legacy ReadyLaunchSettings.js.
	$header_menu_options = array();
	if ( function_exists( 'wp_get_nav_menus' ) ) {
		foreach ( wp_get_nav_menus() as $nav_menu ) {
			$header_menu_options[] = array(
				'label' => $nav_menu->name,
				'value' => $nav_menu->slug,
			);
		}
	}
	if ( empty( $header_menu_options ) ) {
		$header_menu_options[] = array(
			'label' => __( 'ReadyLaunch (Default)', 'buddyboss' ),
			'value' => 'readylaunch',
		);
	}

	bb_register_feature_field(
		'appearance',
		'menus',
		'menus',
		array(
			'name'              => 'bb_rl_header_menu',
			'label'             => __( 'Header', 'buddyboss' ),
			'type'              => 'select',
			'description'       => __( 'Update your header menu from the Menus tab, where you\'ll find a dedicated ReadyLaunch header menu location.', 'buddyboss' ),
			'options'           => $header_menu_options,
			'default'           => bp_get_option( 'bb_rl_header_menu', 'readylaunch' ),
			'sanitize_callback' => 'sanitize_text_field',
			'order'             => 10,
		)
	);

	// Side Menu — drag-sortable list of predefined navigation items.
	// Items are component-conditional (matches legacy ReadyLaunchSettings.js getComponentMenuItems).
	$side_menu_items = array();
	if ( bp_is_active( 'activity' ) ) {
		$side_menu_items[] = array(
			'id'    => 'activity_feed',
			'label' => __( 'Activity Feed', 'buddyboss' ),
			'icon'  => 'pulse',
		);
	}
	$side_menu_items[] = array(
		'id'    => 'members',
		'label' => __( 'Members', 'buddyboss' ),
		'icon'  => 'users',
	);
	if ( bp_is_active( 'groups' ) ) {
		$side_menu_items[] = array(
			'id'    => 'groups',
			'label' => __( 'Groups', 'buddyboss' ),
			'icon'  => 'users-three',
		);
	}
	if ( bp_is_active( 'forums' ) ) {
		$side_menu_items[] = array(
			'id'    => 'forums',
			'label' => __( 'Forums', 'buddyboss' ),
			'icon'  => 'chat-text',
		);
	}
	$courses_integration = function_exists( 'bb_get_courses_integration' ) ? bb_get_courses_integration() : '';
	if ( ! empty( $courses_integration ) ) {
		$side_menu_items[] = array(
			'id'    => 'courses',
			'label' => __( 'Courses', 'buddyboss' ),
			'icon'  => 'graduation-cap',
		);
	}
	if ( bp_is_active( 'messages' ) ) {
		$side_menu_items[] = array(
			'id'    => 'messages',
			'label' => __( 'Messages', 'buddyboss' ),
			'icon'  => 'chat-teardrop-text',
		);
	}
	if ( bp_is_active( 'notifications' ) ) {
		$side_menu_items[] = array(
			'id'    => 'notifications',
			'label' => __( 'Notifications', 'buddyboss' ),
			'icon'  => 'bell',
		);
	}

	bb_register_feature_field(
		'appearance',
		'menus',
		'menus',
		array(
			'name'              => 'bb_rl_side_menu',
			'label'             => __( 'Side', 'buddyboss' ),
			'type'              => 'sortable_toggle_list',
			'label_description' => __( 'Enable and re-order menu items shown on the left sidebar.', 'buddyboss' ),
			'available_items'   => $side_menu_items,
			'default'           => bp_get_option( 'bb_rl_side_menu', array() ),
			'sanitize_callback' => 'bb_appearance_sanitize_side_menu',
			'order'             => 20,
		)
	);

	// Custom Links — user-defined link list (add/edit/delete via modal, drag to reorder).
	bb_register_feature_field(
		'appearance',
		'menus',
		'menus',
		array(
			'name'                      => 'bb_rl_custom_links',
			'label'                     => __( 'Link', 'buddyboss' ),
			'type'                      => 'editable_link_list',
			'label_description'         => __( 'Add and re-order custom links which are shown on the left sidebar.', 'buddyboss' ),
			'default'                   => bp_get_option( 'bb_rl_custom_links', array() ),
			'sanitize_callback'         => 'bb_appearance_sanitize_custom_links',
			'editable_link_list_config' => array(
				'add_label'        => __( 'Add New Link', 'buddyboss' ),
				'modal_title_add'  => __( 'Add Link', 'buddyboss' ),
				'modal_title_edit' => __( 'Edit Link', 'buddyboss' ),
			),
			'order'                     => 30,
		)
	);
}
add_action( 'bb_register_features', 'bb_admin_settings_register_appearance_settings', 20 );

/**
 * Register PRO-gated placeholder fields for the Site SEO panel.
 *
 * Called when the BuddyBoss Sharing plugin is NOT installed (or deactivated).
 * Mirrors `bb_notifications_register_web_push_pro_placeholder_fields()` in
 * `settings-web-push.php` — registers the full Figma field surface as
 * `pro_only` disabled placeholders so admins see what they'd unlock by
 * upgrading to Pro + installing Sharing.
 *
 * Field option keys match Sharing's Settings 2.0 registration
 * (`Site_SEO_Settings::register_*`) so if an admin later installs Sharing,
 * the stored values (which Platform never actually persists here thanks to
 * `__return_empty_string`) seamlessly hand over to Sharing's real fields.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return void
 */
function bb_appearance_register_site_seo_pro_placeholder_fields() {

	$feature_id = 'appearance';
	$panel_id   = 'site_seo';
	$section_id = 'seo';

	// Per-field PRO badge data. Site SEO is gated by the Sharing plugin, not
	// by Platform Pro — so when Sharing is inactive we always want the
	// row-level "PRO" badge to show, regardless of whether Pro is active.
	//
	// `bb_admin_settings_format_field_data` (in `class-bb-admin-settings-ajax.php`)
	// auto-computes `pro_notice` for any `pro_only` field that doesn't already
	// have one set, and that auto-compute (`bb_admin_settings_get_pro_notice`)
	// only returns `show => true` when Pro is inactive or its license is
	// invalid. The OneSignal placeholder relies on that auto-compute because
	// its placeholder only runs when Pro is inactive — so the assumption holds.
	// Site SEO is asymmetric: Sharing inactive can coexist with Pro active, so
	// we set `pro_notice` explicitly here to bypass the auto-compute and keep
	// the badge visible in that combination.
	$pro_notice_field = array(
		'show'       => true,
		'badge_text' => __( 'PRO', 'buddyboss' ),
		'badge_icon' => 'bb-icons-rl-crown-simple',
		'link_url'   => 'https://www.buddyboss.com/platform/',
		'link_icon'  => 'bb-icons-rl-play',
	);

	// Idempotently ensure the section carries the UPGRADE PRO badge.
	//
	// State 4 (no Sharing): the boot-time filter
	// `bb_appearance_site_seo_add_pro_badge_when_no_sharing()` already added
	// the badge. This merge overlays the same value — no-op.
	//
	// State 2 (Sharing license locked at AJAX time): the boot-time filter
	// did NOT add the badge because the DRM-vs-panel-hook race on
	// `plugins_loaded@10` makes license-lock state unreliable at boot
	// (Sharing's `register_with_drm()` runs after Platform's panel hook
	// inside the same `plugins_loaded@10` dispatch). This merge adds the
	// badge at AJAX time, where license state is settled. Same canonical
	// pattern used by Activity Sharing.
	bb_register_feature_section(
		$feature_id,
		$panel_id,
		$section_id,
		array(
			'merge'      => true,
			'pro_notice' => array(
				'show'       => true,
				'badge_text' => __( 'UPGRADE PRO', 'buddyboss' ),
				'badge_icon' => 'bb-icons-rl-crown-simple',
				'link_url'   => 'https://www.buddyboss.com/pricing/',
			),
		)
	);

	// SEO group: title + description + Google SERP preview card.
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		$section_id,
		array(
			'name'              => 'buddyboss_seo_title',
			'label'             => __( 'SEO', 'buddyboss' ),
			'type'              => 'text',
			'placeholder'       => get_bloginfo( 'name' ),
			'description'       => __( 'Set the main title of your website that Google will index. The optimal length is about 55 characters.', 'buddyboss' ),
			'default'           => '',
			'pro_only'          => true,
			'pro_notice'        => $pro_notice_field,
			'sanitize_callback' => '__return_empty_string',
			'group'             => array(
				'key'   => 'seo',
				'label' => __( 'SEO Title', 'buddyboss' ),
			),
			'order'             => 10,
		)
	);

	bb_register_feature_field(
		$feature_id,
		$panel_id,
		$section_id,
		array(
			'name'              => 'buddyboss_seo_description',
			'label'             => __( 'SEO', 'buddyboss' ),
			'type'              => 'textarea',
			'placeholder'       => get_bloginfo( 'description' ),
			'description'       => __( 'Set the default description that will accompany your SEO title in search engine results. The optimal description length is 155 to 300 characters.', 'buddyboss' ),
			'default'           => '',
			'pro_only'          => true,
			'pro_notice'        => $pro_notice_field,
			'sanitize_callback' => '__return_empty_string',
			'group'             => array(
				'key'   => 'seo',
				'label' => __( 'SEO Description', 'buddyboss' ),
			),
			'order'             => 20,
		)
	);

	// Google SERP preview card — reads title/description live from above.
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		$section_id,
		array(
			'name'              => 'buddyboss_seo_preview',
			'label'             => __( 'SEO', 'buddyboss' ),
			'type'              => 'seo_preview',
			'default'           => '',
			'group'             => array(
				'key' => 'seo',
			),
			'preview_config'    => array(
				'site_name'       => get_bloginfo( 'name' ),
				'site_url'        => home_url( '/' ),
				'site_icon'       => get_site_icon_url( 48 ),
				'title_key'       => 'buddyboss_seo_title',
				'description_key' => 'buddyboss_seo_description',
			),
			'pro_only'          => true,
			'pro_notice'        => $pro_notice_field,
			'sanitize_callback' => '__return_empty_string',
			'order'             => 30,
		)
	);

	// Social group: Open Graph toggle.
	// `description` = short inline label next to the toggle ("Enable Open-graph").
	// `help_text`   = secondary helper copy rendered below the field.
	// Matches Figma layout where the toggle's inline label is the short text
	// and the long "Open Graph support improves…" paragraph sits underneath.
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		$section_id,
		array(
			'name'              => 'buddyboss_enable_open_graph',
			'label'             => __( 'Social', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Enable Open-graph', 'buddyboss' ),
			'help_text'         => __( 'Open Graph support improves how your content appears when shared on social platforms such as Facebook, LinkedIn, and X.', 'buddyboss' ),
			'default'           => 0,
			'pro_only'          => true,
			'pro_notice'        => $pro_notice_field,
			// __return_empty_string drops any value a client tries to submit to this Pro-locked placeholder field.
			'sanitize_callback' => '__return_empty_string',
			'order'             => 40,
		)
	);

	// Activity Title Template + Available Tags reference list.
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		$section_id,
		array(
			'name'              => 'buddyboss_activity_og_title_template',
			'label'             => __( 'Activity Title Template', 'buddyboss' ),
			'type'              => 'text',
			'placeholder'       => '{activity_action} | {site_title}',
			'description'       => __( 'Template for activity Open Graph titles. Use the tags below to dynamically insert activity data.', 'buddyboss' ),
			'default'           => '',
			'pro_only'          => true,
			'pro_notice'        => $pro_notice_field,
			'sanitize_callback' => '__return_empty_string',
			'order'             => 50,
		)
	);

	bb_register_feature_field(
		$feature_id,
		$panel_id,
		$section_id,
		array(
			'name'              => 'buddyboss_activity_og_title_template_tags',
			'label'             => '',
			'type'              => 'tags_reference',
			'tags'              => array(
				array(
					'tag'         => '{activity_title}',
					'description' => __( 'Activity post title (falls back to activity_action if empty)', 'buddyboss' ),
				),
				array(
					'tag'         => '{activity_action}',
					'description' => __( 'Activity action text (e.g., "John posted an update")', 'buddyboss' ),
				),
				array(
					'tag'         => '{activity_content}',
					'description' => __( 'Activity content (limited to 60 characters)', 'buddyboss' ),
				),
				array(
					'tag'         => '{author_name}',
					'description' => __( 'Activity author display name', 'buddyboss' ),
				),
			),
			'default'           => '',
			'pro_only'          => true,
			'pro_notice'        => $pro_notice_field,
			'sanitize_callback' => '__return_empty_string',
			'order'             => 55,
		)
	);

	// Indexing group: four search-engine indexing toggles.
	// All four share `label: "Indexing"` + `group.key: 'indexing'` so the
	// left-column "Indexing" label renders only on the first toggle (Posts);
	// the other three have their left column auto-suppressed by the group
	// first/last detection in SettingsForm.
	//
	// Each toggle's inline right-column label ("Posts" / "Profiles" / etc.)
	// goes in `description` (which toggle fields render inline next to the
	// switch). The LAST toggle also carries the shared `help_text` line
	// so "Choose whether search engines should index…" renders once below
	// the cluster, matching the Figma.
	$indexing_toggles = array(
		'buddyboss_seo_index_posts'    => __( 'Posts', 'buddyboss' ),
		'buddyboss_seo_index_profiles' => __( 'Profiles', 'buddyboss' ),
		'buddyboss_seo_index_groups'   => __( 'Groups', 'buddyboss' ),
		'buddyboss_seo_index_forums'   => __( 'Forums', 'buddyboss' ),
	);
	$indexing_order   = 60;
	$indexing_total   = count( $indexing_toggles );
	$indexing_cur     = 0;
	foreach ( $indexing_toggles as $toggle_name => $toggle_label ) {
		++$indexing_cur;
		$is_last_toggle = ( $indexing_cur === $indexing_total );
		bb_register_feature_field(
			$feature_id,
			$panel_id,
			$section_id,
			array(
				'name'              => $toggle_name,
				'label'             => __( 'Indexing', 'buddyboss' ),
				'type'              => 'toggle',
				'description'       => $toggle_label,
				'help_text'         => $is_last_toggle ? __( 'Choose whether search engines should index this content. Turning it off will hide it from search results.', 'buddyboss' ) : '',
				'default'           => 0,
				'pro_only'          => true,
				'pro_notice'        => $pro_notice_field,
				// __return_empty_string drops any value a client tries to submit to this Pro-locked placeholder field.
				'sanitize_callback' => '__return_empty_string',
				'group'             => array(
					'key' => 'indexing',
				),
				'order'             => $indexing_order,
			)
		);
		$indexing_order += 10;
	}
}
