<?php
/**
 * Blogs feature admin settings registration.
 *
 * Registers the "Blog Settings" side panel with the Page Settings
 * section for the `blogging` feature.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Blogging
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/callbacks.php';

/**
 * Register Blogs feature side panels, sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_blogging_register_admin_settings() {

	// Side panel: Blog Settings.
	bb_register_side_panel(
		'blogging',
		'blog_settings',
		array(
			'title'      => __( 'Blog Settings', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-gear-six',
			),
			'order'      => 10,
			'is_default' => true,
		)
	);

	// Section: Post Settings.
	//
	// The two fields (Bookmarking, Subscriptions) are registered pro_only —
	// their behaviour is provided by BuddyBoss Platform Pro's blog module.
	// Without Pro they render as locked "UPGRADE PRO" placeholders; Pro flips
	// them live via the `bb_admin_settings_format_field_data` filter when the
	// license is valid (see BB_Blog's settings enrichment).
	bb_register_feature_section(
		'blogging',
		'blog_settings',
		'post_settings',
		array(
			'title'    => __( 'Post Settings', 'buddyboss' ),
			'help_url' => '648796',
			'order'    => 10,
		)
	);

	// Field: Bookmarking (Pro).
	bb_register_feature_field(
		'blogging',
		'blog_settings',
		'post_settings',
		array(
			'name'        => 'bb_blog_enable_bookmarking',
			'label'       => __( 'Bookmarking', 'buddyboss' ),
			'type'        => 'toggle',
			'description' => __( 'Allow users to bookmark blog posts', 'buddyboss' ),
			'default'     => 0,
			'pro_only'    => true,
			'order'       => 10,
		)
	);

	// Field: Subscriptions (Pro).
	bb_register_feature_field(
		'blogging',
		'blog_settings',
		'post_settings',
		array(
			'name'        => 'bb_blog_enable_subscriptions',
			'label'       => __( 'Subscriptions', 'buddyboss' ),
			'type'        => 'toggle',
			'description' => __( 'Allow users to subscribe to blog post categories', 'buddyboss' ),
			'default'     => 0,
			'pro_only'    => true,
			'order'       => 20,
		)
	);

	// Section: Page Settings.
	bb_register_feature_section(
		'blogging',
		'blog_settings',
		'page_settings',
		array(
			'title'    => __( 'Page Settings', 'buddyboss' ),
			'help_url' => '648801',
			'order'    => 20,
		)
	);

	// Page Settings render only via the BuddyBoss Theme or ReadyLaunch blog
	// templates — without either, the fields are disabled behind a notice.
	$bb_blog_page_settings_available = bb_blog_page_settings_is_available();

	if ( ! $bb_blog_page_settings_available ) {
		bb_register_feature_field(
			'blogging',
			'blog_settings',
			'page_settings',
			array(
				'name'              => 'bb_blog_page_settings_notice',
				'label'             => '',
				'type'              => 'notice',
				'notice_type'       => 'info',
				'description'       => __( 'The Blog Page Settings are only available when the BuddyBoss Theme or ReadyLaunch is selected.', 'buddyboss' ),
				'sanitize_callback' => '__return_empty_string',
				'order'             => 5,
			)
		);
	}

	// Field: Social Links.
	bb_register_feature_field(
		'blogging',
		'blog_settings',
		'page_settings',
		array(
			'name'              => 'bb_blog_social_links',
			'label'             => __( 'Social Links', 'buddyboss' ),
			'type'              => 'toggle_list',
			'options'           => array(
				array(
					'label' => __( 'Facebook', 'buddyboss' ),
					'value' => 'facebook',
				),
				array(
					'label' => __( 'Linkedin', 'buddyboss' ),
					'value' => 'linkedin',
				),
				array(
					'label' => __( 'X', 'buddyboss' ),
					'value' => 'x',
				),
				array(
					'label' => __( 'Whatsapp', 'buddyboss' ),
					'value' => 'whatsapp',
				),
				array(
					'label' => __( 'Email', 'buddyboss' ),
					'value' => 'email',
				),
			),
			'default'           => array(
				'facebook' => 1,
				'linkedin' => 1,
				'x'        => 0,
				'whatsapp' => 0,
				'email'    => 0,
			),
			'option_prefix'     => 'bb_blog_social_link_',
			'sanitize_callback' => 'bb_blog_sanitize_social_links',
			'disabled'          => ! $bb_blog_page_settings_available,
			'order'             => 10,
		)
	);

	// Field: Related Posts.
	bb_register_feature_field(
		'blogging',
		'blog_settings',
		'page_settings',
		array(
			'name'        => 'bb_blog_related_posts',
			'label'       => __( 'Related Posts', 'buddyboss' ),
			'type'        => 'toggle',
			'description' => __( 'Enable related posts at the bottom of blog posts', 'buddyboss' ),
			'default'     => 1,
			'disabled'    => ! $bb_blog_page_settings_available,
			'order'       => 20,
		)
	);

	// Field: Author Bio.
	bb_register_feature_field(
		'blogging',
		'blog_settings',
		'page_settings',
		array(
			'name'        => 'bb_blog_author_bio',
			'label'       => __( 'Author Bio', 'buddyboss' ),
			'type'        => 'toggle',
			'description' => __( 'Enable the Author Bio box at the bottom of blog posts', 'buddyboss' ),
			'default'     => 1,
			'disabled'    => ! $bb_blog_page_settings_available,
			'order'       => 30,
		)
	);

	// Member Blogs upsell panel — registered only when the Member Blogging
	// add-on is NOT active. When the add-on is present it registers the real
	// "Member Blogs" panel (or its own enable/locked gate) on the later
	// `bb_after_register_features` hook, so this placeholder is skipped. This
	// keeps the "Member Blogs" tab visible for discovery/upsell even on sites
	// that have not installed the Plus add-on.
	if ( ! defined( 'BB_MEMBER_BLOG_VERSION' ) ) {
		bb_register_side_panel(
			'blogging',
			'member_blogs',
			array(
				'title' => __( 'Member Blogs', 'buddyboss' ),
				'icon'  => array(
					'type'  => 'font',
					'class' => 'bb-icons-rl bb-icons-rl-newspaper',
				),
				'order' => 20,
			)
		);

		bb_register_feature_section(
			'blogging',
			'member_blogs',
			'member_blogs',
			array(
				'title' => __( 'Member Blogs', 'buddyboss' ),
				'order' => 10,
			)
		);

		// The add-on's constant is undefined here, so the plugin is either not
		// installed or installed-but-inactive. When it is present on disk, tell
		// the admin to activate it; otherwise show the Plus upgrade CTA.
		$bb_member_blog_plugin_file = 'buddyboss-member-blogging/buddyboss-member-blogging.php';

		// Add-on action for the empty-state button. When the plugin is installed
		// but inactive, activate it in place via the Mothership AJAX flow
		// (mosh_addon_activate) instead of a full-page plugins.php redirect.
		$bb_member_blog_addon_action = '';
		$bb_member_blog_addon_slug   = '';

		if ( file_exists( WP_PLUGIN_DIR . '/' . $bb_member_blog_plugin_file ) ) {
			$bb_member_blog_upsell_description = __( 'The Member Blogging add-on is installed but not activated. Activate it to let your community members create blog posts from the frontend.', 'buddyboss' );
			$bb_member_blog_upsell_button      = __( 'Activate Plugin', 'buddyboss' );
			// Kept as a no-JS fallback; the React empty-state button prefers the
			// AJAX action below when it is present.
			$bb_member_blog_upsell_url    = wp_nonce_url(
				self_admin_url( 'plugins.php?action=activate&plugin=' . $bb_member_blog_plugin_file ),
				'activate-plugin_' . $bb_member_blog_plugin_file
			);
			$bb_member_blog_upsell_target = '';
			$bb_member_blog_addon_action  = 'mosh_addon_activate';
			$bb_member_blog_addon_slug    = dirname( $bb_member_blog_plugin_file );
		} else {
			$bb_member_blog_upsell_description = __( 'Allow your community members to contribute by creating blogs for your site via the frontend blog creator form. Available with the Member Blogging add-on on the Plus plan.', 'buddyboss' );
			$bb_member_blog_upsell_button      = __( 'Upgrade to Plus', 'buddyboss' );
			$bb_member_blog_upsell_url         = 'https://www.buddyboss.com/pricing/';
			$bb_member_blog_upsell_target      = '_blank';
		}

		bb_register_feature_field(
			'blogging',
			'member_blogs',
			'member_blogs',
			array(
				'name'                    => 'bb_member_blogging_upsell',
				'label'                   => '',
				'type'                    => 'empty_state',
				'icon'                    => 'bb-icons-rl bb-icons-rl-newspaper',
				'empty_state_title'       => __( 'Member Blogging', 'buddyboss' ),
				'empty_state_description' => $bb_member_blog_upsell_description,
				'button_label'            => $bb_member_blog_upsell_button,
				'button_url'              => $bb_member_blog_upsell_url,
				'button_target'           => $bb_member_blog_upsell_target,
				'addon_action'            => $bb_member_blog_addon_action,
				'addon_slug'              => $bb_member_blog_addon_slug,
				'sanitize_callback'       => '__return_empty_string',
				'order'                   => 10,
			)
		);
	}

	/**
	 * Fires after the Blogs feature settings fields are registered.
	 *
	 * Platform Pro and the Member Blogging add-on hook here to attach
	 * additional side panels, sections and fields to the Blogs feature.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_blogging_after_register_settings_fields' );
}
add_action( 'bb_register_features', 'bb_blogging_register_admin_settings', 20 );
