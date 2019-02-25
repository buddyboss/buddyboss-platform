<?php
/**
 * @package WordPress
 * @subpackage BuddyBoss Media
 *
 * @todo Better logging, log to file, debug mode, remote error messages/notifications
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handle logging
 *
 * @param  string $msg Log message
 * @return void
 */
function buddyboss_media_log( $msg )
{
  global $buddyboss_media;

  // $buddyboss_media->log[] = $msg;
}

/**
 * Print log at footer
 *
 * @return void
 */
function buddyboss_media_print_log()
{
  ?>
  <div class="buddyboss-media-log">
    <pre>
      <?php print_r( $buddyboss_media->log ); ?>
    </pre>

    <br/><br/>
    <hr/>
  </div>
  <?php
}
// add_action( 'wp_footer', 'buddyboss_media_print_log' );

/**
 * Get the default slug used by buddyboss media component.
 *
 * @return string
 */
function buddyboss_media_default_component_slug(){
	return 'photos';
}

/**
 * Get the correct slug used by buddyboss media component.
 * The slug is configurable from settings.
 *
 * @return string
 */
function buddyboss_media_component_slug(){
	return buddyboss_media()->types->photo->slug;
}

/**
 * Checks if the ajax request is made from global media page.
 *
 * @since 1.1
 * @return boolean
 */
function buddyboss_media_cookies_is_global_media_page(){
	$is_global_media_page = false;

	if ( defined('DOING_AJAX') && DOING_AJAX && isset( $_COOKIE['bp-bboss-is-media-page'] ) ) {
		if( $_COOKIE['bp-bboss-is-media-page']=='yes' ){
			$is_global_media_page = true;
		}
	}

	return $is_global_media_page;
}

/**
 * Check if buddyboss media listing is being dispalyed.
 * This might be the photos component under user profile or the global media page.
 *
 * @since 2.0
 * @return boolean
 */
function buddyboss_media_is_media_listing(){
	$is_media_listing = false;
	if(
			buddyboss_media_cookies_is_global_media_page()
			|| ( buddyboss_media()->option('all-media-page') && is_page( buddyboss_media()->option('all-media-page') ) )
			|| ( bp_is_user() && bp_is_current_component( buddyboss_media_component_slug() ) )
			|| ( bp_is_group() &&  ( bbm_is_group_media_screen( 'uploads' ) || bbm_is_group_media_screen( 'albums' ) ) )
		){
		$is_media_listing = true;
	}
	return $is_media_listing;
}

/*
 * @todo: make the sql filterable, e.g: to add custom conditions
 */
function buddyboss_media_screen_content_pages_sql( $sql ){
	/*
	 * $pages_sql = "SELECT COUNT(*) FROM $activity_table a
                INNER JOIN $activity_meta_table am ON a.id = am.activity_id
                LEFT JOIN (SELECT id FROM $groups_table WHERE status != 'public' ) grp ON a.item_id = grp.id
                WHERE a.user_id = $user_id
                AND (am.meta_key = 'buddyboss_media_aid' OR am.meta_key = 'buddyboss_pics_aid' OR am.meta_key = 'bboss_pics_aid')
                AND (a.component != 'groups' || a.item_id != grp.id)";
	 */
	$activity_table = bp_core_get_table_prefix() . 'bp_activity';
	$activity_meta_table = bp_core_get_table_prefix() . 'bp_activity_meta';
	$groups_table = bp_core_get_table_prefix() . 'bp_groups';

	return "SELECT COUNT(*) FROM $activity_table a
                INNER JOIN $activity_meta_table am ON a.id = am.activity_id
                LEFT JOIN (SELECT id FROM $groups_table WHERE status != 'public' ) grp ON a.item_id = grp.id
                WHERE 1=1 
                AND (am.meta_key = 'buddyboss_media_aid' OR am.meta_key = 'buddyboss_pics_aid' OR am.meta_key = 'bboss_pics_aid')
                AND (a.component != 'groups' || a.item_id != grp.id)";
}

/*
 * @todo: make the sql filterable, e.g: to perform custom orderby queries
 */
