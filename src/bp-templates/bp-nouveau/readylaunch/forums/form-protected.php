<?php
/**
 * Password Protected Form Template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<div id="bbpress-forums">
	<fieldset class="bbp-form" id="bbp-protected">
		<Legend><?php esc_html_e( 'Protected', 'buddyboss' ); ?></legend>

		<?php echo wp_kses_post( get_the_password_form() ); ?>

	</fieldset>
</div>
