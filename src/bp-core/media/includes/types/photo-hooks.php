<?php
/**
 * @package WordPress
 * @subpackage BuddyBoss Media
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// class BuddyBoss_Media_Activity_Group_Filter implements BuddyBoss_Media_Group_Filter
// {
// }

class BuddyBoss_Media_Photo_Hooks
{
	protected $activity_photo_size;

	public function activity_photo_size(){
		if( !$this->activity_photo_size ){
			$this->activity_photo_size = buddyboss_media()->option( 'activity-photo-size' );
			if( !$this->activity_photo_size )
				$this->activity_photo_size = 'medium';
		}

		return $this->activity_photo_size;
	}

	/**
	 * Do stuff before activity save.
	 *
	 * @since 2.0.9
	 * @param type $activity
	 * @return type void
	 */
	public function bp_activity_before_save( $activity ){
		if( !isset( $activity->content ) || empty( $activity->content ) )
			return;

		//check if this activity is a buddyboss media activity.
		$compat_class_search = ( strstr( $activity->content, 'class="buddyboss-media-photo-link"' ) !== false
                             || strstr( $activity->content, 'class="buddyboss-pics-photo-link"' ) !== false
                             || strstr( $activity->content, 'class="buddyboss-pics-picture-link"' ) !== false );

		if ( $compat_class_search && isset( $_POST['pics_uploaded']) && !empty( $_POST['pics_uploaded'] ) ){
			//if it is, lets increase 'max links allowed per comment'
			add_filter( 'option_comment_max_links', array( $this, 'increase_comment_max_links' ) );
		}
	}

	/**
	 * @since 2.0.9
	 * @param int $max_links_allowed
	 * @return int
	 */
	public function increase_comment_max_links( $max_links_allowed ){
		/**
		 * Buddyboss media activities have links to photos in activity content.
		 * Those are counted in maximum links allowed per comment moderation.
		 * So lets increase that!
		 */
		$max_files_per_batch = (int) buddyboss_media()->option( 'files-per-batch' );
		return $max_links_allowed + $max_files_per_batch;
	}

  // Fires when the activity item is saved
  public function bp_activity_after_save( &$activity )
  {
    global $buddyboss_media, $bp;

	//remove filter hooked in before save action
	remove_filter( 'option_comment_max_links', array( $this, 'increase_comment_max_links' ) );

    $user = $bp->loggedin_user;
    $new_action = $result = false;

    $compat_class_search = ( strstr( $activity->content, 'class="buddyboss-media-photo-link"' ) !== false
                             || strstr( $activity->content, 'class="buddyboss-pics-photo-link"' ) !== false
                             || strstr( $activity->content, 'class="buddyboss-pics-picture-link"' ) !== false );

    if ( $user && $compat_class_search && isset( $_POST['pics_uploaded']) && !empty( $_POST['pics_uploaded'] ) ){
      /*$action  = '<a href="'.$user->domain.'">'.$user->fullname.'</a> '
        . __( 'posted a photo', 'buddyboss' );*/

		$action  = '%USER% ' . __( 'posted a photo', 'buddyboss' );

		if( is_array( $_POST['pics_uploaded'] ) && count( $_POST['pics_uploaded'] ) > 1 ){
			$action  = '%USER% ' . sprintf( __( 'posted %s photos', 'buddyboss' ), count( $_POST['pics_uploaded'] ) );
		}

		/**
		 * If the activity is posted in a group
		 */
		if( 'groups'==$activity->component ){
			if( bp_has_groups( array( 'include'=>$activity->item_id ) ) ){
				while( bp_groups() ){
					bp_the_group();
					$group_link = sprintf( "<a href='%s'>%s</a>", bp_get_group_permalink(), bp_get_group_name() );
					$action .= ' ' . __( 'in the group', 'buddyboss' ) . ' ' . $group_link;
				}
			}
		}

		$attachment_ids = array();
		foreach( $_POST['pics_uploaded'] as $uploaded_pic ){

            if ( ! isset( $uploaded_pic['attachment_id'] ) ) {
              continue;
            }

			$attachment_ids[] = (int)$uploaded_pic['attachment_id'];

			//Update buddyboss_media table

            if( isset( $_POST['media_visibility'] ) ) {
              $media_privacy = $_POST['media_visibility'];
            } else {
              $media_privacy = 'public';
            }

            // Check media entry present or not before adding new row
            $row_exists = bbm_is_media_row_exists( $activity->id, $uploaded_pic['attachment_id'] );
            if ( ! $row_exists ) {
				bbm_update_media_table( (int)$uploaded_pic['attachment_id'], $uploaded_pic['name'], $activity->id, $media_privacy );
			}

		}

      $action_key = buddyboss_media_compat( 'activity.action_key' );
      $item_key = buddyboss_media_compat( 'activity.item_key' );

      bp_activity_update_meta( $activity->id, $action_key, $action );
      bp_activity_update_meta( $activity->id, $item_key, $attachment_ids );

		// Execute our after save action
      do_action( 'buddyboss_media_photo_posted', $activity, $attachment_ids, $action );

      // Prevent BuddyPress from sending notifications, we'll send our own
    }

    /**
     * BuddyPress Activity Bump plugins update the activity date recorded on post comment that trigger
     * 'bp_activity_after_save' again and the activity posted image(s) gets replaced by the comment picture.
     */
    remove_filter( 'bp_activity_after_save', array( $this, 'bp_activity_after_save' ) );
  }

  // Filter's the activity item's action text
  public function bp_get_activity_action( $action )
  {
    global $activities_template;

    $current_activity_index = $activities_template->current_activity;

    $current_activity       = $activities_template->activities[ $current_activity_index ];

    $current_activity_id    = $current_activity->id;

    $buddyboss_media_action = buddyboss_media_compat_get_meta( $current_activity_id, 'activity.action_keys' );

     //Do not override bbpress forum activity post header
    $exclude_component = array( 'bbpress' );

    if ( $buddyboss_media_action &&
        ! in_array( $current_activity->component, $exclude_component ) ) {

		//convert placeholder into real user link
		//display You if its current users activity
		$replacement = '';
		if( $current_activity->user_id == get_current_user_id() ){
			$replacement = __( 'You', 'buddyboss' );
		} else {
			$userdomain = bp_core_get_user_domain( $current_activity->user_id );
			$user_fullname = bp_core_get_user_displayname( $current_activity->user_id );

			$replacement = '<a href="'.esc_url( $userdomain ).'">' . $user_fullname . '</a>';
		}

		$buddyboss_media_action = str_replace( '%USER%', $replacement, $buddyboss_media_action );

      // Strip any legacy time since placeholders from BP 1.0-1.1
      $content = str_replace( '<span class="time-since">%s</span>', '', $buddyboss_media_action );

      // Insert the time since.
      $time_since = apply_filters_ref_array( 'bp_activity_time_since', array( '<span class="time-since">' . bp_core_time_since( $activities_template->activity->date_recorded ) . '</span>', &$activities_template->activity ) );

      // Insert the permalink
      if ( !bp_is_single_activity() )
        $content = apply_filters_ref_array( 'bp_activity_permalink', array( sprintf( '%1$s <a href="%2$s" class="view activity-time-since" title="%3$s">%4$s</a>', $content, bp_activity_get_permalink( $activities_template->activity->id, $activities_template->activity ), esc_attr__( 'View Discussion', 'buddyboss' ), $time_since ), &$activities_template->activity ) );
      else
        $content .= str_pad( $time_since, strlen( $time_since ) + 2, ' ', STR_PAD_BOTH );

      return apply_filters( 'buddyboss_media_activity_action', $content );
    }

    return $action;
  }

  // Filter's the activity item's content
  public function bp_get_activity_content_body( $content ) {
    global $activities_template;

    $curr_id = isset( $activities_template->current_activity ) ? $activities_template->current_activity : '';

    $current_activity = isset($activities_template->activities[$curr_id]) && !empty($activities_template->activities[$curr_id]) ? $activities_template->activities[$curr_id] : '';

    // Check for activity ID in $_POST if this is a single
    // activity request from a [read more] action
    if ( empty($act_id) && ! empty( $_POST['activity_id'] ) )
    {
      $activity_array = bp_activity_get_specific( array(
          'activity_ids'     => $_POST['activity_id'],
          'display_comments' => 'stream'
      ) );

      $activity = ! empty( $activity_array['activities'][0] ) ? $activity_array['activities'][0] : false;

      $act_id = (int)$activity->id;
    }

    // This should never happen, but if it does, bail.
    if ( isset( $act_id ) && $act_id === 0 ) {
      return $content;
    }

    $content = $this->media_container_activity_content( $current_activity, $content );

    return $content;
  }


  /**
   * Embed media photo container in comment content
   * @param $content
   * @return string
   */
  public function bp_get_activity_comment_content( $content ) {
    global $activities_template;

    if ( empty( $activities_template) ) {
      return $content;
    }

    $current_comment = $activities_template->activity->current_comment;

    $content = $this->media_container_activity_content( $current_comment, $content );

    return $content;
  }

  /**
   * Prepare media photo container
   * @param $act_obj
   * @param $content
   * @return string
   */
 public function media_container_activity_content( $act_obj, $content ) {

    $act_id = isset($act_obj->id) && !empty($act_obj->id) ? $act_obj->id : '';

   // Check for activity ID in $_REQUEST if this is a get_single_activity_content activity request from a [read more] action
   if ( empty( $act_id ) && ! empty( $_REQUEST['activity_id'] ) ) {
     $act_id = $_REQUEST['activity_id'];
   }

    // This is manual for now
    $type    = 'photo'; // photo/video/audio/file/doc/pdf/etc

    $media_ids = buddyboss_media_compat_get_meta( $act_id, 'activity.item_keys' );

    // Photo
    if ( $type === 'photo' && ! empty( $media_ids ) )
    {
      /**
       * if we are displaying grid layout instead of activity post layout, images should be 'thumbnail' size
       */
      if( buddyboss_media_check_custom_activity_template_load() ){
        $img_size = 'thumbnail';//hardcoded !?
      } else {
        //$img_size = 'buddyboss_media_photo_wide';
        $img_size =  buddyboss_media()->types->photo->hooks->activity_photo_size();
      }

      /**
       * After bulk upload feature was added, an array of attachment ids is saved in database.
       * Before bulk upload feature, only one image was uploaded and thus, only one attachment id was saved in activity meta.
       * In that case, $media_ids will be a int variable, lets convert it to array, for uniformity in following code.
       */

      if( !is_array( $media_ids ) ) {
        $media_ids = array( $media_ids );
      }


      /*$is_super_admin       = is_super_admin();
      $bp_displayed_user_id = bp_displayed_user_id();
      $bp_loggedin_user_id  = bp_loggedin_user_id();

      $visible_media = array();
      foreach( $media_ids as $media_id ) {
        if(  bbm_is_media_visible( $media_id, $bp_loggedin_user_id, $is_super_admin  ) ) {
          $visible_media[] = $media_id;
        }
      }*/

      //alt tag
      $clean_content = wp_strip_all_tags( $content, true );
      $alt_text = !empty( $clean_content ) ? substr( $clean_content, 0, 100 ) : '';//first 100 characters ?
      $alt = ' alt="' . esc_attr( $alt_text ) . '"';

      //let's leave it as it is for grid view
      if( buddyboss_media_check_custom_activity_template_load() ){
        foreach( $media_ids as $media_id ){
          $image = wp_get_attachment_image_src( $media_id, $img_size );

          if ( ! empty( $image ) && is_array( $image ) && count( $image ) > 2 ){
            $src = $image[0];
            $w = $image[1];
            $h = $image[2];

            $full = wp_get_attachment_image_src( $media_id, 'full' );

            $width_markup = $w > 0 ? ' width="'.$w.'"' : '';
            $comment_count = bp_activity_recurse_comment_count( $act_obj );
            $favorite_count = bp_activity_get_meta( $act_id, 'favorite_count' );
            $favorite_count = max( $favorite_count, '0' );
            if (  buddyboss_media()->types->photo->hooks->bbm_get_media_is_favorite() ) {
              $data_fav = 'bbm-unfav';
            } else {
              $data_fav = 'bbm-fav';
            }

            if ( $full !== false && is_array( $full ) && count( $full ) > 2 ){
              $owner = ( $act_obj->user_id == get_current_user_id())?'1':'0';
              $content .= '<a class="buddyboss-media-photo-wrap" href="'.$full[0].'">';
              $content .= '<img data-permalink="'. bp_get_activity_thread_permalink() .'" data-photo-id="'.$media_id.'" class="buddyboss-media-photo" src="'.$src.'"'.$width_markup.' ' . $alt . ' data-comment-count="'.$comment_count.'" data-favorite-count="'.$favorite_count.'" data-bbmfav="'.$data_fav.'" data-media="'.$act_id.'" data-owner="'.$owner.'"/></a>';
            }
            else {
              $content .= '<img data-permalink="'. bp_get_activity_thread_permalink() .'" data-photo-id="'.$media_id.'" data-comment-count="'.$comment_count.'" data-favorite-count="'.$favorite_count.'" data-bbmfav="'.$data_fav.'" data-media="'.$act_id.'" data-owner="'.$owner.'" class="buddyboss-media-photo" src="'.$src.'"'.$width_markup.' ' . $alt .' />';
            }
          }
        }
      } else {
        /**
         * In activity view, we display different number of pics in different ways.
         *
         * Here's how multiple photos are displayed.
         * 1 photo - normal size.
         * 2 photo - both thumbnails
         * 3 photo - 1 normal 2 thumbnails
         * 4 phtos - all thumbnails
         * > 4 photos - 4 thumbnails only. rest are hidden, they can see those in photoswipe
         */
        $media_ids[0] = isset($media_ids[0]) ? $media_ids[0] : '';
        $media_ids[1] = isset($media_ids[1]) ? $media_ids[1] : '';

        $image1 = wp_get_attachment_image_src( $media_ids[0], 'full' );
        $w1 = $image1[1];
        $h1 = $image1[2];

        $image2 = wp_get_attachment_image_src( $media_ids[1], 'full' );
        $w2 = $image2[1];
        $h2 = $image2[2];

        $two_imgs_name = 'activity-2-thumbnail';

        // tall images
        if($w1<$h1 && $w2<$h2) {
          $two_imgs_name = 'activity-2-thumbnail-tall';
        }

        $filesizes = array();
        switch( count( array_filter($media_ids )) ){
          case 1:
            $filesizes = array( $img_size );
            $filenames = array( 'activity-thumbnail' );
            break;
          case 2:
            $filesizes = array( array($w1/2, $h1/2), array($w1/2, $h1/2) );
            $filenames = array( $two_imgs_name, $two_imgs_name );
            break;
          case 3:
            $filesizes = array( $img_size, array($w1/2, $h1/2), array($w1/2, $h1/2) );
            $filenames = array( 'activity-thumbnail gallery-type', 'activity-3-thumbnail', 'activity-3-thumbnail' );
            break;
          default:
            $filesizes = array( $img_size, array($w1/3, $h1/3), array($w1/3, $h1/3), array($w1/3, $h1/3) );
            $filenames = array( 'activity-thumbnail gallery-type', 'activity-4-thumbnail', 'activity-4-thumbnail', 'activity-4-thumbnail' );
            break;
        }

        $img_counter = 0;
        $all_imgs_html = '';

        $filesizes          = apply_filters( 'bbm_activity_photo_wrap_size', $filesizes );
        $media_ids          = array_filter( $media_ids );
        $total_img_counter  = sizeof( $media_ids );


        foreach( $media_ids as $media_id ) {

          if ( isset( $filesizes[$img_counter] ) ) {
            $size =  $filesizes[$img_counter];
          } else {
            $size = 'thumbnail';
          }

          $image = wp_get_attachment_image_src( $media_id, $size );

          if ( ! empty( $image ) && is_array( $image ) && count( $image ) > 2 ){
            $src = $image[0];
            $w = $image[1];
            $h = $image[2];

            if ( wp_is_mobile() ) {
              $full = wp_get_attachment_image_src( $media_id, 'large' );
            } else {
              $full = wp_get_attachment_image_src( $media_id, 'buddyboss_media_photo_large' );
            }

           // $full = wp_get_attachment_image_src( $media_id, 'full' );
            $comment_count = bp_activity_recurse_comment_count( $act_obj );
            $favorite_count = bp_activity_get_meta( $act_id, 'favorite_count' );
            $favorite_count = max( $favorite_count, '0' );
            $width_markup = '';
            $height_markup = '';

            //hide more than 4 images
            $maybe_display_none = $img_counter > 3 ? ' style="display:none"' : '';

            if (  buddyboss_media()->types->photo->hooks->bbm_get_media_is_favorite() ) {
              $data_fav = 'bbm-unfav';
            } else {
              $data_fav = 'bbm-fav';
            }

            if ( $full !== false && is_array( $full ) && count( $full ) > 2 ){
              $owner = ( $act_obj->user_id == get_current_user_id() )?'1':'0';

              if ( empty ( $maybe_display_none ) ) {
                $all_imgs_html .= '<a class="buddyboss-media-photo-wrap size-' . $filenames[$img_counter] . '" '.$height_markup.'  href="'.$full[0].'" ' . $maybe_display_none . '>';
              } else {
                $all_imgs_html .= '<a class="buddyboss-media-photo-wrap" '.$height_markup.'  href="'.$full[0].'" ' . $maybe_display_none . '>';
              }


              if ( 4 < $total_img_counter  && 3 === $img_counter  ) {
                $left_img_count = $total_img_counter - 4;
                $all_imgs_html .= '<div class="size-activity-4-count"><div class="size-activity-4-count-a"><div class="size-activity-4-count-b">+'. $left_img_count .'</div></div></div>';
              }

              if( 4 > $img_counter ) {
                $all_imgs_html .= '<img data-photo-id="'.$media_id.'" data-permalink="'. bp_get_activity_thread_permalink() .'" class="buddyboss-media-photo" src="'.$src.'"'.$width_markup.' ' . $alt . ' data-comment-count="'.$comment_count.'" data-favorite-count="'.$favorite_count.'" data-bbmfav="'.$data_fav.'" data-media="'.$act_id.'" data-owner="'.$owner.'"/>';
              } else {
                $all_imgs_html .= '<img data-photo-id="'.$media_id.'" data-permalink="'. bp_get_activity_thread_permalink() .'" class="buddyboss-media-photo" data-comment-count="'.$comment_count.'" data-favorite-count="'.$favorite_count.'" data-bbmfav="'.$data_fav.'" data-media="'.$act_id.'" data-owner="'.$owner.'"/>';
              }

              $all_imgs_html .= '</a>';
            }
            else {
              $all_imgs_html .= '<img ' . $maybe_display_none . ' data-photo-id="'.$media_id.'" data-permalink="'. bp_get_activity_thread_permalink() .'" data-comment-count="'.$comment_count.'" data-favorite-count="'.$favorite_count.'" data-bbmfav="'.$data_fav.'" data-media="'.$act_id.'" data-owner="'.$owner.'" class="buddyboss-media-photo size-' . $filesizes[$img_counter] . '" src="'.$src.'"'.$width_markup.' ' . $alt .' />';
            }
          }
          $img_counter++;
        }

        $content .= "<div class='buddyboss-media-photos-wrap-container'>" . $all_imgs_html . "</div>";
      }
    }

    return $content;
  }

  // Filter's the activity item's content when the plugin is off
  public function off_bp_get_activity_content_body( $content )
  {
    global $buddyboss_media_img_size, $activities_template;

    $curr_id = $activities_template->current_activity;

    $act_id = $activities_template->activities[$curr_id]->id;

    $buddyboss_media_aid = buddyboss_media_compat_get_meta( $act_id, 'activity.item_keys' );

    $image = wp_get_attachment_image_src( $buddyboss_media_aid, 'full' );

    if ( $image !== false && is_array( $image ) && count( $image ) > 2 )
    {
      $src = $image[0];
      $w = $image[1];
      $h = $image[2];
      $content .= '<a href="'. $image[0] .'" target="_blank">'. basename( $image[0] ) .'</a>';
    }

    return $content;
  }

  public function bp_get_member_latest_update( $update )
  {
    global $members_template;

    if ( !bp_is_active( 'activity' ) || empty( $members_template->member->latest_update ) || !$update = maybe_unserialize( $members_template->member->latest_update ) )
      return false;

    $current_activity_id    = $update['id'];

    $buddyboss_media_action = buddyboss_media_compat_get_meta( $current_activity_id, 'activity.action_keys' );

    if ( $buddyboss_media_action )
    {
      // Strip any legacy time since placeholders from BP 1.0-1.1
      $content = str_replace( '<span class="time-since">%s</span>', '', $buddyboss_media_action );

	  //remove user placeholder
	  $content = str_replace( "%USER%", "", $content );

      $activity_action_text = __( 'new photo', 'buddyboss' );

      // Look for 'posted a photo' and linkify
      if ( stristr( $content, $activity_action_text ) )
      {
        $permalink_href = bp_activity_get_permalink( $current_activity_id );

        if ( ! empty( $permalink_href ) )
        {
          $permalink = sprintf( '<a href="%s" title="%s">%s</a>', $permalink_href, strip_tags( $content ), $activity_action_text );

          $content = str_replace( $activity_action_text, $permalink, $content );
        }
      }

		return apply_filters( 'buddyboss_media_activity_action', $content );
    }

    return $update['content'];
  }

  //Checking if media is favorite
	public function bbm_get_media_is_favorite() {
		global $activities_template;

		return apply_filters( 'bbm_get_media_is_favorite', in_array( $activities_template->activity->id, ( array ) $activities_template->my_favs ) );
	}

}

