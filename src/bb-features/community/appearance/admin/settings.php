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
	// SIDE PANEL: SITE SEO (always visible — Platform owns the shell, Sharing fills fields)
	// =========================================================================
	bb_register_side_panel(
		'appearance',
		'site_seo',
		array(
			'title'   => __( 'Site SEO', 'buddyboss' ),
			'icon'    => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-list-magnifying-glass',
			),
			'order'   => 40,
			// Visual separator in the left nav — Site SEO is a distinct
			// category from the ReadyLaunch-scoped panels above (General /
			// Branding / Menus) per Figma.
			'divider' => true,
		)
	);

	// Platform only registers the locked Site SEO placeholder when the Sharing
	// plugin is absent — the "Pattern A" used by other Pro-gated fields (see
	// settings-sharing.php). When BuddyBoss_Sharing is loaded it registers the
	// real section + fields at `bb_register_features` priority 20, including
	// its own pro_notice when the license is invalid. This avoids the
	// merge-mode section override while still showing an upgrade prompt to
	// free-tier admins who never installed Sharing.
	if ( ! class_exists( 'BuddyBoss_Sharing' ) ) {
		// Always force `show => true` here: Sharing is ABSENT, so the section
		// has no fields to render. A licensed Pro user without the Sharing
		// plugin installed would otherwise see an empty card because
		// `bb_admin_settings_get_pro_notice()` returns `show => false` for
		// licensed installs. The badge is the only visible content in this
		// branch, so hiding it produces a blank Site SEO panel.
		$site_seo_pro_notice = array(
			'show'       => true,
			'badge_text' => __( 'UPGRADE PRO', 'buddyboss' ),
			'badge_icon' => 'bb-icons-rl-crown-simple',
			'link_url'   => 'https://www.buddyboss.com/pricing/',
		);

		bb_register_feature_section(
			'appearance',
			'site_seo',
			'seo',
			array(
				'title'      => __( 'Site SEO', 'buddyboss' ),
				'order'      => 10,
				'pro_notice' => $site_seo_pro_notice,
			)
		);
	}

	// =========================================================================
	// GENERAL → SECTION: Site Name
	// =========================================================================
	bb_register_feature_section(
		'appearance',
		'general',
		'site_name',
		array(
			'title'    => __( 'Site Name', 'buddyboss' ),
			'order'    => 10,
			'help_url' => '#', // Placeholder — replace with help-content article ID once authored.
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
			'help_url' => '#',
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
			'name'              => 'bb_rl_enabled',
			'label'             => __( 'Layout', 'buddyboss' ),
			'type'              => 'select',
			'label_description' => __( 'Choose between ReadyLaunch or WordPress Theme', 'buddyboss' ),
			'description'       => __( 'ReadyLaunch overrides your theme\'s styles on BuddyBoss pages.', 'buddyboss' ),
			'options'           => array(
				array(
					'label' => __( 'ReadyLaunch', 'buddyboss' ),
					'value' => '1',
				),
				array(
					'label' => __( 'WordPress Theme', 'buddyboss' ),
					'value' => '0',
				),
			),
			'default'           => bp_get_option( 'bb_rl_enabled', false ) ? '1' : '0',
			'sanitize_callback' => 'bb_appearance_sanitize_layout',
			'field_class'       => 'bb-admin-settings-form__field--custom-full-width',
			'order'             => 10,
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
			'help_url'    => '#',
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
			'help_url'    => '#',
			'conditional' => array(
				'field' => 'bb_rl_enabled',
				'value' => true,
			),
		)
	);

	// Activity Feed widgets — only register when the Activity component is active
	// (matches legacy `ReadyLaunchSettings.js` BP_ADMIN.components.activity guard).
	if ( bp_is_active( 'activity' ) ) {
		if ( function_exists( 'bp_get_activity_root_slug' ) ) {
			$activity_feed_link = sprintf(
				/* translators: %1$s: opening anchor tag, %2$s: closing anchor tag. */
				__( 'Enable or disable widgets to appear on the %1$sactivity feed%2$s.', 'buddyboss' ),
				'<a href="' . esc_url( home_url( '/' . bp_get_activity_root_slug() . '/' ) ) . '">',
				'</a>'
			);
		} else {
			$activity_feed_link = __( 'Enable or disable widgets to appear on the activity feed.', 'buddyboss' );
		}

		bb_register_feature_field(
			'appearance',
			'general',
			'template_sidebars',
			array(
				'name'              => 'bb_rl_activity_sidebars',
				'label'             => __( 'Activity Feed', 'buddyboss' ),
				'type'              => 'toggle_list',
				'label_description' => $activity_feed_link,
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

	if ( function_exists( 'bp_get_members_directory_permalink' ) ) {
		$member_profile_link = sprintf(
			/* translators: %1$s: opening anchor tag, %2$s: closing anchor tag. */
			__( 'Enable or disable widgets to appear on the %1$smember profile%2$s.', 'buddyboss' ),
			'<a href="' . esc_url( bp_get_members_directory_permalink() ) . '">',
			'</a>'
		);
	} else {
		$member_profile_link = __( 'Enable or disable widgets to appear on the member profile.', 'buddyboss' );
	}

	// Member Profile widgets — build option list dynamically so Connections / My
	// Network only appear when their underlying components/features are active
	// (matches legacy `ReadyLaunchSettings.js` BP_ADMIN.components.friends and
	// bp_enable_activity_follow guards).
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
			'label_description' => $member_profile_link,
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

	if ( bp_is_active( 'groups' ) && function_exists( 'bp_get_groups_directory_permalink' ) ) {
		$group_single_link = sprintf(
			/* translators: %1$s: opening anchor tag, %2$s: closing anchor tag. */
			__( 'Enable or disable widgets to appear on the %1$sgroup single%2$s page.', 'buddyboss' ),
			'<a href="' . esc_url( bp_get_groups_directory_permalink() ) . '">',
			'</a>'
		);
	} else {
		$group_single_link = __( 'Enable or disable widgets to appear on the group single page.', 'buddyboss' );
	}

	bb_register_feature_field(
		'appearance',
		'general',
		'template_sidebars',
		array(
			'name'              => 'bb_rl_groups_sidebars',
			'label'             => __( 'Group', 'buddyboss' ),
			'type'              => 'toggle_list',
			'label_description' => $group_single_link,
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
			'title' => __( 'Branding', 'buddyboss' ),
			'order' => 10,
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
			'label'             => __( 'Heading', 'buddyboss' ),
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
			'label'             => __( 'Heading', 'buddyboss' ),
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
			'title' => __( 'Menus', 'buddyboss' ),
			'order' => 10,
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
			'name'                       => 'bb_rl_custom_links',
			'label'                      => __( 'Link', 'buddyboss' ),
			'type'                       => 'editable_link_list',
			'label_description'          => __( 'Add and re-order custom links which are shown on the left sidebar.', 'buddyboss' ),
			'default'                    => bp_get_option( 'bb_rl_custom_links', array() ),
			'sanitize_callback'          => 'bb_appearance_sanitize_custom_links',
			'editable_link_list_config'  => array(
				'add_label'        => __( 'Add New Link', 'buddyboss' ),
				'modal_title_add'  => __( 'Add Link', 'buddyboss' ),
				'modal_title_edit' => __( 'Edit Link', 'buddyboss' ),
			),
			'order'                      => 30,
		)
	);
}
add_action( 'bb_register_features', 'bb_admin_settings_register_appearance_settings', 20 );
