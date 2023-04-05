<?php
/**
 * Add admin Connections settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main Connection settings class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Admin_Setting_Friends extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'Connections', 'buddyboss' );
		$this->tab_name  = 'bp-friends';
		$this->tab_order = 60;
	}

	public function is_active() {
		return bp_is_active( 'friends' );
	}

	public function register_fields() {
		$this->add_section( 'bp_friends', __( 'Connection Settings', 'buddyboss' ), '', array( $this, 'bp_connection_settings_tutorial' ) );

		if ( bp_is_active( 'messages' ) ) {
			$this->add_field( 'bp-force-friendship-to-message', __( 'Messaging', 'buddyboss' ), array( $this, 'bp_admin_setting_callback_force_friendship_to_message' ), array( $this, 'bp_admin_sanitize_callback_force_friendship_to_message' ) );
		}

		if ( bp_is_active( 'activity' ) && bp_is_activity_follow_active() ) {
			// Allow auto follow.
			$this->add_field( 'bb_enable_friends_auto_follow', __( 'Auto Follow', 'buddyboss' ), array( $this, 'bb_admin_setting_callback_enable_friends_auto_follow' ) );
		}

		/**
		 * Fires to register Friends tab settings fields and section.
		 *
		 * @since BuddyBoss 1.2.6
		 *
		 * @param Object $this BP_Admin_Setting_Friends.
		 */
		do_action( 'bp_admin_setting_friends_register_fields', $this );
	}

	/**
	 * Force users to be connected before sending a message to each other.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function bp_admin_setting_callback_force_friendship_to_message() {
		?>
		<input id="bp-force-friendship-to-message" name="bp-force-friendship-to-message" type="checkbox" value="1" <?php checked( bp_force_friendship_to_message( false ) ); ?> />
		<label for="bp-force-friendship-to-message"><?php _e( 'Require non-admin members to be connected before they can message each other', 'buddyboss' ); ?></label>
		<?php
	}

	/**
	 * Allow auto following members after connecting
	 *
	 * @since BuddyBoss 2.3.1
	 *
	 * @return void
	 */
	public function bb_admin_setting_callback_enable_friends_auto_follow() {
		?>

		<input id="bb_enable_friends_auto_follow" name="bb_enable_friends_auto_follow" type="checkbox" value="1" <?php checked( bb_is_friends_auto_follow_active(), true ); ?> />
		<label for="bb_enable_friends_auto_follow"><?php esc_html_e( 'Automatically have members follow a member they connect with', 'buddyboss' ); ?></label>

		<?php
	}

	/**
	 * Sanitization for bp-force-friendship-to-message setting.
	 *
	 * In the UI, a checkbox asks whether you'd like to *enable* forceing users to be friends for messaging. For
	 * legacy reasons, the option that we store is 1 if these friends or messaging is *disabled*. So we use this
	 * function to flip the boolean before saving the intval.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether or not to sanitize.
	 * @return bool
	 */
	public function bp_admin_sanitize_callback_force_friendship_to_message( $value = false ) {
		return $value ? 1 : 0;
	}

	/**
	 * Link to Connection Settings tutorial
	 *
	 * @since BuddyBoss 1.0.0
	 */
	function bp_connection_settings_tutorial() {
		?>

		<p>
			<a class="button" href="<?php echo bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62835,
					),
					'admin.php'
				)
			); ?>"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
		</p>

		<?php
	}
}

return new BP_Admin_Setting_Friends();