// AJAX update picture
function buddyboss_media_post_photo()
{
  global $bp, $buddyboss;

  //Check is it 'bbm-media-upload'
  check_admin_referer( 'bbm-media-upload', '_wpnonce_post_update' );

  if ( !is_user_logged_in() ) {
    echo '-1';
    return false;
  }

  if ( ! function_exists( 'wp_generate_attachment_metadata' ) )
  {
    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
    require_once(ABSPATH . "wp-admin" . '/includes/file.php');
    require_once(ABSPATH . "wp-admin" . '/includes/media.php');
  }

  if ( ! function_exists('media_handle_upload' ) )
  {
    require_once(ABSPATH . 'wp-admin/includes/admin.php');
  }

  add_filter( 'upload_dir', 'buddyboss_media_upload_dir' );

  $aid = media_handle_upload( 'file', 0 );

  remove_filter( 'upload_dir', 'buddyboss_media_upload_dir' );

  // Image rotation fix
  do_action( 'buddyboss_media_add_attachment', $aid );

  $attachment = get_post( $aid );

  $name = $url = null;

  if ( $attachment !== null )
  {
    $name = $attachment->post_title;

    //$img_size = 'buddyboss_media_photo_wide';
	$img_size = 'buddyboss_media_photo_tn';

    $url_nfo = wp_get_attachment_image_src( $aid, $img_size );

    $url = is_array( $url_nfo ) && !empty( $url_nfo ) ? $url_nfo[0] : null;
  }

  $result = array(
    'status'          => ( $attachment !== null ),
    'attachment_id'   => (int)$aid,
    'url'             => esc_url( $url ),
    'name'            => esc_attr( $name )
  );

  echo htmlspecialchars( json_encode( $result ), ENT_NOQUOTES );

  exit(0);
}
add_action( 'wp_ajax_buddyboss_media_post_photo', 'buddyboss_media_post_photo' );


