<?php

/**
 * Forums Admin Settings
 *
 * @package BuddyBoss\Administration
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Sections ******************************************************************/

/**
 * Get the Forums settings sections.
 *
 * @since bbPress (r4001)
 * @return array
 */
function bbp_admin_get_settings_sections() {
	return (array) apply_filters(
		'bbp_admin_get_settings_sections',
		array(
			'bbp_settings_users'        => array(
				'title'    => esc_html__( 'Forum Settings', 'buddyboss' ),
				'page'     => 'discussion',
			),
			'bbp_settings_features'     => array(
				'title'    => esc_html__( 'Forum Features', 'buddyboss' ),
				'page'     => 'discussion',
			),
			'bbp_settings_buddypress'   => array(
				'title'    => esc_html__( 'Group Forums', 'buddyboss' ),
				'page'     => 'buddypress',
			),
			'bbp_settings_root_slugs'   => array(
				'title'    => esc_html__( 'Forums Directory', 'buddyboss' ),
				'callback' => 'bbp_admin_setting_callback_root_slug_section',
				'page'     => 'permalink',
			),
			'bbp_settings_theme_compat' => array(
				'title'    => esc_html__( 'Forum Theme Packages', 'buddyboss' ),
				'callback' => 'bbp_admin_setting_callback_subtheme_section',
				'page'     => 'general',
			),
			'bbp_settings_per_page'     => array(
				'title'    => esc_html__( 'Discussions and Replies Per Page', 'buddyboss' ),
				'page'     => 'reading',
			),
			'bbp_settings_per_rss_page' => array(
				'title'    => esc_html__( 'Discussions and Replies Per RSS Page', 'buddyboss' ),
				'page'     => 'reading',
			),
			'bbp_settings_single_slugs' => array(
				'title'    => esc_html__( 'Forum Permalinks', 'buddyboss' ),
				'callback' => 'bbp_admin_setting_callback_single_slug_section',
				'page'     => 'permalink',
			),
			 'bbp_settings_user_slugs' => array(
				 'title'    => esc_html__( 'Forum Profile Permalinks', 'buddyboss' ),
				 'callback' => 'bbp_admin_setting_callback_user_slug_section',
				 'page'     => 'permalink',
			 ),
			'bbp_settings_akismet'      => array(
				'title'    => esc_html__( 'Akismet Integration', 'buddyboss' ),
				'page'     => 'discussion',
			),
		)
	);
}

/**
 * Get all of the settings fields.
 *
 * @since bbPress (r4001)
 * @return type
 */
