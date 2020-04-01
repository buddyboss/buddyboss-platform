<?php
/**
 * BuddyBoss Groups Zoom Extension.
 *
 * @package BuddyBoss\Groups\Extensions
 * @since BuddyBoss 1.2.10
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_Group_Zoom_Extension' ) ) {
	/**
	 * Class BP_Group_Zoom_Extension
	 */
	class BP_Group_Zoom_Extension extends BP_Group_Extension {
		/**
		 * Your __construct() method will contain configuration options for
		 * your extension, and will pass them to parent::init()
		 *
		 * @since BuddyBoss 1.2.10
		 */
		function __construct() {
			$args = array(
				'slug'              => 'zoom',
				'name'              => __( 'Zoom', 'buddyboss' ),
				'nav_item_position' => 100,
				'enable_nav_item'   => false,
			);

			if ( bp_is_group() ) {
				// Tweak the nav item variable based on if group has zoom or not
				$args['enable_nav_item'] = ( bool ) groups_get_groupmeta( bp_get_current_group_id(), 'bp-group-zoom-conference' );
			}

			parent::init( $args );
		}

		/**
		 * display() contains the markup that will be displayed on the main
		 * plugin tab
		 *
		 * @since BuddyBoss 1.2.10
		 */
		function display( $group_id = null ) {
			$group_id = bp_get_group_id();
			echo 'What a cool plugin!';
		}

		/**
		 * settings_screen() is the catch-all method for displaying the content
		 * of the edit, create, and Dashboard admin panels
		 */
		function settings_screen( $group_id = null ) {
			$setting = groups_get_groupmeta( $group_id, 'bp-group-zoom-conference' );

			?>
			<p class="bp-controls-wrap">
				<input type="checkbox" name="bp-group-zoom-conference" id="bp-group-zoom-conference"
				       class="bs-styled-checkbox" value="1" <?php echo checked( $setting ); ?> />
				<label for="bp-group-zoom-conference"
				       class="bp-label-text"><?php esc_html_e( 'Enable Zoom Conference' ); ?></label>
			</p>
			<?php
		}

		/**
		 * settings_sceren_save() contains the catch-all logic for saving
		 * settings from the edit, create, and Dashboard admin panels
		 */
		function settings_screen_save( $group_id = null ) {
			$setting = '';

			if ( isset( $_POST['bp-group-zoom-conference'] ) ) {
				$setting = $_POST['bp-group-zoom-conference'];
			}

			groups_update_groupmeta( $group_id, 'bp-group-zoom-conference', $setting );
		}
	}

	if ( bp_is_active( 'groups' ) ) {
		bp_register_group_extension( 'BP_Group_Zoom_Extension' );
	}
}

