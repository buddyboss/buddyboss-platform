<?php
/**
 * Backwards compatiblity & deprecated functions.
 *
 * @package WordPress
 * @subpackage BuddyBoss Media
 */

/**
 * get_buddyboss_media_photo_action() was replaced by
 * get_buddyboss_media_photo_caption() - this function
 * provides backwards compatibility
 */
function get_buddyboss_media_photo_action()
{
  return get_buddyboss_media_photo_caption();
}

?>