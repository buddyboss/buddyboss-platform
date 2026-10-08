<?php
/**
 * Blogs feature admin settings registration.
 *
 * Registers the "Blog Settings" side panel with the Page Settings
 * section for the `blogging` feature.
 *
 * @since   BuddyBoss 3.2.0
 * @package BuddyBoss\Blogging
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/callbacks.php';

// Member Blogging add-on state resolution + install/activate AJAX handlers for
// the "Member Blogs" upsell panel registered below. Loaded unconditionally so
// the AJAX actions exist even on requests where the panel itself is not built.
require_once __DIR__ . '/member-blogs-addon.php';

/**
 * Whether the active BuddyBoss Platform Pro is too old to power the blog
 * Bookmarking / Subscriptions features registered on this screen.
 *
 * Both toggles are provided by Pro's blog module, which ships in the version
 * returned by bb_pro_blog_version(). When an older Pro is active it never
 * unlocks the fields, so they would sit as silent "UPGRADE LAUNCH" placeholders —
 * indistinguishable from Pro not being installed at all. This gate lets the
 * screen surface a version notice (and disable the toggles) so the admin knows
 * Pro simply needs updating.
 *
 * Returns false when Pro is not active: the fields then render as the normal
 * upsell, which is the correct state (there is nothing to "update").
 *
 * @since BuddyBoss 3.2.0
 *
 * @return bool True when Pro is active but older than the required version.
 */
function bb_blog_is_pro_outdated_for_settings() {
	return function_exists( 'bb_platform_pro' )
		&& function_exists( 'bb_pro_blog_version' )
		&& version_compare( bb_platform_pro()->version, bb_pro_blog_version(), '<' );
}

/**
 * Build the "update Pro" version-compat notice field for the Blogs settings.
 *
 * Shared between the Post Settings panel (where the Bookmarking / Subscriptions
 * toggles live) and the Member Blogs panel so the same message appears on both
 * blogging sub-pages. Each caller passes a unique field name — the feature
 * registry enforces globally-unique field names — and its own display order.
 *
 * @since BuddyBoss 3.2.0
 *
 * @param string $name  Unique field name.
 * @param int    $order Display order within its section.
 *
 * @return array Field registration arguments.
 */
function bb_blog_get_version_compat_notice_field( $name, $order ) {
	return array(
		'name'              => $name,
		'label'             => '',
		'type'              => 'notice',
		'notice_type'       => 'warning',
		'description'       => sprintf(
			/* translators: %s: required BuddyBoss Platform Pro version, e.g. 3.1.0. */
			__( 'Blog Bookmarking and Subscriptions require BuddyBoss Platform Pro %s or later. Please update BuddyBoss Platform Pro to use these features.', 'buddyboss-platform' ),
			function_exists( 'bb_pro_blog_version' ) ? bb_pro_blog_version() : '3.1.0'
		),
		'sanitize_callback' => '__return_empty_string',
		'order'             => $order,
	);
}

/**
 * Register Blogs feature side panels, sections and fields.
 *
 * @since BuddyBoss 3.2.0
 *
 * @return void
 */
