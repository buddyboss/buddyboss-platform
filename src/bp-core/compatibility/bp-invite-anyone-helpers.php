<?php
/**
 * Added support for third party plugin Invite Anyone.
 *
 * @package BuddyBoss
 *
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BB_Invite_Anyone_Plugin_Compatibility' ) ) {

	/**
	 * BB_Invite_Anyone_Plugin_Compatibility Class.
	 *
	 * This class handles compatibility code for third party plugins used in conjunction with Platform.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	class BB_Invite_Anyone_Plugin_Compatibility {

		/**
		 * The single instance of the class.
		 *
		 * @since BuddyBoss [BBVERSION]
		 * @var self
		 */
		private static $instance = null;

		/**
		 * BB_Invite_Anyone_Plugin_Compatibility constructor.
		 */
		public function __construct() {
			$this->compatibility_init();
		}

		/**
		 * Get the instance of this class.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return Controller|null
		 */
		public static function instance() {

			if ( null === self::$instance ) {
				$class_name     = __CLASS__;
				self::$instance = new $class_name();
			}

			return self::$instance;
		}

		/**
		 * Register the compatibility hooks for the plugin.
		 */
		public function compatibility_init() {

			remove_filter( 'groups_create_group_steps', 'invite_anyone_remove_group_creation_invites', 1 );
			remove_action( 'bp_setup_nav', 'invite_anyone_remove_invite_subnav', 15 );

			add_filter( 'bp_before_create_group_content_template', array( $this, 'bb_rename_send_envites_steps_for_invite_anyone' ) );

		}

		/**
		 * Function to rename step name for both Buddyboss Platform and Invite Anyone
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_rename_send_envites_steps_for_invite_anyone() {

			global $bp;

			$options = invite_anyone_options();
			$enabled = ! empty( $options['group_invites_enable_create_step'] ) && 'yes' === $options['group_invites_enable_create_step'];

			if ( ! $enabled ) {
				return;
			}

			$bp->groups->group_creation_steps['group-invites']['name'] = __( 'Buddyboss Invites', 'buddyboss' );
			$bp->groups->group_creation_steps['invite-anyone']['name'] = __( 'Invite Anyone', 'buddyboss' );

		}

	}
}

BB_Invite_Anyone_Plugin_Compatibility::instance();
