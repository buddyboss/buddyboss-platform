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

/**
 * Main settings section description for the settings page.
 *
 * @since BuddyPress 1.6.0
 */
function bp_admin_setting_callback_main_section() { }

/**
 * Admin bar for logged out users setting field.
 *
 * @since BuddyPress 1.6.0
 *
 */
function bp_admin_setting_callback_admin_bar() {
?>

	<input id="hide-loggedout-adminbar" name="hide-loggedout-adminbar" type="checkbox" value="1" <?php checked( !bp_hide_loggedout_adminbar( false ) ); ?> />
	<label for="hide-loggedout-adminbar"><?php _e( 'Show the Toolbar for logged out users', 'buddyboss' ); ?></label>

<?php
}

/**
 * Allow members to delete their accounts setting field.
 *
 * @since BuddyPress 1.6.0
 *
 */
function bp_admin_setting_callback_account_deletion() {
?>

	<input id="bp-disable-account-deletion" name="bp-disable-account-deletion" type="checkbox" value="1" <?php checked( !bp_disable_account_deletion( false ) ); ?> />
	<label for="bp-disable-account-deletion"><?php _e( 'Allow registered members to delete their own accounts', 'buddyboss' ); ?></label>

<?php
}

/**
 * Allow admin to make the site private network.
 *
 * @since BuddyBoss 3.1.1
 *
 */
function bp_admin_setting_callback_private_network() {
	?>

	<input id="bp-enable-private-network" name="bp-enable-private-network" type="checkbox" value="1" <?php checked( !bp_enable_private_network( false ) ); ?> />
	<label for="bp-enable-private-network"><?php _e( 'Block entire website from logged out users (but allow Login and Register)', 'buddyboss' ); ?></label>

	<?php
}

/** Activity *******************************************************************/

/**
 * Groups settings section description for the settings page.
 *
 * @since BuddyPress 1.6.0
 */
function bp_admin_setting_callback_activity_section() { }

/**
 * Allow Akismet setting field.
 *
 * @since BuddyPress 1.6.0
 *
 */
function bp_admin_setting_callback_activity_akismet() {
?>

	<input id="_bp_enable_akismet" name="_bp_enable_akismet" type="checkbox" value="1" <?php checked( bp_is_akismet_active( true ) ); ?> />
	<label for="_bp_enable_akismet"><?php _e( 'Allow Akismet to scan for activity stream spam', 'buddyboss' ); ?></label>

<?php
}

/**
 * Allow activity comments on posts and comments.
 *
 * @since BuddyPress 1.6.0
 */
function bp_admin_setting_callback_blogforum_comments() {
?>

	<input id="bp-disable-blogforum-comments" name="bp-disable-blogforum-comments" type="checkbox" value="1" <?php checked( !bp_disable_blogforum_comments( false ) ); ?> />
	<label for="bp-disable-blogforum-comments"><?php _e( 'Allow activity stream commenting on posts and comments', 'buddyboss' ); ?></label>

<?php
}

/**
 * Allow Heartbeat to refresh activity stream.
 *
 * @since BuddyPress 2.0.0
 */
function bp_admin_setting_callback_heartbeat() {
?>

	<input id="_bp_enable_heartbeat_refresh" name="_bp_enable_heartbeat_refresh" type="checkbox" value="1" <?php checked( bp_is_activity_heartbeat_active( true ) ); ?> />
	<label for="_bp_enable_heartbeat_refresh"><?php _e( 'Automatically check for new items while viewing the activity stream', 'buddyboss' ); ?></label>

<?php
}

/**
 * Sanitization for bp-disable-blogforum-comments setting.
 *
 * In the UI, a checkbox asks whether you'd like to *enable* post/comment activity comments. For
 * legacy reasons, the option that we store is 1 if these comments are *disabled*. So we use this
 * function to flip the boolean before saving the intval.
 *
 * @since BuddyPress 1.6.0
 *
 * @param bool $value Whether or not to sanitize.
 * @return bool
 */
function bp_admin_sanitize_callback_blogforum_comments( $value = false ) {
	return $value ? 0 : 1;
}

/** XProfile ******************************************************************/

/**
 * Profile settings section description for the settings page.
 *
 * @since BuddyPress 1.6.0
 */
function bp_admin_setting_callback_xprofile_section() { }

/**
 * Enable BP->WP profile syncing field.
 *
 * @since BuddyPress 1.6.0
 *
 */
function bp_admin_setting_callback_profile_sync() {
?>

	<input id="bp-disable-profile-sync" name="bp-disable-profile-sync" type="checkbox" value="1" <?php checked( !bp_disable_profile_sync( false ) ); ?> />
	<label for="bp-disable-profile-sync"><?php _e( 'Enable BuddyBoss to WordPress profile syncing', 'buddyboss' ); ?></label>

<?php
}

