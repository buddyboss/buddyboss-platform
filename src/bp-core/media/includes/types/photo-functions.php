<?php
/**
 * Photos Functions
 *
 * @package     WordPress
 * @subpackage  BuddyBoss Media
 */

/**
 * Retrive the photo count of displayed user
 * @return null|string
 */
function bbm_total_photos_count() {
	global $wpdb, $bp;

	$photos_user_id = isset( $bp->displayed_user->id ) ? $bp->displayed_user->id : '';
	$new_sql        = "SELECT COUNT(m.id) FROM {$wpdb->prefix}buddyboss_media m INNER JOIN {$wpdb->posts} p ON p.ID = m.media_id WHERE  p.post_type = 'attachment' AND m.media_author = %d AND activity_id NOT IN ( SELECT id FROM {$wpdb->base_prefix}bp_activity WHERE is_spam = 1 OR hide_sitewide = 1 )";
	$sql            = $wpdb->prepare( $new_sql, $photos_user_id );

	buddyboss_media_log( ' MENU PHOTO COUNT SQL ' );
	buddyboss_media_log( $sql );

	$photos_cnt = $wpdb->get_var( $sql );

	return $photos_cnt;
}

/**
 * Get all attached media ids to the activity
 * @param $activity_id
 * @return array
 */
function bbm_activity_media_ids( $activity_id ) {
	global $wpdb;

	$sql			 = "SELECT DISTINCT m.media_id FROM {$wpdb->prefix}buddyboss_media m INNER JOIN {$wpdb->posts} p ON p.ID = m.media_id WHERE p.post_type = 'attachment' AND m.activity_id = %d";
	$sql			 = $wpdb->prepare( $sql, $activity_id );
	$media_ids       = $wpdb->get_col( $sql );

	return $media_ids;
}