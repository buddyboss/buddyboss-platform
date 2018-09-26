<?php
/**
 * BuddyBoss Admin Settings.
 *
 * @package BuddyBoss
 * @subpackage CoreAdministration
 * @since BuddyPress 2.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/** Settings Page *************************************************************/

/**
 * The main settings page
 *
 * @since BuddyPress 1.6.0
 *
 */
function bp_core_admin_settings() {

	// We're saving our own options, until the WP Settings API is updated to work with Multisite.
	$form_action = add_query_arg( 'page', 'bp-settings', bp_get_admin_url( 'admin.php' ) );

	?>

	<div class="wrap">

		<h1><?php _e( 'BuddyBoss Settings', 'buddyboss' ); ?> </h1>

		<form action="<?php echo esc_url( $form_action ) ?>" method="post">

			<?php settings_fields( 'buddypress' ); ?>

			<?php do_settings_sections( 'buddypress' ); ?>

			<p class="submit">
				<input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e( 'Save Settings', 'buddyboss' ); ?>" />
			</p>
		</form>
	</div>

<?php
}

/**
 * Save our settings.
 *
 * @since BuddyPress 1.6.0
 */
function bp_core_admin_settings_save() {
	global $wp_settings_fields;

	if ( isset( $_GET['page'] ) && 'bp-settings' == $_GET['page'] && !empty( $_POST['submit'] ) ) {
		check_admin_referer( 'buddypress-options' );

		// Because many settings are saved with checkboxes, and thus will have no values
		// in the $_POST array when unchecked, we loop through the registered settings.
		if ( isset( $wp_settings_fields['buddypress'] ) ) {
			foreach( (array) $wp_settings_fields['buddypress'] as $section => $settings ) {
				foreach( $settings as $setting_name => $setting ) {
					$value = isset( $_POST[$setting_name] ) ? $_POST[$setting_name] : '';

					bp_update_option( $setting_name, $value );
				}
			}
		}

		// Some legacy options are not registered with the Settings API, or are reversed in the UI.
		$legacy_options = array(
			'bp-disable-account-deletion',
			'bp-disable-avatar-uploads',
			'bp-disable-cover-image-uploads',
			'bp-disable-group-avatar-uploads',
			'bp-disable-group-cover-image-uploads',
			'bp_disable_blogforum_comments',
			'bp-disable-profile-sync',
			'bp_restrict_group_creation',
			'hide-loggedout-adminbar',
		);

		foreach( $legacy_options as $legacy_option ) {
			// Note: Each of these options is represented by its opposite in the UI
			// Ie, the Profile Syncing option reads "Enable Sync", so when it's checked,
			// the corresponding option should be unset.
			$value = isset( $_POST[$legacy_option] ) ? '' : 1;
			bp_update_option( $legacy_option, $value );
		}

        /**
         * sync bp-enable-member-dashboard with cutomizer settings.
         * @since BuddyBoss 3.1.1
         */
        $bp_nouveau_appearance = bp_get_option( 'bp_nouveau_appearance', array() );
        $bp_nouveau_appearance[ 'user_front_page' ] = isset( $_POST[ 'bp-enable-member-dashboard' ] ) ? $_POST[ 'bp-enable-member-dashboard' ] : 0;
        bp_update_option( 'bp_nouveau_appearance', $bp_nouveau_appearance );

		bp_core_redirect( add_query_arg( array( 'page' => 'bp-settings', 'updated' => 'true' ), bp_get_admin_url( 'admin.php' ) ) );
	}
}
add_action( 'bp_admin_init', 'bp_core_admin_settings_save', 100 );

/**
 * Output settings API option.
 *
 * @since BuddyPress 1.6.0
 *
 * @param string $option  Form option to echo.
 * @param string $default Form option default.
 * @param bool   $slug    Form option slug.
 */
function bp_form_option( $option, $default = '' , $slug = false ) {
	echo bp_get_form_option( $option, $default, $slug );
}
	/**
	 * Return settings API option
	 *
	 * @since BuddyPress 1.6.0
	 *
	 *
	 * @param string $option  Form option to return.
	 * @param string $default Form option default.
	 * @param bool   $slug    Form option slug.
	 * @return string
	 */
	function bp_get_form_option( $option, $default = '', $slug = false ) {

		// Get the option and sanitize it.
		$value = bp_get_option( $option, $default );

		// Slug?
		if ( true === $slug ) {

			/**
			 * Filters the slug value in the form field.
			 *
			 * @since BuddyPress 1.6.0
			 *
			 * @param string $value Value being returned for the requested option.
			 */
			$value = esc_attr( apply_filters( 'editable_slug', $value ) );
		} else { // Not a slug.
			$value = esc_attr( $value );
		}

		// Fallback to default.
		if ( empty( $value ) )
			$value = $default;

		/**
		 * Filters the settings API option.
		 *
		 * @since BuddyPress 1.6.0
		 *
		 * @param string $value  Value being returned for the requested option.
		 * @param string $option Option whose value is being requested.
		 */
		return apply_filters( 'bp_get_form_option', $value, $option );
	}