function buddyboss_media_screen_content_sql( $sql ){
	/*
		$sql = "SELECT a.*, am.meta_value FROM $activity_table a
          INNER JOIN $activity_meta_table am ON a.id = am.activity_id
          LEFT JOIN (SELECT id FROM $groups_table WHERE status != 'public' ) grp ON a.item_id = grp.id
          WHERE a.user_id = $user_id
          AND (am.meta_key = 'buddyboss_media_aid' OR am.meta_key = 'buddyboss_pics_aid' OR am.meta_key = 'bboss_pics_aid')
          AND (a.component != 'groups' || a.item_id != grp.id)
          ORDER BY a.date_recorded DESC";
	 */
	$activity_table = bp_core_get_table_prefix() . 'bp_activity';
	$activity_meta_table = bp_core_get_table_prefix() . 'bp_activity_meta';
	$groups_table = bp_core_get_table_prefix() . 'bp_groups';

	return "SELECT a.*, am.meta_value FROM $activity_table a
          INNER JOIN $activity_meta_table am ON a.id = am.activity_id
          LEFT JOIN (SELECT id FROM $groups_table WHERE status != 'public' ) grp ON a.item_id = grp.id
          WHERE 1=1 
          AND (am.meta_key = 'buddyboss_media_aid' OR am.meta_key = 'buddyboss_pics_aid' OR am.meta_key = 'bboss_pics_aid')
          AND (a.component != 'groups' || a.item_id != grp.id)
          ORDER BY a.date_recorded DESC";
}

//Update buddyboss_media table
function bbm_update_media_table( $attachment_id, $media_title, $activity_id, $media_privacy  ) {

	global $wpdb;

	$wpdb->insert(
			$wpdb->prefix . 'buddyboss_media', array(
				'blog_id' => get_current_blog_id(),
				'media_id' => $attachment_id,
				'media_author' => get_current_user_id(),
				'media_title' => $media_title,
				'activity_id' => $activity_id,
				'privacy' => $media_privacy,
				'upload_date' => current_time( 'mysql' ),
			),
			array(
				'%d',
				'%d',
				'%d',
				'%s',
				'%d',
				'%s',
				'%d',
			)
	);
}

/**
 * Determine whether media entry present in table or not
 * @param $activity_id
 * @param $media_id
 * @return bool
 */
function bbm_is_media_row_exists( $activity_id, $media_id ) {
	global $wpdb;

	$query = $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}buddyboss_media WHERE activity_id = %d AND media_id = %d",
			(int) $activity_id,
			(int) $media_id
	);

	$id = $wpdb->get_var( $query );

	return is_numeric( $id );
}

/**
 * Delete media before an activity item proceeds to be deleted.
 * @param $args
 */
function bbm_delete_row_media_table( $activity_ids_deleted ) {

	global $wpdb;

	foreach ( $activity_ids_deleted as $activity_id ) {

		//Check delete media marked yes
		$delete_media_permanently = buddyboss_media()->option( 'delete_media_permanently' );

		if ( 'yes' == $delete_media_permanently ) {

			$activities    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->base_prefix}bp_activity WHERE id = %d", $activity_id ) );
			$act_obj       = $activities;
			$type          = array( 'bbp_topic_edit', 'bbp_topic_create', 'bbp_reply_edit', 'bbp_reply_create' );

			//Verify we are not deleting media if it has bbpress topic and reply associated with it
			if ( isset( $act_obj ) && in_array( $act_obj->type, $type ) ) {

				//Select post_id of bbpress reply or topic
				if ( 'bbpress' === $act_obj->component ) {
					$post_id = $act_obj->item_id;
				} else {
					$post_id = $act_obj->secondary_item_id;
				}

				//Skip permanent delete if it has bbpress topic or reply
				$post = get_post( $post_id );
				if ( ! empty( $post ) ) {
					continue;
				}
			}

			//Delete all media attached in activity
			$activity_media_ids 	= $wpdb->get_col( $wpdb->prepare( "SELECT media_id FROM {$wpdb->prefix}buddyboss_media WHERE activity_id = %d", $activity_id ) );
			if ( ! empty( $activity_media_ids ) && is_array( $activity_media_ids ) ) {
				foreach ( $activity_media_ids as $key => $attachment_id ) {
					wp_delete_attachment( $attachment_id );
				}
			}
		}

		//Delete entry from buddyboss_media table
		$wpdb->delete( $wpdb->prefix . 'buddyboss_media', array( 'activity_id' => $activity_id ), array( '%d' ) );
	}

}

