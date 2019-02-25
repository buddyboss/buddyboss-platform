<?php
/**
 * @package WordPress
 * @subpackage BuddyBoss Media
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function buddyboss_media_screen_photo_grid_content()
{
  global $bp, $wpdb, $buddyboss_media;

  // TODO: add debug flag to main class
  $wpdb->show_errors = false;

  $meta_keys = buddyboss_media_compat( 'activity.meta_keys' );
  $meta_key_clause = buddyboss_media_compat( 'activity.meta_key' );

  $img_size = 'full'; //buddyboss_media_photo_wide';

  $gallery_class = 'gallery';

  $user_id = $bp->displayed_user->id;
  $activity_table = bp_core_get_table_prefix() . 'bp_activity';
  $activity_meta_table = bp_core_get_table_prefix() . 'bp_activity_meta';
  $groups_table = bp_core_get_table_prefix() . 'bp_groups';

  //@todo, this sql query should be more 'configurable' using filters/actions etc
  $pages_sql = "SELECT COUNT(*) FROM $activity_table a
                INNER JOIN $activity_meta_table am ON a.id = am.activity_id
                LEFT JOIN (SELECT id FROM $groups_table WHERE status != 'public' ) grp ON a.item_id = grp.id
                WHERE a.user_id = $user_id
                AND (am.meta_key = 'buddyboss_media_aid' OR am.meta_key = 'buddyboss_pics_aid' OR am.meta_key = 'bboss_pics_aid')
                AND (a.component != 'groups' || a.item_id != grp.id)";
  $pages_sql = apply_filters( 'buddyboss_media_screen_content_pages_sql', $pages_sql );

  $buddyboss_media->types->photo->grid_num_pics = $wpdb->get_var($pages_sql);

  $current_page = isset( $_GET['page'] ) ? (int) $_GET['page'] : 1;
	//if we are not on a user profile,
  //if we are on 'all photos'/'all media' page, $_GET['page'] will not work becuase of wordpress' default url rewrite
  if( !bp_is_user() ){
	  $current_page = get_query_var( 'paged', 1 );
  }

  $buddyboss_media->types->photo->grid_current_page = $current_page;

  // Prepare a SQL query to retrieve the activity posts
  // that have pictures associated with them
  //@todo, this sql query should be more 'configurable' using filters/actions etc
  //@todo, add LIMIT clause
  //the sql query doesn't have a LIMIT clause. So all rows are fetched from database, although number of rows displayed is correct.
  //so if there are 1000 media items, all 1000 will be retrieved from database but only 15 will be displayed!
  $sql = "SELECT a.*, am.meta_value FROM $activity_table a
          INNER JOIN $activity_meta_table am ON a.id = am.activity_id
          LEFT JOIN (SELECT id FROM $groups_table WHERE status != 'public' ) grp ON a.item_id = grp.id
          WHERE a.user_id = $user_id
          AND (am.meta_key = 'buddyboss_media_aid' OR am.meta_key = 'buddyboss_pics_aid' OR am.meta_key = 'bboss_pics_aid')
          AND (a.component != 'groups' || a.item_id != grp.id)
          ORDER BY a.date_recorded DESC";
  $sql = apply_filters( 'buddyboss_media_screen_content_sql', $sql );

  buddyboss_media_log("SQL: $sql");

  $pics = $wpdb->get_results($sql,ARRAY_A);

  $buddyboss_media->types->photo->grid_pagination = new BuddyBoss_Media_Paginated( $pics, $buddyboss_media->types->photo->grid_pics_per_page, $buddyboss_media->types->photo->grid_current_page );

  // buddyboss_media_log("RESULT: $pics");

  // If we have results let's print out a simple grid
  if ( ! empty( $pics ) )
  {
    $buddyboss_media->types->photo->grid_had_pics = true;
    $buddyboss_media->types->photo->grid_num_pics = count( $pics );

    /**
     * DEBUG
     */
    // echo '<br/><br/><div style="display:block;background:#f0f0f0;border:2px solid #ccc;margin:20px;padding:15px;color:#333;"><pre>';
    // var_dump( $pics );
    // echo '</pre></div><hr/><br/><br/><br/><br/>';
    // die;
    /**/

    $html_grid = '<ul class="'.$gallery_class.'" id="buddyboss-media-grid">'."\n";

    foreach( $pics as $pic )
    {
      /**
       * DEBUG
       */
      // echo '<br/><br/><div style="display:block;background:#f0f0f0;border:2px solid #ccc;margin:20px;padding:15px;color:#333;"><pre>';
      // var_dump( bp_activity_get_permalink($pic['id']), $pic );
      // echo '</pre></div><hr/><br/><br/><br/><br/>';
      // die;
      /**/

      //BP ACTIVITY PRIVACY FIX
      if( function_exists( 'bp_activity_privacy_add_js' ) ){
      $is_super_admin = is_super_admin();
      $bp_displayed_user_id = bp_displayed_user_id();
      $bp_loggedin_user_id = bp_loggedin_user_id();

      if( $pic['privacy'] == 'loggedin' && !$bp_loggedin_user_id )
            continue;
      if( $pic['privacy'] == 'friends' && !friends_check_friendship( $bp_loggedin_user_id, $bp_displayed_user_id ) && $bp_loggedin_user_id != $bp_displayed_user_id )
            continue;
      if( $pic['privacy'] == 'groupfriends' && (!friends_check_friendship( $bp_loggedin_user_id, $bp_displayed_user_id || !groups_is_user_member( $bp_loggedin_user_id, $bp_displayed_user_id )) ) )
            continue;
      if( $pic['privacy'] == 'grouponly' && !groups_is_user_member( $bp_loggedin_user_id, $bp_displayed_user_id ) )
            continue;
      if( $pic['privacy'] == 'groupmoderators' && !groups_is_user_mod( $bp_loggedin_user_id, $bp_displayed_user_id ) )
            continue;
      if( $pic['privacy'] == 'groupadmins' && !groups_is_user_admin( $bp_loggedin_user_id, $bp_displayed_user_id ) )
            continue;
      if( $pic['privacy'] == 'adminsonly' && !$is_super_admin )
            continue;
      if( $pic['privacy'] == 'onlyme' && $bp_loggedin_user_id != $bp_displayed_user_id )
            continue;
      }
      $attachment_id = isset($pic['meta_value']) ? (int)$pic['meta_value'] : 0;

      // Make sure we have a valid attachment ID
      if ( $attachment_id > 0 )
      {
        // Let's get the permalink of this attachment to show within a lightbox
        $permalink = bp_activity_get_permalink( $pic[ 'id' ] );

        // We need to remove the media plugin's photo filters so it doesn't add an image/link
        // to the activity body
        remove_filter( 'bp_get_activity_content_body', array( $buddyboss_media->types->photo->hooks, 'bp_get_activity_content_body' ) );


        // Let's get the caption
        $caption = $caption_inner = '';

        if ( bp_has_activities( 'include='.$pic['id'] ) )
        {
          while ( bp_activities() )
          {
            bp_the_activity();

            // We need to store both the action and body so we can fall back to upload date
            // in PhotoSwipe. PHP/WP will output this data and our JS will filter and apply
            // the proper caption
            $caption_inner  = '<div class="buddyboss_media_caption_action">' . bp_get_activity_action() . '</div>';
            $caption_inner .= '<div class="buddyboss_media_caption_body">' . bp_get_activity_content_body() . '</div>';


            $caption = '<div class="buddyboss_media_caption">' . $caption_inner . '</div>';
          }
        }

        // Grab the image details
        $image = wp_get_attachment_image_src( $attachment_id, $img_size );

        // grab the thumbnail details
        $tn = wp_get_attachment_image_src( $attachment_id, 'buddyboss_media_photo_tn' );

        if ( is_array($tn) && !empty($tn) && isset($tn[0]) && $tn[0] != '' )
        {
          $buddyboss_media->types->photo->grid_data[] = array(
            'attachment'  => $attachment_id,
            'caption'     => $caption,
            'image'       => $image,
            'tn'          => $tn,
            'permalink'   => $permalink
          );

          $html_grid .= '<li class="gallery-item"><div><a rel="gal_item" href="' . $image[0] . '"><img src="'.$tn[0].'" width="'.$tn[1].'" height="'.$tn[2].'" /></a></div></li>'."\n";
        }
      }
    }

    $html_grid .= '</ul>'."\n\n";

    $buddyboss_media->types->photo->grid_html = $html_grid;

    $buddyboss_media->types->photo->grid_has_pics = true;
  }
  else {
    $buddyboss_media->types->photo->grid_has_pics = false;
    $buddyboss_media->types->photo->grid_num_pics = 0;
    $buddyboss_media->types->photo->grid_current_pic = null;
    $buddyboss_media->types->photo->grid_data = array();
    $buddyboss_media->types->photo->grid_html = null;
  }
}


/**
 * Hook profile Photo grid template into BuddyPress plugins template
 *
 * @since BuddyBoss Media 1.0.4
 *
 * @uses add_action() To add the content hook
 * @uses bp_core_load_template() To load the plugins template
 */
function buddyboss_media_screen_photo_grid() {
  add_action( 'bp_template_content', 'buddyboss_media_template_photos' );
  bp_core_load_template( apply_filters( 'buddyboss_media_screen_photo_grid', 'members/single/plugins' ) );
}

function buddyboss_media_template_photos() {
	$theme_compat_id = bp_get_theme_compat_id();
	if ( 'legacy' === $theme_compat_id ) {
		buddyboss_media_load_template( 'members/single/buddyboss-media-photos' );
	} elseif ( 'nouveau' === $theme_compat_id ) {
		buddyboss_media_load_template( 'bp-nouveau/members/single/buddyboss-media-photos' );
	}
}

?>