function bbp_admin_get_settings_fields() {
	$fields = (array) apply_filters(
		'bbp_admin_get_settings_fields',
		array(

			/** Forum Settings Section **/

			'bbp_settings_users'        => array(

				// Edit lock setting
				'_bbp_edit_lock'       => array(
					'title'             => __( 'Disallow editing after', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_editlock',
					'sanitize_callback' => 'intval',
					'args'              => array(),
				),

				// Throttle setting
				'_bbp_throttle_time'   => array(
					'title'             => __( 'Throttle posting every', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_throttle',
					'sanitize_callback' => 'intval',
					'args'              => array(),
				),

				// Allow anonymous posting setting
				'_bbp_allow_anonymous' => array(
					'title'             => __( 'Anonymous posting', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_anonymous',
					'sanitize_callback' => 'intval',
					'args'              => array(),
				),

				// Allow global access (on multisite)
				'_bbp_default_role'    => array(
					'sanitize_callback' => 'sanitize_text_field',
					'args'              => array(),
				),
			),

			/** Forum Features Section **/

			'bbp_settings_features'     => array(

				// Allow topic and reply revisions
				'_bbp_allow_revisions'        => array(
					'title'             => __( 'Revisions', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_revisions',
					'sanitize_callback' => 'intval',
					'args'              => array(),
				),

				// Allow favorites setting
				'_bbp_enable_favorites'       => array(
					'title'             => __( 'Favorites', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_favorites',
					'sanitize_callback' => 'intval',
					'args'              => array(),
				),

				// Allow subscriptions setting
				'_bbp_enable_subscriptions'   => array(
					'title'             => __( 'Subscriptions', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_subscriptions',
					'sanitize_callback' => 'intval',
					'args'              => array(),
				),

				// Allow topic tags
				'_bbp_allow_topic_tags'       => array(
					'title'             => __( 'Discussion tags', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_topic_tags',
					'sanitize_callback' => 'intval',
					'args'              => array(),
				),

				// Allow topic tags
				'_bbp_allow_search'           => array(
					'title'             => __( 'Search', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_search',
					'sanitize_callback' => 'intval',
					'args'              => array(),
				),

				// Allow fancy editor setting
				'_bbp_use_wp_editor'          => array(
					'title'             => __( 'Post Formatting', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_use_wp_editor',
					'args'              => array(),
					'sanitize_callback' => 'intval',
				),

				// Allow auto embedding setting
				'_bbp_use_autoembed'          => array(
					'title'             => __( 'Link Previews', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_use_autoembed',
					'sanitize_callback' => 'intval',
					'args'              => array(),
				),

				// Set reply threading level
				'_bbp_thread_replies_depth'   => array(
					'title'             => __( 'Reply Threading', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_thread_replies_depth',
					'sanitize_callback' => 'intval',
					'args'              => array(),
				),

				// Allow threaded replies
				'_bbp_allow_threaded_replies' => array(
					'sanitize_callback' => 'intval',
					'args'              => array(),
				),
			),

			/** Theme Packages **/

			'bbp_settings_theme_compat' => array(

				// Theme package setting
				'_bbp_theme_package_id' => array(
					'title'             => __( 'Current Package', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_subtheme_id',
					'sanitize_callback' => 'esc_sql',
					'args'              => array(),
				),
			),

			/** Group Forums Settings **/

			'bbp_settings_buddypress'   => array(

				// Are group forums enabled?
				'_bbp_enable_group_forums'  => array(
					'title'             => esc_html__( 'Group Forums', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_group_forums',
					'sanitize_callback' => 'intval',
					'args'              => array(),
				),
			),

			/** Forums Directory Settings **/

			'bbp_settings_root_slugs'   => array(

				// Root slug setting
				// '_bbp_root_slug' => array(
				// 'title'             => __( 'Forums Directory', 'buddyboss' ),
				// 'callback'          => 'bbp_admin_setting_callback_root_slug',
				// 'sanitize_callback' => 'bbp_sanitize_slug',
				// 'args'              => array()
				// ),

				   // Include root setting
				   '_bbp_include_root' => array(
					   'title'             => esc_html__( 'Forums Prefix', 'buddyboss' ),
					   'callback'          => 'bbp_admin_setting_callback_include_root',
					   'sanitize_callback' => 'intval',
					   'args'              => array(),
				   ),

				// What to show on Forum Root
				'_bbp_show_on_root'    => array(
					'title'             => esc_html__( 'Forums Directory shows', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_show_on_root',
					'sanitize_callback' => 'sanitize_text_field',
					'args'              => array(),
				),
			),

			/** Discussions and Replies Per Page Section **/

			'bbp_settings_per_page'     => array(

				// Replies per page setting
				'_bbp_forums_per_page'  => array(
					'title'             => __( 'Forums', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_forums_per_page',
					'sanitize_callback' => 'intval',
					'args'              => array(),
				),

				// Replies per page setting
				'_bbp_topics_per_page'  => array(
					'title'             => __( 'Discussions', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_topics_per_page',
					'sanitize_callback' => 'intval',
					'args'              => array(),
				),

				// Replies per page setting
				'_bbp_replies_per_page' => array(
					'title'             => __( 'Replies', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_replies_per_page',
					'sanitize_callback' => 'intval',
					'args'              => array(),
				),
			),

			/** Discussions and Replies Per Page Section **/

			'bbp_settings_per_rss_page' => array(

				// Replies per page setting
				'_bbp_topics_per_rss_page'  => array(
					'title'             => __( 'Discussions', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_topics_per_rss_page',
					'sanitize_callback' => 'intval',
					'args'              => array(),
				),

				// Replies per page setting
				'_bbp_replies_per_rss_page' => array(
					'title'             => __( 'Replies', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_replies_per_rss_page',
					'sanitize_callback' => 'intval',
					'args'              => array(),
				),
			),

			/** Forum Permalink Slugs **/

			'bbp_settings_single_slugs' => array(

				// Forum slug setting
				'_bbp_forum_slug'     => array(
					'title'             => __( 'Forum', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_forum_slug',
					'sanitize_callback' => 'bbp_sanitize_slug',
					'args'              => array(),
				),

				// Topic slug setting
				'_bbp_topic_slug'     => array(
					'title'             => __( 'Discussion', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_topic_slug',
					'sanitize_callback' => 'bbp_sanitize_slug',
					'args'              => array(),
				),

				// Topic tag slug setting
				'_bbp_topic_tag_slug' => array(
					'title'             => __( 'Discussion Tag', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_topic_tag_slug',
					'sanitize_callback' => 'bbp_sanitize_slug',
					'args'              => array(),
				),

				// View slug setting
				'_bbp_view_slug'      => array(
					'title'             => __( 'Discussion View', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_view_slug',
					'sanitize_callback' => 'bbp_sanitize_slug',
					'args'              => array(),
				),

				// Reply slug setting
				'_bbp_reply_slug'     => array(
					'title'             => __( 'Reply', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_reply_slug',
					'sanitize_callback' => 'bbp_sanitize_slug',
					'args'              => array(),
				),

				// Search slug setting
				'_bbp_search_slug'    => array(
					'title'             => __( 'Search', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_search_slug',
					'sanitize_callback' => 'bbp_sanitize_slug',
					'args'              => array(),
				),
			),

			/** Forum Profile Permalinks **/

			'bbp_settings_user_slugs' => array(

				// Topics slug setting
//				'_bbp_topic_archive_slug' => array(
//					'title'             => esc_html__( 'Discussions Started', 'buddyboss' ),
//					'callback'          => 'bbp_admin_setting_callback_topic_archive_slug',
//					'sanitize_callback' => 'bbp_sanitize_slug',
//					'args'              => array( 'label_for'=>'_bbp_topic_archive_slug' )
//				),

				// Replies slug setting
				'_bbp_reply_archive_slug' => array(
					'title'             => esc_html__( 'Replies Created', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_reply_archive_slug',
					'sanitize_callback' => 'bbp_sanitize_slug',
					'args'              => array( 'label_for'=>'_bbp_reply_archive_slug' )
				),

				// Favorites slug setting
				'_bbp_user_favs_slug' => array(
					'title'             => esc_html__( 'Favorite Discussions', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_user_favs_slug',
					'sanitize_callback' => 'bbp_sanitize_slug',
					'args'              => array( 'label_for'=>'_bbp_user_favs_slug' )
				),

				// Subscriptions slug setting
				'_bbp_user_subs_slug' => array(
					'title'             => esc_html__( 'Subscriptions', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_user_subs_slug',
					'sanitize_callback' => 'bbp_sanitize_slug',
					'args'              => array( 'label_for'=>'_bbp_user_subs_slug' )
				)
			),

			/** Akismet Settings **/

			'bbp_settings_akismet'      => array(

				// Should we use Akismet
				'_bbp_enable_akismet' => array(
					'title'             => esc_html__( 'Akismet Spam Protection', 'buddyboss' ),
					'callback'          => 'bbp_admin_setting_callback_akismet',
					'sanitize_callback' => 'intval',
					'args'              => array(),
				),
			),
		)
	);

	return $fields;
}

/**
 * Get settings fields by section.
 *
 * @since bbPress (r4001)
 * @param string $section_id
 * @return mixed False if section is invalid, array of fields otherwise.
 */
function bbp_admin_get_settings_fields_for_section( $section_id = '' ) {

	// Bail if section is empty
	if ( empty( $section_id ) ) {
		return false;
	}

	$fields = bbp_admin_get_settings_fields();
	$retval = isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;

	return (array) apply_filters( 'bbp_admin_get_settings_fields_for_section', $retval, $section_id );
}

/** User Section **************************************************************/

/**
 * Edit lock setting field
 *
 * @since bbPress (r2737)
 *
 * @uses bbp_form_option() To output the option value
 */
function bbp_admin_setting_callback_editlock() {
	?>

	<input name="_bbp_edit_lock" id="_bbp_edit_lock" type="number" min="0" step="1" value="<?php bbp_form_option( '_bbp_edit_lock', '5' ); ?>" class="small-text"<?php bbp_maybe_admin_setting_disabled( '_bbp_edit_lock' ); ?> />
	<label for="_bbp_edit_lock"><?php esc_html_e( 'minutes', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Throttle setting field
 *
 * @since bbPress (r2737)
 *
 * @uses bbp_form_option() To output the option value
 */
function bbp_admin_setting_callback_throttle() {
	?>

	<input name="_bbp_throttle_time" id="_bbp_throttle_time" type="number" min="0" step="1" value="<?php bbp_form_option( '_bbp_throttle_time', '10' ); ?>" class="small-text"<?php bbp_maybe_admin_setting_disabled( '_bbp_throttle_time' ); ?> />
	<label for="_bbp_throttle_time"><?php esc_html_e( 'seconds', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Allow anonymous posting setting field
 *
 * @since bbPress (r2737)
 *
 * @uses checked() To display the checked attribute
 */
function bbp_admin_setting_callback_anonymous() {
	?>

	<input name="_bbp_allow_anonymous" id="_bbp_allow_anonymous" type="checkbox" value="1"
	<?php
	checked( bbp_allow_anonymous( false ) );
	bbp_maybe_admin_setting_disabled( '_bbp_allow_anonymous' );
	?>
	 />
	<label for="_bbp_allow_anonymous"><?php esc_html_e( 'Allow guest users without accounts to create discussions and replies', 'buddyboss' ); ?></label>

	<?php
}

/** Features Section **********************************************************/

/**
 * Allow favorites setting field
 *
 * @since bbPress (r2786)
 *
 * @uses checked() To display the checked attribute
 */
function bbp_admin_setting_callback_favorites() {
	?>

	<input name="_bbp_enable_favorites" id="_bbp_enable_favorites" type="checkbox" value="1"
	<?php
	checked( bbp_is_favorites_active( true ) );
	bbp_maybe_admin_setting_disabled( '_bbp_enable_favorites' );
	?>
	 />
	<label for="_bbp_enable_favorites"><?php esc_html_e( 'Allow members to mark discussions as favorites', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Allow subscriptions setting field
 *
 * @since bbPress (r2737)
 *
 * @uses checked() To display the checked attribute
 */
function bbp_admin_setting_callback_subscriptions() {
	?>

	<input name="_bbp_enable_subscriptions" id="_bbp_enable_subscriptions" type="checkbox" value="1"
	<?php
	checked( bbp_is_subscriptions_active( true ) );
	bbp_maybe_admin_setting_disabled( '_bbp_enable_subscriptions' );
	?>
	 />
	<label for="_bbp_enable_subscriptions"><?php esc_html_e( 'Allow members to subscribe to discussions and standalone forums', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Allow topic tags setting field
 *
 * @since bbPress (r4944)
 *
 * @uses checked() To display the checked attribute
 */
function bbp_admin_setting_callback_topic_tags() {
	?>

	<input name="_bbp_allow_topic_tags" id="_bbp_allow_topic_tags" type="checkbox" value="1"
	<?php
	checked( bbp_allow_topic_tags( true ) );
	bbp_maybe_admin_setting_disabled( '_bbp_allow_topic_tags' );
	?>
	 />
	<label for="_bbp_allow_topic_tags"><?php esc_html_e( 'Allow discussions to have tags', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Allow forum wide search
 *
 * @since bbPress (r4970)
 *
 * @uses checked() To display the checked attribute
 */
function bbp_admin_setting_callback_search() {
	?>

	<input name="_bbp_allow_search" id="_bbp_allow_search" type="checkbox" value="1"
	<?php
	checked( bbp_allow_search( true ) );
	bbp_maybe_admin_setting_disabled( '_bbp_allow_search' );
	?>
	 />
	<label for="_bbp_allow_search"><?php esc_html_e( 'Allow forum wide search', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Hierarchical reply maximum depth level setting field
 *
 * Replies will be threaded if depth is 2 or greater
 *
 * @since bbPress (r4944)
 *
 * @uses apply_filters() Calls 'bbp_thread_replies_depth_max' to set a
 *                        maximum displayed level
 * @uses selected() To display the selected attribute
 */
function bbp_admin_setting_callback_thread_replies_depth() {

	// Set maximum depth for dropdown
	$max_depth     = (int) apply_filters( 'bbp_thread_replies_depth_max', 10 );
	$current_depth = bbp_thread_replies_depth();

	// Start an output buffer for the select dropdown
	ob_start();
	?>

	<label for="_bbp_thread_replies_depth">
		<select name="_bbp_thread_replies_depth" id="_bbp_thread_replies_depth" <?php bbp_maybe_admin_setting_disabled( '_bbp_thread_replies_depth' ); ?>>
		<?php for ( $i = 2; $i <= $max_depth; $i++ ) : ?>

			<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $i, $current_depth ); ?>><?php echo esc_html( $i ); ?></option>

		<?php endfor; ?>
		</select>
	</label>
	<?php $select = ob_get_clean(); ?>

	<label for="_bbp_allow_threaded_replies">
		<input name="_bbp_allow_threaded_replies" id="_bbp_allow_threaded_replies" type="checkbox" value="1"
		<?php
		checked( '1', bbp_allow_threaded_replies( true ) );
		bbp_maybe_admin_setting_disabled( '_bbp_allow_threaded_replies' );
		?>
		 />
		<?php printf( esc_html__( 'Enable threaded (nested) replies %s levels deep', 'buddyboss' ), $select ); ?>
	</label>

	<?php
}

/**
 * Allow topic and reply revisions
 *
 * @since bbPress (r3412)
 *
 * @uses checked() To display the checked attribute
 */
function bbp_admin_setting_callback_revisions() {
	?>

	<input name="_bbp_allow_revisions" id="_bbp_allow_revisions" type="checkbox" value="1"
	<?php
	checked( bbp_allow_revisions( true ) );
	bbp_maybe_admin_setting_disabled( '_bbp_allow_revisions' );
	?>
	 />
	<label for="_bbp_allow_revisions"><?php esc_html_e( 'Allow discussion and reply revision logging', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Use the WordPress editor setting field
 *
 * @since bbPress (r3586)
 *
 * @uses checked() To display the checked attribute
 */
function bbp_admin_setting_callback_use_wp_editor() {
	?>

	<input name="_bbp_use_wp_editor" id="_bbp_use_wp_editor" type="checkbox" value="1"
	<?php
	checked( bbp_use_wp_editor( true ) );
	bbp_maybe_admin_setting_disabled( '_bbp_use_wp_editor' );
	?>
	 />
	<label for="_bbp_use_wp_editor"><?php esc_html_e( 'Add toolbar & buttons to textareas to help with HTML formatting', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Main subtheme section
 *
 * @since bbPress (r2786)
 */
function bbp_admin_setting_callback_subtheme_section() {
	?>

	<p><?php esc_html_e( 'How your forum content is displayed within your existing theme.', 'buddyboss' ); ?></p>

	<?php
}

/**
 * Use the WordPress editor setting field
 *
 * @since bbPress (r3586)
 *
 * @uses checked() To display the checked attribute
 */
function bbp_admin_setting_callback_subtheme_id() {

	// Declare locale variable
	$theme_options   = '';
	$current_package = bbp_get_theme_package_id( 'default' );

	// Note: This should never be empty. /templates/ is the
	// canonical backup if no other packages exist. If there's an error here,
	// something else is wrong.
	//
	// @see bbPress::register_theme_packages()
	foreach ( (array) bbpress()->theme_compat->packages as $id => $theme ) {
		$theme_options .= '<option value="' . esc_attr( $id ) . '"' . selected( $theme->id, $current_package, false ) . '>' . sprintf( esc_html__( '%1$s - %2$s', 'buddyboss' ), esc_html( $theme->name ), esc_html( str_replace( WP_CONTENT_DIR, '', $theme->dir ) ) ) . '</option>';
	}

	if ( ! empty( $theme_options ) ) :
		?>

		<select name="_bbp_theme_package_id" id="_bbp_theme_package_id" <?php bbp_maybe_admin_setting_disabled( '_bbp_theme_package_id' ); ?>><?php echo $theme_options; ?></select>
		<label for="_bbp_theme_package_id"><?php esc_html_e( 'will serve all Forums templates', 'buddyboss' ); ?></label>

	<?php else : ?>

		<p><?php esc_html_e( 'No template packages available.', 'buddyboss' ); ?></p>

		<?php
	endif;
}

/**
 * Allow oEmbed in replies
 *
 * @since bbPress (r3752)
 *
 * @uses checked() To display the checked attribute
 */
function bbp_admin_setting_callback_use_autoembed() {
	?>

	<input name="_bbp_use_autoembed" id="_bbp_use_autoembed" type="checkbox" value="1"
	<?php
	checked( bbp_use_autoembed( true ) );
	bbp_maybe_admin_setting_disabled( '_bbp_use_autoembed' );
	?>
	 />
	<label for="_bbp_use_autoembed"><?php esc_html_e( 'Embed media (YouTube, Twitter, Vimeo, etc&hellip;) directly into discussions and replies', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Forums per page setting field
 *
 * @since BuddyBoss 1.0.0
 *
 * @uses bbp_form_option() To output the option value
 */
function bbp_admin_setting_callback_forums_per_page() {
	?>

	<input name="_bbp_forums_per_page" id="_bbp_forums_per_page" type="number" min="1" step="1" value="<?php bbp_form_option( '_bbp_forums_per_page', '15' ); ?>" class="small-text"<?php bbp_maybe_admin_setting_disabled( '_bbp_forums_per_page' ); ?> />
	<label for="_bbp_forums_per_page"><?php esc_html_e( 'per page', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Topics per page setting field
 *
 * @since bbPress (r2786)
 *
 * @uses bbp_form_option() To output the option value
 */
function bbp_admin_setting_callback_topics_per_page() {
	?>

	<input name="_bbp_topics_per_page" id="_bbp_topics_per_page" type="number" min="1" step="1" value="<?php bbp_form_option( '_bbp_topics_per_page', '15' ); ?>" class="small-text"<?php bbp_maybe_admin_setting_disabled( '_bbp_topics_per_page' ); ?> />
	<label for="_bbp_topics_per_page"><?php esc_html_e( 'per page', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Replies per page setting field
 *
 * @since bbPress (r2786)
 *
 * @uses bbp_form_option() To output the option value
 */
function bbp_admin_setting_callback_replies_per_page() {
	?>

	<input name="_bbp_replies_per_page" id="_bbp_replies_per_page" type="number" min="1" step="1" value="<?php bbp_form_option( '_bbp_replies_per_page', '15' ); ?>" class="small-text"<?php bbp_maybe_admin_setting_disabled( '_bbp_replies_per_page' ); ?> />
	<label for="_bbp_replies_per_page"><?php esc_html_e( 'per page', 'buddyboss' ); ?></label>

	<?php
}

/** Per RSS Page Section ******************************************************/

/**
 * Topics per RSS page setting field
 *
 * @since bbPress (r2786)
 *
 * @uses bbp_form_option() To output the option value
 */
function bbp_admin_setting_callback_topics_per_rss_page() {
	?>

	<input name="_bbp_topics_per_rss_page" id="_bbp_topics_per_rss_page" type="number" min="1" step="1" value="<?php bbp_form_option( '_bbp_topics_per_rss_page', '25' ); ?>" class="small-text"<?php bbp_maybe_admin_setting_disabled( '_bbp_topics_per_rss_page' ); ?> />
	<label for="_bbp_topics_per_rss_page"><?php esc_html_e( 'per page', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Replies per RSS page setting field
 *
 * @since bbPress (r2786)
 *
 * @uses bbp_form_option() To output the option value
 */
function bbp_admin_setting_callback_replies_per_rss_page() {
	?>

	<input name="_bbp_replies_per_rss_page" id="_bbp_replies_per_rss_page" type="number" min="1" step="1" value="<?php bbp_form_option( '_bbp_replies_per_rss_page', '25' ); ?>" class="small-text"<?php bbp_maybe_admin_setting_disabled( '_bbp_replies_per_rss_page' ); ?> />
	<label for="_bbp_replies_per_rss_page"><?php esc_html_e( 'per page', 'buddyboss' ); ?></label>

	<?php
}

/** Slug Section **************************************************************/

/**
 * Slugs settings section description for the settings page
 *
 * @since bbPress (r2786)
 */
function bbp_admin_setting_callback_root_slug_section() {

	// Flush rewrite rules when this section is saved
	if ( isset( $_GET['edited'] ) && isset( $_GET['page'] ) ) {
		flush_rewrite_rules();
	}
	?>

	<?php
	printf(
	/* translators: Description. */
		'<p>%s</p>',
		sprintf(
		/* translators: Description with link. */
			__( 'Customize your Forums directory. Use %s for more flexibility.', 'buddyboss' ),
			sprintf(
			/* translators: 1: Link, 2: Text. */
				'<a href="%1$s">%2$s</a>',
				esc_url( bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => 83108,
						),
						'admin.php'
					)
				) ),
				esc_html__( 'Shortcodes', 'buddyboss' )
			)
		)
	);
}

/**
 * Root slug setting field
 *
 * @since bbPress (r2786)
 *
 * @uses bbp_form_option() To output the option value
 */
function bbp_admin_setting_callback_root_slug() {
	?>

		<input name="_bbp_root_slug" id="_bbp_root_slug" type="text" class="regular-text code" value="<?php bbp_form_option( '_bbp_root_slug', 'forums', true ); ?>"<?php bbp_maybe_admin_setting_disabled( '_bbp_root_slug' ); ?> />

	<?php
	// Slug Check
	bbp_form_slug_conflict_check( '_bbp_root_slug', 'forums' );
}

/**
 * Include root slug setting field
 *
 * @since bbPress (r2786)
 *
 * @uses checked() To display the checked attribute
 */
function bbp_admin_setting_callback_include_root() {
	?>

	<input name="_bbp_include_root" id="_bbp_include_root" type="checkbox" value="1"
	<?php
	checked( bbp_include_root_slug() );
	bbp_maybe_admin_setting_disabled( '_bbp_include_root' );
	?>
	 />
	<?php
	printf(
		'<label for="_bbp_include_root">%s</label>',
		sprintf(
			__( 'Prefix all forum content with the  <a href="%s">Forums page</a> slug (Recommended)', 'buddyboss' ),
			add_query_arg(
				array(
					'page' => 'bp-pages',
				),
				admin_url( 'admin.php' )
			)
		)
	);

}

/**
 * Include root slug setting field
 *
 * @since bbPress (r2786)
 *
 * @uses checked() To display the checked attribute
 */
function bbp_admin_setting_callback_show_on_root() {

	// Current setting
	$show_on_root = bbp_show_on_root();

	// Options for forum root output
	$root_options = array(
		'forums' => array(
			'name' => __( 'Forum Index', 'buddyboss' ),
		),
		'topics' => array(
			'name' => __( 'Discussions by Last Post', 'buddyboss' ),
		),
	);
	?>

	<select name="_bbp_show_on_root" id="_bbp_show_on_root" <?php bbp_maybe_admin_setting_disabled( '_bbp_show_on_root' ); ?>>

		<?php foreach ( $root_options as $option_id => $details ) : ?>

			<option <?php selected( $show_on_root, $option_id ); ?> value="<?php echo esc_attr( $option_id ); ?>"><?php echo esc_html( $details['name'] ); ?></option>

		<?php endforeach; ?>

	</select>

	<?php
}

/** Single Slugs **************************************************************/

/**
 * Slugs settings section description for the settings page
 *
 * @since bbPress (r2786)
 */
function bbp_admin_setting_callback_single_slug_section() {
	?>

	<p><?php printf( esc_html__( 'Custom URL slugs for Forum content. Slugs should be all lowercase and contain only letters, numbers, and hyphens.', 'buddyboss' ), get_admin_url( null, 'options-permalink.php' ) ); ?></p>
	<?php
}

/**
 * Forum slug setting field
 *
 * @since bbPress (r2786)
 *
 * @uses bbp_form_option() To output the option value
 */
function bbp_admin_setting_callback_forum_slug() {
	?>

	<input name="_bbp_forum_slug" id="_bbp_forum_slug" type="text" class="regular-text code" value="<?php bbp_form_option( '_bbp_forum_slug', 'forum', true ); ?>"<?php bbp_maybe_admin_setting_disabled( '_bbp_forum_slug' ); ?> />

	<?php
	// Slug Check
	bbp_form_slug_conflict_check( '_bbp_forum_slug', 'forum' );
}

/**
 * Topic slug setting field
 *
 * @since bbPress (r2786)
 *
 * @uses bbp_form_option() To output the option value
 */
function bbp_admin_setting_callback_topic_slug() {
	?>

	<input name="_bbp_topic_slug" id="_bbp_topic_slug" type="text" class="regular-text code" value="<?php bbp_form_option( '_bbp_topic_slug', 'discussion', true ); ?>"<?php bbp_maybe_admin_setting_disabled( '_bbp_topic_slug' ); ?> />

	<?php
	// Slug Check
	bbp_form_slug_conflict_check( '_bbp_topic_slug', 'discussion' );
}

/**
 * Reply slug setting field
 *
 * @since bbPress (r2786)
 *
 * @uses bbp_form_option() To output the option value
 */
function bbp_admin_setting_callback_reply_slug() {
	?>

	<input name="_bbp_reply_slug" id="_bbp_reply_slug" type="text" class="regular-text code" value="<?php bbp_form_option( '_bbp_reply_slug', 'reply', true ); ?>"<?php bbp_maybe_admin_setting_disabled( '_bbp_reply_slug' ); ?> />

	<?php
	// Slug Check
	bbp_form_slug_conflict_check( '_bbp_reply_slug', 'reply' );
}

/**
 * Topic tag slug setting field
 *
 * @since bbPress (r2786)
 *
 * @uses bbp_form_option() To output the option value
 */
function bbp_admin_setting_callback_topic_tag_slug() {
	?>

	<input name="_bbp_topic_tag_slug" id="_bbp_topic_tag_slug" type="text" class="regular-text code" value="<?php bbp_form_option( '_bbp_topic_tag_slug', 'discussion-tag', true ); ?>"<?php bbp_maybe_admin_setting_disabled( '_bbp_topic_tag_slug' ); ?> />

	<?php

	// Slug Check
	bbp_form_slug_conflict_check( '_bbp_topic_tag_slug', 'discussion-tag' );
}

/**
 * View slug setting field
 *
 * @since bbPress (r2789)
 *
 * @uses bbp_form_option() To output the option value
 */
function bbp_admin_setting_callback_view_slug() {
	?>

	<input name="_bbp_view_slug" id="_bbp_view_slug" type="text" class="regular-text code" value="<?php bbp_form_option( '_bbp_view_slug', 'view', true ); ?>"<?php bbp_maybe_admin_setting_disabled( '_bbp_view_slug' ); ?> />

	<?php
	// Slug Check
	bbp_form_slug_conflict_check( '_bbp_view_slug', 'view' );
}

/**
 * Search slug setting field
 *
 * @since bbPress (r4579)
 *
 * @uses bbp_form_option() To output the option value
 */
function bbp_admin_setting_callback_search_slug() {
	?>

	<input name="_bbp_search_slug" id="_bbp_search_slug" type="text" class="regular-text code" value="<?php bbp_form_option( '_bbp_search_slug', 'search', true ); ?>"<?php bbp_maybe_admin_setting_disabled( '_bbp_search_slug' ); ?> />

	<?php
	// Slug Check
	bbp_form_slug_conflict_check( '_bbp_search_slug', 'search' );
}

/** BuddyBoss ****************************************************************/

/**
 * Allow BuddyBoss group forums setting field
 *
 * @since bbPress (r3575)
 *
 * @uses checked() To display the checked attribute
 */
function bbp_admin_setting_callback_group_forums() {
	?>

	<input name="_bbp_enable_group_forums" id="_bbp_enable_group_forums" type="checkbox" value="1"
	<?php
	checked( bbp_is_group_forums_active( true ) );
	bbp_maybe_admin_setting_disabled( '_bbp_enable_group_forums' );
	?>
	 />
	<label for="_bbp_enable_group_forums"><?php esc_html_e( 'Allow social groups to have their own forums', 'buddyboss' ); ?></label>
	<?php
}

/**
 * Replies per page setting field
 *
 * @since bbPress (r3575)
 *
 * @uses bbp_form_option() To output the option value
 */
function bbp_admin_setting_callback_group_forums_root_id() {

	// Output the dropdown for all forums
	bbp_dropdown(
		array(
			'selected'           => bbp_get_group_forums_root_id(),
			'show_none'          => __( '- Forums Directory -', 'buddyboss' ),
			'orderby'            => 'title',
			'order'              => 'ASC',
			'select_id'          => '_bbp_group_forums_root_id',
			'disable_categories' => false,
			'disabled'           => '_bbp_group_forums_root_id',
		)
	);
	?>

	<label for="_bbp_group_forums_root_id"><?php esc_html_e( 'is the parent for all group forums', 'buddyboss' ); ?></label>
	<p class="description"><?php esc_html_e( 'Changing this does not move existing forums.', 'buddyboss' ); ?></p>

	<?php
}

/** Akismet *******************************************************************/

/**
 * Allow Akismet setting field
 *
 * @since bbPress (r3575)
 *
 * @uses checked() To display the checked attribute
 */
function bbp_admin_setting_callback_akismet() {
	?>

	<input name="_bbp_enable_akismet" id="_bbp_enable_akismet" type="checkbox" value="1"
	<?php
	checked( bbp_is_akismet_active( true ) );
	bbp_maybe_admin_setting_disabled( '_bbp_enable_akismet' );
	?>
	 />
	<?php
	printf(
	/* translators: Description. */
		'<label for="_bbp_enable_akismet">%s</label>',
		sprintf(
		/* translators: Description with link. */
			esc_html__( 'Allow %s spam filtering to actively prevent forum spam.', 'buddyboss' ),
			sprintf(
			/* translators: 1: Link, 2: Text. */
				'<a href="%1$s" target="_blank">%2$s</a>',
				esc_url( 'https://akismet.com/' ),
				esc_html__( 'Akismet', 'buddyboss' )
			)
		)
	);
}

/** Settings Page *************************************************************/

/**
 * The main settings page
 *
 * @since bbPress (r2643)
 *
 * @uses settings_fields() To output the hidden fields for the form
 * @uses do_settings_sections() To output the settings sections
 */
function bbp_admin_settings() {
	?>

	<div class="wrap">

		<h2><?php esc_html_e( 'Forums Settings', 'buddyboss' ); ?></h2>

		<form action="options.php" method="post">

			<?php settings_fields( 'bbpress' ); ?>

			<?php do_settings_sections( 'bbpress' ); ?>

			<p class="submit">
				<input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'buddyboss' ); ?>" />
			</p>
		</form>
	</div>

	<?php
}


/** Converter Section *********************************************************/

/**
 * Main settings section description for the settings page
 *
 * @param $args array Array of section data.
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_main_section( $args ) {
	?>
	<h2>
		<?php
		if ( isset( $args['icon'] ) && ! empty( $args['icon'] ) ) {
			?>
			<i class="<?php echo esc_attr( $args['icon'] ); ?>"></i>
			<?php
		}
		esc_html_e( 'Import Forums', 'buddyboss' );
		?>
	</h2>
	<h3><?php esc_html_e( 'Database Settings', 'buddyboss' ); ?></h3>
	<p><?php _e( 'Information about your previous forums database so that they can be converted. <strong>Backup your database before proceeding.</strong>', 'buddyboss' ); ?></p>

	<?php
}

/**
 * Edit Platform setting field
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_platform() {

	$current          = bbp_get_form_option( '_bbp_converter_platform' );
	$platform_options = '';
	$curdir           = opendir( bbpress()->admin->admin_dir . 'converters/' );

	// Bail if no directory was found (how did this happen?)
	if ( empty( $curdir ) ) {
		return;
	}

	// Loop through files in the converters folder and assemble some options
	while ( $file = readdir( $curdir ) ) {
		if ( ( stristr( $file, '.php' ) ) && ( stristr( $file, 'index' ) === false ) ) {
			$file              = preg_replace( '/.php/', '', $file );
			$platform_options .= '<option value="' . $file . '"' . selected( $file, $current, false ) . '>' . esc_html( $file ) . '</option>';
		}
	}

	closedir( $curdir );
	?>

	<select name="_bbp_converter_platform" id="_bbp_converter_platform"><?php echo $platform_options; ?></select>
	<label for="_bbp_converter_platform"><?php esc_html_e( 'is the previous forum software', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Edit Database Server setting field
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_dbserver() {
	?>

	<input name="_bbp_converter_db_server" id="_bbp_converter_db_server" type="text" value="<?php bbp_form_option( '_bbp_converter_db_server', 'localhost' ); ?>" class="medium-text" />
	<label for="_bbp_converter_db_server"><?php esc_html_e( 'IP or hostname', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Edit Database Server Port setting field
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_dbport() {
	?>

	<input name="_bbp_converter_db_port" id="_bbp_converter_db_port" type="text" value="<?php bbp_form_option( '_bbp_converter_db_port', '3306' ); ?>" class="small-text" />
	<label for="_bbp_converter_db_port"><?php esc_html_e( 'Use default 3306 if unsure', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Edit Database User setting field
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_dbuser() {
	?>

	<input name="_bbp_converter_db_user" id="_bbp_converter_db_user" type="text" value="<?php bbp_form_option( '_bbp_converter_db_user' ); ?>" class="medium-text" />
	<label for="_bbp_converter_db_user"><?php esc_html_e( 'User for your database connection', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Edit Database Pass setting field
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_dbpass() {
	?>

	<div class="_bbp_converter_db_pass_wrap">
		<input name="_bbp_converter_db_pass" id="_bbp_converter_db_pass" type="password" value="<?php bbp_form_option( '_bbp_converter_db_pass' ); ?>" class="medium-text" />
		<i class="bb-icon-l bb-icon-eye bbp-db-pass-toggle"></i>
	</div>
	<label for="_bbp_converter_db_pass"><?php esc_html_e( 'Password to access the database', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Edit Database Name setting field
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_dbname() {
	?>

	<input name="_bbp_converter_db_name" id="_bbp_converter_db_name" type="text" value="<?php bbp_form_option( '_bbp_converter_db_name' ); ?>" class="medium-text" />
	<label for="_bbp_converter_db_name"><?php esc_html_e( 'Name of the database with your old forum data', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Main settings section description for the settings page
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_options_section() {
	?>
	<h3><?php _e( 'Options', 'buddyboss' ); ?></h3>
	<p><?php esc_html_e( 'Some optional parameters to help tune the conversion process.', 'buddyboss' ); ?></p>

	<?php
}

/**
 * Edit Table Prefix setting field
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_dbprefix() {
	?>

	<input name="_bbp_converter_db_prefix" id="_bbp_converter_db_prefix" type="text" value="<?php bbp_form_option( '_bbp_converter_db_prefix' ); ?>" class="medium-text" />
	<label for="_bbp_converter_db_prefix"><?php esc_html_e( '(If converting from BuddyBoss Forums, use "wp_bb_" or your custom prefix)', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Edit Rows Limit setting field
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_rows() {
	?>

	<input name="_bbp_converter_rows" id="_bbp_converter_rows" type="text" value="<?php bbp_form_option( '_bbp_converter_rows', '100' ); ?>" class="small-text" />
	<label for="_bbp_converter_rows"><?php esc_html_e( 'rows to process at a time', 'buddyboss' ); ?></label>
	<p class="description"><?php esc_html_e( 'Keep this low if you experience out-of-memory issues.', 'buddyboss' ); ?></p>

	<?php
}

/**
 * Edit Delay Time setting field
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_delay_time() {
	?>

	<input name="_bbp_converter_delay_time" id="_bbp_converter_delay_time" type="text" value="<?php bbp_form_option( '_bbp_converter_delay_time', '1' ); ?>" class="small-text" />
	<label for="_bbp_converter_delay_time"><?php esc_html_e( 'second(s) delay between each group of rows', 'buddyboss' ); ?></label>
	<p class="description"><?php esc_html_e( 'Keep this high to prevent too-many-connection issues.', 'buddyboss' ); ?></p>

	<?php
}

/**
 * Edit Restart setting field
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_restart() {
	?>

	<input name="_bbp_converter_restart" id="_bbp_converter_restart" type="checkbox" value="1" <?php checked( get_option( '_bbp_converter_restart', false ) ); ?> />
	<label for="_bbp_converter_restart"><?php esc_html_e( 'Start a fresh conversion from the beginning', 'buddyboss' ); ?></label>
	<p class="description"><?php esc_html_e( 'You should clean old conversion information before starting over.', 'buddyboss' ); ?></p>

	<?php
}

/**
 * Edit Clean setting field
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_clean() {
	?>

	<input name="_bbp_converter_clean" id="_bbp_converter_clean" type="checkbox" value="1" <?php checked( get_option( '_bbp_converter_clean', false ) ); ?> />
	<label for="_bbp_converter_clean"><?php esc_html_e( 'Purge all information from a previously attempted import', 'buddyboss' ); ?></label>
	<p class="description"><?php esc_html_e( 'Use this if an import failed and you want to remove that incomplete data.', 'buddyboss' ); ?></p>

	<?php
}

/**
 * Edit Convert Users setting field
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_convert_users() {
	?>

	<input name="_bbp_converter_convert_users" id="_bbp_converter_convert_users" type="checkbox" value="1" <?php checked( get_option( '_bbp_converter_convert_users', false ) ); ?> />
	<label for="_bbp_converter_convert_users"><?php esc_html_e( 'Attempt to import user accounts from previous forums', 'buddyboss' ); ?></label>
	<p class="description"><?php esc_html_e( 'Non-Forums passwords cannot be automatically converted. They will be converted as each user logs in.', 'buddyboss' ); ?></p>

	<?php
}

/** Converter Page ************************************************************/

/**
 * The main settings page
 *
 * @uses settings_fields() To output the hidden fields for the form
 * @uses do_settings_sections() To output the settings sections
 */
function bbp_converter_settings() {

	// Status.
	$step = (int) get_option( '_bbp_converter_step', 0 );
	$max  = (int) bbpress()->admin->converter->max_steps;

	// Starting or continuing?
	$status_text = ! empty( $step )
		? sprintf( esc_html__( 'Up next: step %s', 'buddyboss' ), $step )
		: esc_html__( 'Ready', 'buddyboss' );

	// Starting or continuing?
	$start_text = ! empty( $step )
		? esc_html__( 'Resume', 'buddyboss' )
		: esc_html__( 'Start', 'buddyboss' );

	// Starting or continuing?
	$progress_text = ! empty( $step )
		? sprintf( esc_html__( 'Previously stopped at step %1$d of %2$d', 'buddyboss' ), $step, $max )
		: esc_html__( 'Ready to go.', 'buddyboss' );
	?>

	<div class="wrap">
		<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Tools', 'buddyboss' ) ); ?></h2>
		<div class="nav-settings-subsubsub">
			<ul class="subsubsub">
				<?php bp_core_tools_settings_admin_tabs(); ?>
			</ul>
		</div>
	</div>
	<div class="wrap">
		<div class="bp-admin-card">

			<form action="#" method="post" id="bbp-converter-settings">

				<?php settings_fields( 'bbpress_converter' ); ?>

				<?php do_settings_sections( 'bbpress_converter' ); ?>

				<p class="submit">
					<input type="button" name="submit" class="button-primary" id="bbp-converter-start" value="<?php echo esc_attr( $start_text ); ?>" />
					<input type="button" name="submit" class="button-primary" id="bbp-converter-stop" value="<?php esc_attr_e( 'Pause', 'buddyboss' ); ?>" />
					<span class="spinner" id="bbp-converter-spinner"></span>
				</p>

				<div class="bbp-converter-states" id="bbp-converter-state-message" <?php echo ! empty( $step ) ? 'style="display:block;"' : ''; ?>>
					<span id="bbp-converter-label"><?php esc_attr_e( 'Import Monitor', 'buddyboss' ); ?></span>
					<span id="bbp-converter-status"><?php echo esc_html( $status_text ); ?></span>
					<span id="bbp-converter-step-percentage" class="bbp-progress-bar"></span>
					<span id="bbp-converter-total-percentage" class="bbp-progress-bar"></span>
				</div>
				<div class="bbp-converter-updated" id="bbp-converter-message" <?php echo ! empty( $step ) ? 'style="display:block;"' : ''; ?>>
					<p><?php echo esc_html( $progress_text ); ?></p>
				</div>
			</form>

		</div>
	</div>

	<?php
}

/** Helpers *******************************************************************/

/**
 * Contextual help for Forums settings page
 *
 * @since bbPress (r3119)
 * @uses get_current_screen()
 */
function bbp_admin_settings_help() {

	$current_screen = get_current_screen();

	// Bail if current screen could not be found
	if ( empty( $current_screen ) ) {
		return;
	}

	// Overview
	$current_screen->add_help_tab(
		array(
			'id'      => 'overview',
			'title'   => __( 'Overview', 'buddyboss' ),
			'content' => '<p>' . __( 'This screen provides access to all of the Forums settings.', 'buddyboss' ) . '</p>' .
						 '<p>' . __( 'Please see the additional help tabs for more information on each indiviual section.', 'buddyboss' ) . '</p>',
		)
	);

	// Main Settings
	$current_screen->add_help_tab(
		array(
			'id'      => 'main_settings',
			'title'   => __( 'Main Settings', 'buddyboss' ),
			'content' => '<p>' . __( 'In the Main Settings you have a number of options:', 'buddyboss' ) . '</p>' .
						 '<p>' .
							'<ul>' .
								'<li>' . __( 'You can choose to lock a post after a certain number of minutes. "Locking post editing" will prevent the author from editing some amount of time after saving a post.', 'buddyboss' ) . '</li>' .
								'<li>' . __( '"Throttle time" is the amount of time required between posts from a single author. The higher the throttle time, the longer a user will need to wait between posting to the forum.', 'buddyboss' ) . '</li>' .
								'<li>' . __( 'Saving discussions allows users to return later to discussions they are interested in. This is enabled by default.', 'buddyboss' ) . '</li>' .
								'<li>' . __( 'Subscriptions allow users to subscribe for notifications to discussions that interest them. This is enabled by default.', 'buddyboss' ) . '</li>' .
								'<li>' . __( 'Discussion-Tags allow users to filter discussions between forums. This is enabled by default.', 'buddyboss' ) . '</li>' .
								'<li>' . __( '"Anonymous Posting" allows guest users who do not have accounts on your site to both create discussions as well as replies.', 'buddyboss' ) . '</li>' .
								'<li>' . __( 'The Fancy Editor brings the luxury of the Visual editor and HTML editor from the traditional WordPress dashboard into your theme.', 'buddyboss' ) . '</li>' .
								'<li>' . __( 'Auto-embed will embed the media content from a URL directly into the replies. For example: links to Flickr and YouTube.', 'buddyboss' ) . '</li>' .
							'</ul>' .
						'</p>' .
						'<p>' . __( 'You must click the Save Changes button at the bottom of the screen for new settings to take effect.', 'buddyboss' ) . '</p>',
		)
	);

	// Per Page
	$current_screen->add_help_tab(
		array(
			'id'      => 'per_page',
			'title'   => __( 'Per Page', 'buddyboss' ),
			'content' => '<p>' . __( 'Per Page settings allow you to control the number of discussions and replies to appear on each page.', 'buddyboss' ) . '</p>' .
						 '<p>' . __( 'This is comparable to the WordPress "Reading Settings" page, where you can set the number of posts that should show on blog pages and in feeds.', 'buddyboss' ) . '</p>' .
						 '<p>' . __( 'These are broken up into two separate groups: one for what appears in your theme, another for RSS feeds.', 'buddyboss' ) . '</p>',
		)
	);

	// Slugs
	$current_screen->add_help_tab(
		array(
			'id'      => 'slus',
			'title'   => __( 'Slugs', 'buddyboss' ),
			'content' => '<p>' . __( 'The Slugs section allows you to control the permalink structure for your forums.', 'buddyboss' ) . '</p>' .
						 '<p>' . __( '"Archive Slugs" are used as the "root" for your forums and discussions. If you combine these values with existing page slugs, Forums will attempt to output the most correct title and content.', 'buddyboss' ) . '</p>' .
						 '<p>' . __( '"Single Slugs" are used as a prefix when viewing an individual forum, discussion, reply, user, or view.', 'buddyboss' ) . '</p>' .
						 '<p>' . __( 'In the event of a slug collision with WordPress or BuddyBoss, a warning will appear next to the problem slug(s).', 'buddyboss' ) . '</p>',
		)
	);

	// Help Sidebar
	$current_screen->set_help_sidebar(
		'<p><strong>' . __( 'For more information:', 'buddyboss' ) . '</strong></p>' .
		'<p>' . __( '<a href="https://www.buddyboss.com/resources/">Documentation</a>', 'buddyboss' ) . '</p>'
	);
}

/**
 * Disable a settings field if the value is forcibly set in Forums' global
 * options array.
 *
 * @since bbPress (r4347)
 *
 * @param string $option_key
 */
function bbp_maybe_admin_setting_disabled( $option_key = '' ) {
	disabled( isset( bbpress()->options[ $option_key ] ) );
}

/**
 * Output settings API option
 *
 * @since bbPress (r3203)
 *
 * @uses bbp_get_bbp_form_option()
 *
 * @param string $option
 * @param string $default
 * @param bool   $slug
 */
function bbp_form_option( $option, $default = '', $slug = false ) {
	echo bbp_get_form_option( $option, $default, $slug );
}
	/**
	 * Return settings API option
	 *
	 * @since bbPress (r3203)
	 *
	 * @uses get_option()
	 * @uses esc_attr()
	 * @uses apply_filters()
	 *
	 * @param string $option
	 * @param string $default
	 * @param bool   $slug
	 */
function bbp_get_form_option( $option, $default = '', $slug = false ) {

	// Get the option and sanitize it
	$value = get_option( $option, $default );

	// Slug?
	if ( true === $slug ) {
		$value = esc_attr( apply_filters( 'editable_slug', $value ) );

		// Not a slug
	} else {
		$value = esc_attr( $value );
	}

	// Fallback to default
	if ( empty( $value ) ) {
		$value = $default;
	}

	// Allow plugins to further filter the output
	return apply_filters( 'bbp_get_form_option', $value, $option );
}

/**
 * Used to check if a Forums slug conflicts with an existing known slug.
 *
 * @since bbPress (r3306)
 *
 * @param string $slug
 * @param string $default
 *
 * @uses bbp_get_form_option() To get a sanitized slug string
 */
function bbp_form_slug_conflict_check( $slug, $default ) {

	// Only set the slugs once ver page load
	static $the_core_slugs = array();

	// Get the form value
	$this_slug = bbp_get_form_option( $slug, $default, true );

	if ( empty( $the_core_slugs ) ) {

		// Slugs to check
		$core_slugs = apply_filters(
			'bbp_slug_conflict_check',
			array(

				/** WordPress Core */

				// Core Post Types
				'post_base'               => array(
					'name'    => __( 'Posts', 'buddyboss' ),
					'default' => 'post',
					'context' => 'WordPress',
				),
				'page_base'               => array(
					'name'    => __( 'Pages', 'buddyboss' ),
					'default' => 'page',
					'context' => 'WordPress',
				),
				'revision_base'           => array(
					'name'    => __( 'Revisions', 'buddyboss' ),
					'default' => 'revision',
					'context' => 'WordPress',
				),
				'attachment_base'         => array(
					'name'    => __( 'Attachments', 'buddyboss' ),
					'default' => 'attachment',
					'context' => 'WordPress',
				),
				'nav_menu_base'           => array(
					'name'    => __( 'Menus', 'buddyboss' ),
					'default' => 'nav_menu_item',
					'context' => 'WordPress',
				),

				// Post Tags
				'tag_base'                => array(
					'name'    => __( 'Tag base', 'buddyboss' ),
					'default' => 'tag',
					'context' => 'WordPress',
				),

				// Post Categories
				'category_base'           => array(
					'name'    => __( 'Category base', 'buddyboss' ),
					'default' => 'category',
					'context' => 'WordPress',
				),

				/** Forums Core */

				// Forum archive slug
				'_bbp_root_slug'          => array(
					'name'    => __( 'Forums base', 'buddyboss' ),
					'default' => 'forums',
					'context' => 'Forums',
				),

				// Topic archive slug
				'_bbp_topic_archive_slug' => array(
					'name'    => __( 'Discussions base', 'buddyboss' ),
					'default' => 'discussions',
					'context' => 'Forums',
				),

				// Forum slug
				'_bbp_forum_slug'         => array(
					'name'    => __( 'Forum slug', 'buddyboss' ),
					'default' => 'forum',
					'context' => 'Forums',
				),

				// Topic slug
				'_bbp_topic_slug'         => array(
					'name'    => __( 'Discussion slug', 'buddyboss' ),
					'default' => 'discussion',
					'context' => 'Forums',
				),

				// Reply slug
				'_bbp_reply_slug'         => array(
					'name'    => __( 'Reply slug', 'buddyboss' ),
					'default' => 'reply',
					'context' => 'Forums',
				),

				// User profile slug
				'_bbp_user_slug'          => array(
					'name'    => __( 'User base', 'buddyboss' ),
					'default' => 'users',
					'context' => 'Forums',
				),

				// View slug
				'_bbp_view_slug'          => array(
					'name'    => __( 'View base', 'buddyboss' ),
					'default' => 'view',
					'context' => 'Forums',
				),

				// Topic tag slug
				'_bbp_topic_tag_slug'     => array(
					'name'    => __( 'Discussion tag slug', 'buddyboss' ),
					'default' => 'discussion-tag',
					'context' => 'Forums',
				),
			)
		);

		/** BuddyBoss Core */

		if ( defined( 'BP_PLATFORM_VERSION' ) ) {
			$bp = buddypress();

			// Loop through root slugs and check for conflict
			if ( ! empty( $bp->pages ) ) {
				foreach ( $bp->pages as $page => $page_data ) {
					$page_base                = $page . '_base';
					$page_title               = sprintf( __( '%s page', 'buddyboss' ), $page_data->title );
					$core_slugs[ $page_base ] = array(
						'name'    => $page_title,
						'default' => $page_data->slug,
						'context' => 'BuddyPress',
					);
				}
			}
		}

		// Set the static
		$the_core_slugs = apply_filters( 'bbp_slug_conflict', $core_slugs );
	}

	// Loop through slugs to check
	foreach ( $the_core_slugs as $key => $value ) {

		// Get the slug
		$slug_check = bbp_get_form_option( $key, $value['default'], true );

		// Compare
		if ( ( $slug !== $key ) && ( $slug_check === $this_slug ) ) :
			?>

			<span class="attention"><?php printf( esc_html__( 'Possible %1$s conflict: %2$s', 'buddyboss' ), $value['context'], '<strong>' . $value['name'] . '</strong>' ); ?></span>

			<?php
		endif;
	}
}

/** User Slug Section *********************************************************/

/**
 * Slugs settings section description for the settings page
 *
 * @since 2.0.0 bbPress (r2786)
 */
function bbp_admin_setting_callback_user_slug_section() {
	?>

	<p><?php esc_html_e( 'Custom URL slugs for the Forums tab in member profiles. Slugs should be all lowercase and contain only letters, numbers, and hyphens.', 'buddyboss' ); ?></p>

	<?php
}

/**
 * Topic archive slug setting field
 *
 * @since 2.0.0 bbPress (r2786)
 */
function bbp_admin_setting_callback_topic_archive_slug() {
	?>

	<input name="_bbp_topic_archive_slug" id="_bbp_topic_archive_slug" type="text" class="regular-text code" value="<?php bbp_form_option( '_bbp_topic_archive_slug', 'topics', true ); ?>"<?php bbp_maybe_admin_setting_disabled( '_bbp_topic_archive_slug' ); ?> />

	<?php
	// Slug Check
	bbp_form_slug_conflict_check( '_bbp_topic_archive_slug', 'topics' );
}

/**
 * Reply archive slug setting field
 *
 * @since 2.4.0 bbPress (r4932)
 */
function bbp_admin_setting_callback_reply_archive_slug() {
	?>

	<input name="_bbp_reply_archive_slug" id="_bbp_reply_archive_slug" type="text" class="regular-text code" value="<?php bbp_form_option( '_bbp_reply_archive_slug', 'replies', true ); ?>"<?php bbp_maybe_admin_setting_disabled( '_bbp_reply_archive_slug' ); ?> />

	<?php
	// Slug Check
	bbp_form_slug_conflict_check( '_bbp_reply_archive_slug', 'replies' );
}

/**
 * Favorites slug setting field
 *
 * @since 2.4.0 bbPress (r4932)
 */
function bbp_admin_setting_callback_user_favs_slug() {
	?>

	<input name="_bbp_user_favs_slug" id="_bbp_user_favs_slug" type="text" class="regular-text code" value="<?php bbp_form_option( '_bbp_user_favs_slug', 'favorites', true ); ?>"<?php bbp_maybe_admin_setting_disabled( '_bbp_user_favs_slug' ); ?> />

	<?php
	// Slug Check
	bbp_form_slug_conflict_check( '_bbp_user_favs_slug', 'favorites' );
}

/**
 * Subscriptions slug setting field
 *
 * @since 2.4.0 bbPress (r4932)
 */
function bbp_admin_setting_callback_user_subs_slug() {
	?>

	<input name="_bbp_user_subs_slug" id="_bbp_user_subs_slug" type="text" class="regular-text code" value="<?php bbp_form_option( '_bbp_user_subs_slug', 'subscriptions', true ); ?>"<?php bbp_maybe_admin_setting_disabled( '_bbp_user_subs_slug' ); ?> />

	<?php
	// Slug Check
	bbp_form_slug_conflict_check( '_bbp_user_subs_slug', 'subscriptions' );
}