add_action( 'bp_activity_deleted_activities','bbm_delete_row_media_table', 10, 1 );


/**
 * Generate hyperlink text for media in content
 * @param $attachment_ids
 * @return mixed|void
 */
function bbm_generate_media_activity_content( $attachment_ids ) {

	$media_html = '';

	foreach( $attachment_ids as $attachment_id ) {

		$media_src	= current( wp_get_attachment_image_src( $attachment_id, 'full' ) );
		$media_title = get_post_field( 'post_title', $attachment_id );

		$_POST['pics_uploaded'][] = array(
			'status' 		=> 'true',
			'attachment_id' => $attachment_id,
			'url'			=> $media_src,
			'name'			=> $media_title,
		);

		$media_html .= "<a href=\"{$media_src}\" title=\"{$media_title}\" class=\"buddyboss-media-photo-link\">{$media_title}</a>";
	}

	return apply_filters( 'bbm_generate_media_activity_content', $media_html );
}

/**
 * Return all media ids by activity
 * @param $act_id
 * @return mixed|void
 */
function bbm_get_activity_media( $act_id ) {
	global $wpdb;

	$activity_media_query 	= "SELECT media_id FROM {$wpdb->prefix}buddyboss_media WHERE activity_id = {$act_id}";
	$media_ids 				= $wpdb->get_col( $activity_media_query );

	return apply_filters( 'bbm_get_activity_media', $media_ids );
}

// Localize Scripts
add_filter( 'bp_core_get_js_strings', 'buddyboss_wall_activity_localize_scripts', 10, 1 );

/**
 * Localize the strings needed for the Photos Post form UI
 *
 * @since 3.0.0
 *
 * @param array $params Associative array containing the JS Strings needed by scripts.
 *
 * @return array The same array with specific strings for the Activity Post form UI if needed.
 */