function bb_blogging_register_admin_settings() {

	// Side panel: Blog Settings.
	bb_register_side_panel(
		'blogging',
		'blog_settings',
		array(
			'title'      => __( 'Blog Settings', 'buddyboss-platform' ),
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
	// Without Pro they render as locked "UPGRADE LAUNCH" placeholders; Pro flips
	// them live via the `bb_admin_settings_format_field_data` filter when the
	// license is valid (see BB_Blog's settings enrichment).
	bb_register_feature_section(
		'blogging',
		'blog_settings',
		'post_settings',
		array(
			'title'    => __( 'Post Settings', 'buddyboss-platform' ),
			'help_url' => '648796',
			'order'    => 10,
		)
	);

	// When Pro is active but too old to power Bookmarking / Subscriptions, show a
	// version notice above the two toggles and disable them. The blog module that
	// unlocks these fields ships in bb_pro_blog_version(); an older Pro leaves
	// them locked, so the notice explains that Pro needs updating.
	$bb_blog_pro_outdated = bb_blog_is_pro_outdated_for_settings();

	if ( $bb_blog_pro_outdated ) {
		bb_register_feature_field(
			'blogging',
			'blog_settings',
			'post_settings',
			bb_blog_get_version_compat_notice_field( 'bb_blog_version_compat_notice', 5 )
		);
	}

	// Field: Bookmarking (Pro).
	bb_register_feature_field(
		'blogging',
		'blog_settings',
		'post_settings',
		array(
			'name'        => 'bb_blog_enable_bookmarking',
			'label'       => __( 'Bookmarking', 'buddyboss-platform' ),
			'type'        => 'toggle',
			'description' => __( 'Allow users to bookmark blog posts', 'buddyboss-platform' ),
			'default'     => 0,
			'pro_only'    => true,
			'disabled'    => $bb_blog_pro_outdated,
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
			'label'       => __( 'Subscriptions', 'buddyboss-platform' ),
			'type'        => 'toggle',
			'description' => __( 'Allow users to subscribe to blog post categories', 'buddyboss-platform' ),
			'default'     => 0,
			'pro_only'    => true,
			'disabled'    => $bb_blog_pro_outdated,
			'order'       => 20,
		)
	);

	// Section: Page Settings.
	bb_register_feature_section(
		'blogging',
		'blog_settings',
		'page_settings',
		array(
			'title'    => __( 'Page Settings', 'buddyboss-platform' ),
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
				'description'       => __( 'The Blog Page Settings are only available when the BuddyBoss Theme or ReadyLaunch is selected.', 'buddyboss-platform' ),
				'sanitize_callback' => '__return_empty_string',
				'order'             => 5,
			)
		);
	}

	// Field: Social Links.
	//
	// The platform keys and their defaults are single-sourced from
	// bb_blog_social_link_platforms() so they cannot drift from the sanitize
	// callback, the activation seed, or the front-end share links. The helper
	// (admin/callbacks.php) is loaded before this runs, but guard anyway and
	// fall back to the canonical list. Labels are UI strings owned by this
	// field registration and are looked up per key.
	$bb_blog_social_defaults = function_exists( 'bb_blog_social_link_platforms' )
		? bb_blog_social_link_platforms()
		: array(
			'facebook' => 1,
			'linkedin' => 1,
			'x'        => 0,
			'whatsapp' => 0,
			'email'    => 0,
		);

	$bb_blog_social_labels = array(
		'facebook' => __( 'Facebook', 'buddyboss-platform' ),
		'linkedin' => __( 'Linkedin', 'buddyboss-platform' ),
		'x'        => __( 'X', 'buddyboss-platform' ),
		'whatsapp' => __( 'Whatsapp', 'buddyboss-platform' ),
		'email'    => __( 'Email', 'buddyboss-platform' ),
	);

	$bb_blog_social_options = array();
	foreach ( $bb_blog_social_defaults as $bb_blog_platform => $bb_blog_platform_default ) {
		$bb_blog_social_options[] = array(
			'label' => isset( $bb_blog_social_labels[ $bb_blog_platform ] ) ? $bb_blog_social_labels[ $bb_blog_platform ] : $bb_blog_platform,
			'value' => $bb_blog_platform,
		);
	}

	bb_register_feature_field(
		'blogging',
		'blog_settings',
		'page_settings',
		array(
			'name'              => 'bb_blog_social_links',
			'label'             => __( 'Social Links', 'buddyboss-platform' ),
			'type'              => 'toggle_list',
			'options'           => $bb_blog_social_options,
			'default'           => $bb_blog_social_defaults,
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
			'label'       => __( 'Related Posts', 'buddyboss-platform' ),
			'type'        => 'toggle',
			'description' => __( 'Enable related posts at the bottom of blog posts', 'buddyboss-platform' ),
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
			'label'       => __( 'Author Bio', 'buddyboss-platform' ),
			'type'        => 'toggle',
			'description' => __( 'Enable the Author Bio box at the bottom of blog posts', 'buddyboss-platform' ),
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
	// that have not installed the Scale add-on.
	if ( ! defined( 'BB_MEMBER_BLOG_VERSION' ) ) {
		bb_register_side_panel(
			'blogging',
			'member_blogs',
			array(
				'title' => __( 'Member Blogs', 'buddyboss-platform' ),
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
				'title' => __( 'Member Blogs', 'buddyboss-platform' ),
				'order' => 10,
			)
		);

		// The add-on's constant is undefined here, so the plugin is either not
		// installed, installed-but-inactive, or active on a build too old to
		// register its own settings. Which of those it is — and whether the
		// license even permits an install — is resolved lazily by
		// bb_member_blogging_format_upsell_field_data() on the
		// `bb_admin_settings_format_field_data` filter, so the license/plan
		// lookup (a potentially blocking remote call) stays off this
		// registration path, which runs on every admin request.
		//
		// The values below are the safe fallback for the case where that filter
		// never runs: the Plus upsell, which is correct for any site that
		// cannot install the add-on.
		bb_register_feature_field(
			'blogging',
			'member_blogs',
			'member_blogs',
			array(
				'name'                    => 'bb_member_blogging_upsell',
				'label'                   => '',
				'type'                    => 'empty_state',
				'icon'                    => 'bb-icons-rl bb-icons-rl-newspaper',
				'empty_state_title'       => __( 'Member Blogging', 'buddyboss-platform' ),
				'empty_state_description' => __( 'Allow your community members to contribute by creating blogs for your site via the frontend blog creator form. Available with the Member Blogging add-on on the Plus plan.', 'buddyboss-platform' ),
				'button_label'            => __( 'Upgrade to Scale', 'buddyboss-platform' ),
				// Fallback only. With `upgrade_from_catalog` on, the AJAX formatter
				// replaces this with the campaign-tagged URL from the field-upgrades
				// catalog when it holds an entry for this panel, so marketing can retarget
				// the link from S3 without a plugin release.
				'button_url'              => 'https://www.buddyboss.com/pricing/',
				'upgrade_from_catalog'    => true,
				'button_target'           => '_blank',
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
	 * @since BuddyBoss 3.2.0
	 */
	do_action( 'bb_blogging_after_register_settings_fields' );
}
add_action( 'bb_register_features', 'bb_blogging_register_admin_settings', 20 );
