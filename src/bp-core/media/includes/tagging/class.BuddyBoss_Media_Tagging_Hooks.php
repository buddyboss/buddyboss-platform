<?php
/**
 * @package WordPress
 * @subpackage BuddyBoss Media
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'BuddyBoss_Media_Tagging_Hooks' ) ):

class BuddyBoss_Media_Tagging_Hooks {

	private static $instance;
	private $tooltip_texts = array();

	public static function get_instance() {
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		if( function_exists( 'bp_is_active' ) && bp_is_active( 'friends' ) ){
			if( buddyboss_media()->option( 'enable_tagging' )=='yes' ){
				$this->load();
			}
		}
	}
	
	protected function load(){
		//modify activity title
		add_filter( 'bp_get_activity_action',			array( $this, 'activity_action' ), 20,2 );//hook a little late
		
		//add tooltip text at the bottom
		add_action( 'bp_after_activity_entry_comments', array( $this, 'activity_tagging_tooltip_text' ) );
		add_action( 'bp_directory_members_item', array( $this, 'activity_tagging_tooltip_text' ) );
	}
	
	public function activity_action( $action ){
		
		if( ( $tagged_people = bp_activity_get_meta( bp_get_activity_id() , 'bboss_media_tagged_friends', true ) )!= false ){
			$count = count( $tagged_people );
			
			$current_user_is_tagged = false;
			if( in_array( bp_loggedin_user_id(), $tagged_people ) ){
				$current_user_is_tagged = true;
			}
			
			// Only the current user is tagged
			if ( $count === 1 && $current_user_is_tagged ){
				$tagged_txt = __( 'You', 'buddyboss-media' );
			} else {
				// Show up to two 3 names (you + 3 others)
				$tagged_for_display = array();
				$tagged_for_tooltip = array();

				// Fallback
				$tagged_txt = '';

					$args = apply_filters( 'bboss_media_tagged_friends', array(
					'per_page'			=> 0,
					'populate_extras'	=> false,
					'exclude'			=> bp_loggedin_user_id(),
				) );
				
				if( bp_has_members( $args ) ){
					$current = 0;
					while ( bp_members() ){
						bp_the_member();
						$user_tagged_html = bp_core_get_userlink( bp_get_member_user_id() );
						
						// For the first 3 we want the output to show
						if ( $current < 3 ){
							$tagged_for_display[] = $user_tagged_html;
						}
						// For all other users we want the output in a tooltip
						else {
							$tagged_for_tooltip[] = $user_tagged_html;
						}
						
						$current++;
					}
				}
				
				$others = count( $tagged_for_tooltip );

				// 1 user
				if ( count( $tagged_for_display ) === 1 ){
					if ( $current_user_is_tagged ){
						$tagged_txt = sprintf( __( 'You and %s', 'buddyboss-media' ), $tagged_for_display[0] );
					} else {
						$tagged_txt = sprintf( __( '%s', 'buddyboss-media' ), $tagged_for_display[0] );
					}
				}
				
				// 2 users
				else if ( count( $tagged_for_display ) === 2 ){
					if ( $current_user_is_tagged ){
						$tagged_txt = sprintf( __( 'You, %s and %s', 'buddyboss-media' ), $tagged_for_display[0], $tagged_for_display[1] );
					}
					else {
						$tagged_txt = sprintf( __( '%s and %s', 'buddyboss-media' ), $tagged_for_display[0], $tagged_for_display[1] );
					}
				}
				
				// 3 users + no others
				else if ( count( $tagged_for_display ) === 3 && $others === 0 ){
					if ( $current_user_is_tagged ){
						$tagged_txt = sprintf( __( 'You, %s, %s and %s', 'buddyboss-media' ), $tagged_for_display[0], $tagged_for_display[1], $tagged_for_display[2] );
					}
					else {
						$tagged_txt = sprintf( __( '%s, %s and %s', 'buddyboss-media' ), $tagged_for_display[0], $tagged_for_display[1], $tagged_for_display[2] );
					}
				}
				
				// 3 users + others
				else if ( count( $tagged_for_display ) === 3 && $others > 0 ){
					/** 
					 * We'll display only 2 users and rest go in tooltip.
					 * So lets move the last entry from tagged_for_display to tagged_for_tooltip.
					 */
					$tagged_for_display_last_entry = array_pop( $tagged_for_display );
					array_unshift( $tagged_for_tooltip, $tagged_for_display_last_entry );
					$others++;
					
					$others_count_txt = number_format_i18n( $others );
					$others_i18n = sprintf( __( '%s others', 'buddyboss-media' ), $others_count_txt );
					$others_txt = '<a class="buddyboss-media-tt-others">' . $others_i18n . '</a>';

					if ( $current_user_is_tagged ){
						//You, member one, member two and 7 others
						$tagged_txt = sprintf( __( 'You, %s, %s and %s', 'buddyboss-media' ), $tagged_for_display[0], $tagged_for_display[1], $others_txt );
					}
					else {
						//member one, member two and 7 others
						$tagged_txt = sprintf( __( '%s, %s and %s', 'buddyboss-media' ), $tagged_for_display[0], $tagged_for_display[1], $others_txt );
					}
				}
			}
			
			if( $tagged_txt ){
				$action .= sprintf( __( ' - with %s', 'buddyboss-media' ), $tagged_txt );
			}
			
			//save tooltip text to be used later
			if( !empty( $tagged_for_tooltip ) ){
				$this->tooltip_texts[bp_get_activity_id()] = implode( '<br>', $tagged_for_tooltip );
			}
		}
		return $action;
	}
	
	function activity_tagging_tooltip_text( $activity_id=false, $echo=true ){
		global $activities_template;

		if ( ! $activity_id && isset( $activities_template ) )
			$activity_id = $activities_template->activity->id;

		if ( $this->tooltip_texts && isset( $this->tooltip_texts[$activity_id] ) ) {
			$html = "<div class='buddyboss-media-tt-content' style='display: none'>" . $this->tooltip_texts[$activity_id] . "</div>";
			if( $echo ){
				echo $html;
			} else {
				return $html;
			}
		}
	}
	
} //end BuddyBoss_Media_Tagging_Hooks

BuddyBoss_Media_Tagging_Hooks::get_instance();
endif;