function buddyboss_media_load_template_filter( $found_template, $templates ) {

  global $bp;

  // @TODO: Should we change the component name to 'buddyboss'?
  // @TODO: Can we dynamically let the user choose a component's slug?
  if ( $bp->current_component !== buddyboss_media_component_slug() )
    return $found_template;

  $filtered_templates = array();

  $templates_dir = buddyboss_media()->templates_dir;

  foreach ( (array) $templates as $template )
  {
    if ( file_exists( STYLESHEETPATH . '/' . $template ) )
      $filtered_templates[] = STYLESHEETPATH . '/' . $template;
    elseif ( file_exists( TEMPLATEPATH . '/' . $template ) )
      $filtered_templates[] = TEMPLATEPATH . '/' . $template;
    elseif ( file_exists( $templates_dir . '/' . $template ) )
      $filtered_templates[] = $templates_dir . '/' . $template;
  }

  if( !empty( $filtered_templates ) )
    $found_template = $filtered_templates[0];

  return apply_filters( 'buddyboss_media_load_template_filter', $found_template );
}
add_filter( 'bp_located_template', 'buddyboss_media_load_template_filter', 10, 2 );

function buddyboss_media_upload_dir( $filter )
{
  return apply_filters( 'buddyboss_media_upload_dir', $filter );
}

function buddyboss_media_greetings_template_text( $text )
{
	if( bp_is_current_component( buddyboss_media_component_slug() ) ){
		$firstname = '';
		if ( is_user_logged_in() && function_exists( 'bp_get_user_firstname' ) ){
			$firstname = bp_get_user_firstname();
		}

		$text =  sprintf( __( "Add a photo, %s", 'buddyboss' ), $firstname );
	}

	return $text;
}
add_filter( 'buddyboss_wall_greeting_template', 'buddyboss_media_greetings_template_text' );
?>
