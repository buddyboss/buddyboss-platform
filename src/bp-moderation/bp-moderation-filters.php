<?php
/**
 * Filters related to the Moderation component.
 *
 * @package BuddyBoss\Moderation
 * @since BuddyBoss 1.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Activity Moderation
 */
function dips_debug(){
	if ( ! empty( $_GET['debug'] ) ) {

		/*$args = [
			'user_id' => 2,
			'count_total' => true
		];

		$moderation = BP_Moderation::get( $args );
		echo '<pre>';
		var_dump( $moderation );
		echo '</pre>';*/

		/*$moderation = new BP_Moderation();
		$moderation->updated_by = 4;
		$moderation->item_id = 7;
		$moderation->content = 'testing again';
		$moderation->item_type = 'group';
		$moderation->date_updated = current_time( 'mysql' );
		$moderation->hide_sitewide = false;
		$moderation->category_id = 0;
		$moderation->blog_id = 1;
		$moderation->save();
		echo '<pre>';
		var_dump( $moderation );
		echo '</pre>';*/
		exit();
	}
}
add_action( 'init', 'dips_debug', 99 );
