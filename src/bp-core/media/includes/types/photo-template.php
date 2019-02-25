<?php
/**
 * @package WordPress
 * @subpackage BuddyBoss Media
 *
 * @todo Refactor, all functions should begin with 'buddyboss_media_*'
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// @TODO: Fix this madness!!
$buddyboss_media_ii = 0;

/**
 * Check if a picture grid has pictures
 *
 * @since BuddyBoss Media 1.0
 */
function buddyboss_has_photos()
{
  global $buddyboss_media, $buddyboss_media_ii;

  $buddyboss_media_ii++;

  if ( $buddyboss_media_ii > 25 )
  {
    return false;
  }

  if ( $buddyboss_media->types->photo->grid_has_run === false )
  {
    buddyboss_media_screen_photo_grid_content();
    $buddyboss_media->types->photo->grid_has_run = true;
    return $buddyboss_media->types->photo->grid_has_pics;
  }

  if ( $buddyboss_media->types->photo->grid_has_pics === true )
  {
    if ( $buddyboss_media->types->photo->grid_has_run === true )
    {
      if ( $buddyboss_media->types->photo->grid_num_pics < $buddyboss_media->types->photo->grid_photo_index )
      {
        return false;
      }
    }


    $buddyboss_media->types->photo->grid_current_pic = $buddyboss_media->types->photo->grid_pagination->fetchPagedRow();

    if ( $buddyboss_media->types->photo->grid_current_pic === false )
    {
      return false;
    }

  }

  return $buddyboss_media->types->photo->grid_has_pics;
}

/**
 * Handles the enxt picture in the loop
 *
 * @since BuddyBoss Media 1.0
 */
function buddyboss_the_photo()
{
  global $buddyboss_media;

  buddyboss_setup_next_photo();
}

/**
 * Setup the next picture
 *
 * @since BuddyBoss Media 1.0
 */
function buddyboss_setup_next_photo()
{
  global $buddyboss_media;

  ++$buddyboss_media->types->photo->grid_photo_index;
}

/**
 * buddyboss_media_html_grid
 * buddyboss_photo_attachment_id
 * buddyboss_photo_image
 * buddyboss_photo_tn
 * buddyboss_photo_permalink
 */
function get_buddyboss_media_html_grid()
{
  return $buddyboss_media->html_grid;
}

function get_buddyboss_media_photo_attachment_id()
{
  global $buddyboss_media;

  if ( isset( $buddyboss_media->types->photo->grid_data[ $buddyboss_media->types->photo->grid_current_pic ]['attachment_id'] ) )
    return $buddyboss_media->types->photo->grid_data[ $buddyboss_media->types->photo->grid_current_pic ]['attachment_id'];

  return '';
}

function get_buddyboss_media_photo_image()
{
  global $buddyboss_media;

  if ( isset( $buddyboss_media->types->photo->grid_data[ $buddyboss_media->types->photo->grid_current_pic ]['image'] ) )
    return $buddyboss_media->types->photo->grid_data[ $buddyboss_media->types->photo->grid_current_pic ]['image'];

  return array();
}

function get_buddyboss_media_photo_tn()
{
  global $buddyboss_media;

  if ( isset( $buddyboss_media->types->photo->grid_data[ $buddyboss_media->types->photo->grid_current_pic ]['tn'] ) )
    return $buddyboss_media->types->photo->grid_data[ $buddyboss_media->types->photo->grid_current_pic ]['tn'];

  return array();
}

function get_buddyboss_media_photo_permalink()
{
  global $buddyboss_media;

  if ( isset( $buddyboss_media->types->photo->grid_data[ $buddyboss_media->types->photo->grid_current_pic ]['permalink'] ) )
    return $buddyboss_media->types->photo->grid_data[ $buddyboss_media->types->photo->grid_current_pic ]['permalink'];

  return '';
}

function get_buddyboss_media_photo_ajaxlink()
{
  global $buddyboss_media;

  if ( isset( $buddyboss_media->types->photo->grid_data[ $buddyboss_media->types->photo->grid_current_pic ]['ajaxlink'] ) )
    return $buddyboss_media->types->photo->grid_data[ $buddyboss_media->types->photo->grid_current_pic ]['ajaxlink'];

  return '';
}

function get_buddyboss_media_photo_link()
{
  global $buddyboss_media;

  if ( isset( $buddyboss_media->types->photo->grid_data[ $buddyboss_media->types->photo->grid_current_pic ]['image'][0] ) )
    return $buddyboss_media->types->photo->grid_data[ $buddyboss_media->types->photo->grid_current_pic ]['image'][0];

  return '';
}


function buddyboss_media_photo_caption()
{
  echo get_buddyboss_media_photo_caption();
}

function get_buddyboss_media_photo_caption()
{
  global $buddyboss_media;

  if ( isset( $buddyboss_media->types->photo->grid_data[ $buddyboss_media->types->photo->grid_current_pic ]['caption'] ) )
    return $buddyboss_media->types->photo->grid_data[ $buddyboss_media->types->photo->grid_current_pic ]['caption'];

  return '';
}

function buddyboss_media_pagination()
{
  global $buddyboss_media;

  echo $buddyboss_media->types->photo->grid_pagination->fetchPagedNavigation();
}

function buddyboss_media_ajax_photo( $activity_id )
{
  global $bp, $wpdb, $buddyboss_media;

  $user_id = (int)$bp->displayed_user->id;
  $activity_id = (int)$activity_id;
  $activity_table = bp_core_get_table_prefix() . 'bp_activity';
  $activity_meta_table = bp_core_get_table_prefix() . 'bp_activity_meta';

  $wpdb->show_errors = BUDDYBOSS_DEBUG;

  $meta_keys = buddyboss_media_compat( 'activity.item_keys' );
	$meta_keys_csv = "'" . implode( "','", $meta_keys ) . "'";
  $sql = $wpdb->prepare( "SELECT a.*, am.meta_value FROM
          $activity_table a INNER JOIN $activity_meta_table am
          ON a.id = am.activity_id
          WHERE a.user_id = %d
          AND meta_key IN ( $meta_keys_csv ) 
          AND activity_id = %d
          ORDER BY a.date_recorded DESC", $user_id, $activity_id );

  $pic_res = $wpdb->get_results( $sql, ARRAY_A );

  $html = '';

  // If we have results
  if ( !empty( $pic_res ) )
  {
    $pic = array_pop( $pic_res );

    $attachment_id = isset($pic['meta_value']) ? (int)$pic['meta_value'] : 0;

    // Make sure we have a valid attachment ID
    if ( $attachment_id > 0 )
    {
      $img = wp_get_attachment_image_src( $attachment_id, 'buddyboss_media_photo_large' );

      if ( is_array($img) && !empty($img) && isset($img[0]) && $img[0] != '' )
      {
        $html = '<img src="'. esc_url( $img[0] ) .'" />';
      }
    }
  }

  return $html;
}


?>