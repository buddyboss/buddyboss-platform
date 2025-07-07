<?php
/**
 * ReadyLaunch - Member Settings Export Data template.
 *
 * This template handles the data export functionality for members.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_nouveau_user_feedback( 'member-data-export' );
?>

<form action="<?php echo esc_url( bp_displayed_user_domain() . bp_get_settings_slug() . '/export/' ); ?>" name="account-data-export-form" id="account-data-export-form" class="standard-form" method="post">
	<?php bp_nouveau_submit_button( 'member-data-export' ); ?>
</form>
