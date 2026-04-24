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
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/callbacks.php';
require_once __DIR__ . '/pages-panel.php';

/**
 * Register Appearance feature settings in the Feature Registry.
 *
 * @since BuddyBoss [BBVERSION]
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

	// Three possible states for the Site SEO panel (mirrors the Web Push
	// Notifications panel pattern in `settings-web-push.php`):
	// 1. NEW Sharing installed — `Site_SEO_Settings` class exists and
	// registers its own fields. Platform skips the fallback.
	// 2. OLD Sharing installed — `BuddyBoss_Sharing` main class exists but
	// predates Settings 2.0. Show an Update-Required empty state card
	// (mirrors Web Push "Pro OLD" branch). No "UPGRADE PRO" badge —
	// plugin is already present, just out of date.
	// 3. Sharing NOT installed / deactivated — show the full Figma fields
	// as PRO-gated disabled placeholders with an "UPGRADE PRO" badge
	// on the section (mirrors OneSignal `bb_notifications_register_web_push_pro_placeholder_fields()`).
	// Require both the class AND the Settings 2.0 registration method so a
	// partial Sharing build that ships the class namespace without the 2.0
	// hook still falls through to the "Update Required" card instead of
	// silently no-oping. Mirrors the Activity Sharing panel's detection
	// (`bb_sharing_has_new_sharing_plugin()`).
	$has_new_sharing = class_exists( '\\BuddyBoss\\Sharing\\Admin\\Site_SEO_Settings' )
		&& method_exists( '\\BuddyBoss\\Sharing\\Admin\\Site_SEO_Settings', 'register_site_seo' );
	$has_old_sharing = ! $has_new_sharing && class_exists( 'BuddyBoss_Sharing' );

	if ( $has_old_sharing ) {
		// OLD Sharing — Update Required empty state, no UPGRADE PRO badge.
		bb_register_feature_section(
			'appearance',
			'site_seo',
			'seo',
			array(
				'title' => __( 'Site SEO', 'buddyboss' ),
				'order' => 10,
			)
		);
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
	} elseif ( ! $has_new_sharing ) {
		// Sharing NOT installed — render the full Figma as PRO-gated
		// disabled placeholders so admins see what they'd get after install.
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
			'help_url' => '459612',
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
			'help_url' => '459617',
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
			'help_url'    => '459627',
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

	// Only register the field when at least one active integration offers a page.
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
			'help_url'    => '459623',
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

	// Inline "group single" link injected at AJAX format time. Do NOT call
	// `groups_get_groups()` here — the Groups component's `table_name` global
	// is undefined at `bb_register_features` (`bp_loaded@5`), so any DB query
	// against it emits a WP DB error.
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
			'help_url' => '459621',
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
			'help_url' => '459625',
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
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_appearance_register_site_seo_pro_placeholder_fields() {

	$feature_id = 'appearance';
	$panel_id   = 'site_seo';
	$section_id = 'seo';

	// -------------------------------------------------------------------------
	// SECTION: Site SEO (pro-gated placeholder, UPGRADE PRO badge).
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		$feature_id,
		$panel_id,
		$section_id,
		array(
			'title'      => __( 'Site SEO', 'buddyboss' ),
			'order'      => 10,
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