/**
 * Allow members to upload avatars field.
 *
 * @since BuddyPress 1.6.0
 *
 */
function bp_admin_setting_callback_avatar_uploads() {
?>

	<input id="bp-disable-avatar-uploads" name="bp-disable-avatar-uploads" type="checkbox" value="1" <?php checked( !bp_disable_avatar_uploads( false ) ); ?> />
	<label for="bp-disable-avatar-uploads"><?php _e( 'Allow registered members to upload avatars', 'buddyboss' ); ?></label>

<?php
}

/**
 * Allow members to upload cover images field.
 *
 * @since BuddyPress 2.4.0
 */
function bp_admin_setting_callback_cover_image_uploads() {
?>
	<input id="bp-disable-cover-image-uploads" name="bp-disable-cover-image-uploads" type="checkbox" value="1" <?php checked( ! bp_disable_cover_image_uploads() ); ?> />
	<label for="bp-disable-cover-image-uploads"><?php _e( 'Allow registered members to upload cover images', 'buddyboss' ); ?></label>
<?php
}

/** Groups Section ************************************************************/

/**
 * Groups settings section description for the settings page.
 *
 * @since BuddyPress 1.6.0
 */
function bp_admin_setting_callback_groups_section() { }

/**
 * Allow all users to create groups field.
 *
 * @since BuddyPress 1.6.0
 *
 */
function bp_admin_setting_callback_group_creation() {
?>

	<input id="bp_restrict_group_creation" name="bp_restrict_group_creation" type="checkbox" aria-describedby="bp_group_creation_description" value="1" <?php checked( !bp_restrict_group_creation( false ) ); ?> />
	<label for="bp_restrict_group_creation"><?php _e( 'Enable group creation for all users', 'buddyboss' ); ?></label>
	<p class="description" id="bp_group_creation_description"><?php _e( 'Administrators can always create groups, regardless of this setting.', 'buddyboss' ); ?></p>

<?php
}

/**
 * 'Enable group avatars' field markup.
 *
 * @since BuddyPress 2.3.0
 */
function bp_admin_setting_callback_group_avatar_uploads() {
?>
	<input id="bp-disable-group-avatar-uploads" name="bp-disable-group-avatar-uploads" type="checkbox" value="1" <?php checked( ! bp_disable_group_avatar_uploads() ); ?> />
	<label for="bp-disable-group-avatar-uploads"><?php _e( 'Allow customizable avatars for groups', 'buddyboss' ); ?></label>
<?php
}

/**
 * 'Enable group cover images' field markup.
 *
 * @since BuddyPress 2.4.0
 */
function bp_admin_setting_callback_group_cover_image_uploads() {
?>
	<input id="bp-disable-group-cover-image-uploads" name="bp-disable-group-cover-image-uploads" type="checkbox" value="1" <?php checked( ! bp_disable_group_cover_image_uploads() ); ?> />
	<label for="bp-disable-group-cover-image-uploads"><?php _e( 'Allow customizable cover images for groups', 'buddyboss' ); ?></label>
<?php
}

/** Settings Page *************************************************************/

/**
 * The main settings page
 *
 * @since BuddyPress 1.6.0
 *
 */
function bp_core_admin_settings() {
    $active_tab = bp_core_get_admin_active_tab();
    $form_action = bp_core_admin_setting_url( $active_tab );
	?>

	<div class="wrap">
		<h1><?php _e( 'BuddyBoss Settings', 'buddyboss' ); ?> </h1>
		<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs(); ?></h2>

		<form action="<?php echo esc_url( $form_action ) ?>" method="post">
            <?php bp_core_get_admin_active_tab_object()->form_html(); ?>
		</form>
	</div>

<?php
}

/**
 * The main settings page
 *
 * @since BuddyPress 1.6.0
 *
 */
function bp_core_admin_integrations() {
    $active_tab = bp_core_get_admin_integration_active_tab();
    $form_action = bp_core_admin_integrations_url( $active_tab );
    ?>

    <div class="wrap">
        <h1><?php _e( 'Plugin Integrations', 'buddyboss' ); ?> </h1>
        <h2 class="nav-tab-wrapper"><?php bp_core_admin_integration_tabs(); ?></h2>

        <form action="<?php echo esc_url( $form_action ) ?>" method="post">
            <?php bp_core_get_admin_integration_active_tab_object()->form_html(); ?>
        </form>
    </div>

<?php
}

function bp_core_admin_appboss() {
	require buddypress()->plugin_dir . 'bp-core/admin/templates/appboss-screen.php';
}

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
