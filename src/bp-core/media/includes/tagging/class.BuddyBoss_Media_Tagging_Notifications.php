<?php
/**
 * @package WordPress
 * @subpackage BuddyBoss Media
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'BuddyBoss_Media_Tagging_Notifications' ) ):

class BuddyBoss_Media_Tagging_Notifications {

	private static $instance;
	private $component_action	= 'buddyboss_media_tagged';

	public static function get_instance() {
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		if( function_exists( 'bp_is_active' ) && bp_is_active( 'friends' ) && bp_is_active( 'notifications' ) ){
			if( buddyboss_media()->option( 'enable_tagging' )=='yes' ){
				$this->load();
			}
		}
	}
	
	protected function load(){
		add_action( 'bp_activity_deleted_activities', array( $this, 'deleted_activities_remove_notifications' ) );
		add_action( 'bp_activity_screen_single_activity_permalink', array( $this, 'remove_screen_notifications_single_activity_permalink' ) );

	}
	
	public function register_notification( $component_names = array() ){
		// Force $component_names to be an array
		if ( ! is_array( $component_names ) ) {
			$component_names = array();
		}

		// Add 'forums' component to registered components array
		array_push( $component_names, $this->component_name );
	
		// Return component's with 'forums' appended
		return $component_names;
	}
	
	public function format_bp_notifications( $action, $item_id, $secondary_item_id, $total_items, $format='string' ) {
		if ( $action == $this->component_action ) {
			$activity_permalink = bp_activity_get_permalink( $item_id );
			if( $activity_permalink ){
				$text = sprintf( __( "%s tagged you in a photo", "buddyboss-media" ), bp_core_get_user_displayname( $secondary_item_id ) );
				
				if( 'string'==$format ){
					return sprintf( "<a href='%s'>%s</a>", esc_url( $activity_permalink ), $text );
				} else {
					return array(
						'text' => $text,
						'link' => $activity_permalink,
					);
				}
			}
		}
	}
	
	public function notifications_bp( $activity_id ){
		global $bp, $wpdb;
		//delete existing notification 
		bp_notifications_delete_all_notifications_by_type( $activity_id, buddyboss_media_default_component_slug(), $this->component_action );
		
		//get all users tagged for this activity
		$tagged_users = bp_activity_get_meta( $activity_id, 'bboss_media_tagged_friends', true );
		if( $tagged_users && !empty( $tagged_users ) ){
			$activity = new BP_Activity_Activity( $activity_id );
			if( !$activity || is_wp_error( $activity ) )
				return false;
			
			//add new notifications
			foreach( $tagged_users as $tagged_user ){
				bp_notifications_add_notification(
					array(
						'user_id'           => $tagged_user,
						'item_id'           => $activity_id,
						'secondary_item_id'	=> $activity->user_id,
						'component_name'    => buddyboss_media_default_component_slug(),
						'component_action'  => $this->component_action,
					)
				);
			}
		}
	}
	
	/**
	 * Delete tagging notifications when corresponding photos(activities) are deleted.
	 * 
	 * @since BuddyBoss Media 2.0.9 
	 * 
	 * @global type $wpdb
	 * @param mixed $activity_ids_deleted
	 * @return void
	 */
	public function deleted_activities_remove_notifications( $activity_ids_deleted ){
		$actvity_ids_csv = $activity_ids_deleted;
		if( is_array( $activity_ids_deleted ) )
			$actvity_ids_csv = implode ( ',', $activity_ids_deleted );
		
		if( empty( $actvity_ids_csv ) )
			return;
		
		$sql = "DELETE FROM " . buddypress()->notifications->table_name . 
				" WHERE "
				. " component_name=%s "
				. " AND component_action=%s "
				. " AND item_id IN ( " . $actvity_ids_csv . " )";
		
		global $wpdb;
		$wpdb->query( $wpdb->prepare( $sql, buddyboss_media_default_component_slug(), $this->component_action ) );
	}

	function remove_screen_notifications_single_activity_permalink( $activity ){

		if ( ! bp_is_active( 'notifications' ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		// Mark as read any notifications for the current user related to this activity item.
		bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), $activity->id, buddyboss_media_default_component_slug(), $this->component_action );
	}
	
}// end BuddyBoss_Media_Tagging_Notifications

BuddyBoss_Media_Tagging_Notifications::get_instance();
endif;