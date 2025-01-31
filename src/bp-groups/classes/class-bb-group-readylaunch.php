<?php
/**
 * BuddyBoss Groups Readylaunch.
 *
 * @package BuddyBoss\Groups\Classes
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

class BB_Group_Readylaunch {

	/**
	 * The single instance of the class.
	 *
	 * @since  BuddyBoss [BBVERSION]
	 *
	 * @access private
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Get the instance of this class.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return Controller|BB_Group_Readylaunch|null
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
		}

		return self::$instance;
	}

	/**
	 * Constructor method.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		add_filter( 'bb_group_subscription_button_args', array( $this, 'bb_rl_update_group_subscription_button' ), 10, 2 );
		add_filter( 'bb_nouveau_get_groups_bubble_buttons', array( $this, 'bb_rl_get_groups_bubble_buttons' ), 10, 3 );
	}

	public function bb_rl_update_group_subscription_button( $button, $r ) {
		$button['link_text']                           = str_replace( '<i class="bb-icon-l bb-icon-bell"></i>', '<i class="bb-icons-rl-bell"></i>', $button['link_text'] );
		$button['button_attr']['data-title']           = str_replace( '<i class="bb-icon-l bb-icon-bell"></i>', '<i class="bb-icons-rl-bell"></i>', $button['button_attr']['data-title'] );
		$button['button_attr']['data-title-displayed'] = str_replace( '<i class="bb-icon-l bb-icon-bell"></i>', '<i class="bb-icons-rl-bell"></i>', $button['button_attr']['data-title-displayed'] );
		$button['data-balloon-pos']                    = 'left';

		return $button;
	}

	public function bb_rl_get_groups_bubble_buttons( $buttons, $group, $type ) {


		return $buttons;
	}
}