function buddyboss_wall_activity_localize_scripts( $params = array() ) {

	// Bail otu if it request is not from media listing page
	if ( ! buddyboss_media_is_media_listing() ) {
		return $params;
	}

	$activity_params = array(
		'user_id'     => bp_loggedin_user_id(),
		'object'      => 'user',
		'backcompat'  => (bool) has_action( 'bp_activity_post_form_options' ),
		'post_nonce'  => wp_create_nonce( 'post_update', '_wpnonce_post_update' ),
	);

	$user_displayname = bp_get_loggedin_user_fullname();

	if ( buddypress()->avatar->show_avatars ) {
		$width  = bp_core_avatar_thumb_width();
		$height = bp_core_avatar_thumb_height();
		$activity_params = array_merge( $activity_params, array(
			'avatar_url'    => bp_get_loggedin_user_avatar( array(
				'width'  => $width,
				'height' => $height,
				'html'   => false,
			) ),
			'avatar_width'  => $width,
			'avatar_height' => $height,
			'avatar_alt'    => sprintf( __( 'Profile photo of %s', 'buddypress' ), $user_displayname ),
			'user_domain'   => bp_loggedin_user_domain()
		) );
	}

	/**
	 * Filter to include specific Action buttons.
	 *
	 * @since 3.0.0
	 *
	 * @param array $value The array containing the button params. Must look like:
	 * array( 'buttonid' => array(
	 *  'id'      => 'buttonid',                            // Id for your action
	 *  'caption' => __( 'Button caption', 'text-domain' ),
	 *  'icon'    => 'dashicons-*',                         // The dashicon to use
	 *  'order'   => 0,
	 *  'handle'  => 'button-script-handle',                // The handle of the registered script to enqueue
	 * );
	 */
	$activity_buttons = apply_filters( 'bp_nouveau_activity_buttons', array() );

	if ( ! empty( $activity_buttons ) ) {
		$activity_params['buttons'] = bp_sort_by_key( $activity_buttons, 'order', 'num' );

		// Enqueue Buttons scripts and styles
		foreach ( $activity_params['buttons'] as $key_button => $buttons ) {
			if ( empty( $buttons['handle'] ) ) {
				continue;
			}

			if ( wp_style_is( $buttons['handle'], 'registered' ) ) {
				wp_enqueue_style( $buttons['handle'] );
			}

			if ( wp_script_is( $buttons['handle'], 'registered' ) ) {
				wp_enqueue_script( $buttons['handle'] );
			}

			unset( $activity_params['buttons'][ $key_button ]['handle'] );
		}
	}

	// Activity Objects
	if ( ! bp_is_single_item() && ! bp_is_user() ) {
		$activity_objects = array(
			'profile' => array(
				'text'                     => __( 'Post in: Profile', 'buddypress' ),
				'autocomplete_placeholder' => '',
				'priority'                 => 5,
			),
		);

		// the groups component is active & the current user is at least a member of 1 group
		if ( bp_is_active( 'groups' ) && bp_has_groups( array( 'user_id' => bp_loggedin_user_id(), 'max' => 1 ) ) ) {
			$activity_objects['group'] = array(
				'text'                     => __( 'Post in: Group', 'buddypress' ),
				'autocomplete_placeholder' => __( 'Start typing the group name...', 'buddypress' ),
				'priority'                 => 10,
			);
		}

		$activity_params['objects'] = apply_filters( 'bp_nouveau_activity_objects', $activity_objects );
	}

	$activity_strings = array(
		'whatsnewPlaceholder' => sprintf( __( "What's new, %s?", 'buddypress' ), bp_get_user_firstname( $user_displayname ) ),
		'whatsnewLabel'       => __( 'Post what\'s new', 'buddypress' ),
		'whatsnewpostinLabel' => __( 'Post in', 'buddypress' ),
	);

	if ( bp_is_group() ) {
		$activity_params = array_merge(
			$activity_params,
			array(
				'object'  => 'group',
				'item_id' => bp_get_current_group_id(),
			)
		);
	}

	$params['activity'] = array(
		'params'  => $activity_params,
		'strings' => $activity_strings,
	);

	return $params;
}

add_action( 'bp_loaded', 'bbm_disable_akismet_spam_check' );

/**
 * Disable Akismet.
 * BuddyBoss Media plugin add a photo links in activity content and because of that Akismet plugin
 * mark activity 'spam'.
 * Following function will disable the use of Akismet while posting media.
 */
function bbm_disable_akismet_spam_check() {
	if ( isset( $_POST['pics_uploaded'] ) && ! empty( $_POST['pics_uploaded'] ) ) {
		add_filter( 'bp_activity_use_akismet', '__return_false' );
	}
}

add_action( 'delete_user', 'bbm_delete_user_albums_data', 10, 1 );

// Delete user's albums from database
function bbm_delete_user_albums_data( $user_id ) {
	global $wpdb;

	/*Delete Data from album table*/
	$wpdb->query( "DELETE FROM {$wpdb->prefix}buddyboss_media_albums WHERE user_id = " . $user_id );
}

add_filter( 'bp_activity_latest_update_content', 'bbm_restore_previous_latest_update', 11, 2 );

/**
 * Remove photo filename from latest update content.
 * Return previous latest update content if update does not have content.
 */
function bbm_restore_previous_latest_update( $content, $activity_content ) {

	// Extract text from the photo a tag
	preg_match( "/<a[^>]+class=\"buddyboss-media-photo-link\"[^>]*>(.*?)<\/a>/is", $content, $output_array );

	// No need to continue, if activity content does not have a buddyboss media photo
	if( ! isset( $output_array[0] ) ) {
		return $content;
	}

	$content = str_replace( $output_array[0], '', $content );

	$content = trim( $content );

	// Just return previous latest update if content is empty
	if ( empty( $content ) ) {
		// Fetch previous latest update
		$user_id = bp_displayed_user_id();
		$update = bp_get_user_meta( $user_id, 'bp_latest_update', true );
		$content = $update['content'];
	}

	// Return previous latest update content
	return $content;
}
