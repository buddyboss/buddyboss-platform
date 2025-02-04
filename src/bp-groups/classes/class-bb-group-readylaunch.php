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

		add_action( 'bb_rl_footer', array( $this, 'bb_rl_load_popup' ) );
	}

	public function bb_rl_update_group_subscription_button( $button, $r ) {
		$button['link_text']                           = str_replace( '<i class="bb-icon-l bb-icon-bell"></i>', '<i class="bb-icons-rl-bell"></i>', $button['link_text'] );
		$button['button_attr']['data-title']           = str_replace( '<i class="bb-icon-l bb-icon-bell"></i>', '<i class="bb-icons-rl-bell"></i>', $button['button_attr']['data-title'] );
		$button['button_attr']['data-title-displayed'] = str_replace( '<i class="bb-icon-l bb-icon-bell"></i>', '<i class="bb-icons-rl-bell"></i>', $button['button_attr']['data-title-displayed'] );
		$button['data-balloon-pos']                    = 'left';

		return $button;
	}

	public function bb_rl_get_groups_bubble_buttons( $buttons, $group, $type ) {
		$buttons['about-group'] = array(
			'id' => 'about-group',
			'link_text' => __( 'About group', 'buddyboss' ),
			'position' => 10,
			'component' => 'groups',
			'button_element' => 'a',
			'button_attr' => array(
				'id' => 'about-group-' . $group->id,
				'href' => bp_get_group_permalink( $group ) . '#about-group-' . $group->id,
				'class' => 'button item-button bp-secondary-action about-group',
				'data-bp-content-type' => 'group-info',
			)
		);

		if ( bp_is_item_admin() ) {
			$buttons['group-manage'] = array(
				'id' => 'group-manage',
				'link_text' => __( 'Manage', 'buddyboss' ),
				'position' => 20,
				'component' => 'groups',
				'button_element' => 'a',
				'button_attr' => array(
					'id' => 'group-manage-' . $group->id,
					'href' => bp_get_group_permalink( $group ) . '#group-manage-' . $group->id,
					'class' => 'button item-button bp-secondary-action group-manage',
					'data-bp-content-type' => 'group-manage',
				)
			);

			$buttons['delete-group'] = array(
				'id' => 'delete-group',
				'link_text' => __( 'Delete', 'buddyboss' ),
				'position' => 1000,
				'component' => 'groups',
				'button_element' => 'a',
				'button_attr' => array(
					'id' => 'delete-group-' . $group->id,
					'href' => bp_get_group_permalink( $group ) . '#delete-group-' . $group->id,
					'class' => 'button item-button bp-secondary-action delete-group',
					'data-bp-content-type' => 'delete-group',
				)
			);
		}

		return $buttons;
	}

	public function bb_rl_load_popup() {

	}
}
