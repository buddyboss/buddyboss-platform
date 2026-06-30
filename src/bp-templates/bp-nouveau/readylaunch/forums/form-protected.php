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
		<Legend><?php esc_html_e( 'Protected', 'buddyboss-platform' ); ?></legend>

		<?php
		// get_the_password_form() returns core-generated markup (already filtered
		// via the `the_password_form` hook). It must NOT be passed through
		// wp_kses_post(), whose post allowlist strips <form> and <input>, leaving
		// only the "Password:" label with no field. Echo the trusted form directly.
		echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core-generated, already-filtered password form; escaping would strip its inputs.
		?>

	</fieldset>
</div>
