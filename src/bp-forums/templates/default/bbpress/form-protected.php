<?php

/**
 * Password Protected
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div id="bbpress-forums">
	<fieldset class="bbp-form" id="bbp-protected">
		<Legend><?php esc_html_e( 'Protected', 'buddyboss-platform' ); ?></legend>

		<?php echo get_the_password_form(); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_the_password_form() is a WordPress core function returning the password <form> HTML; wp_kses_post would strip its form controls. */ ?>

	</fieldset>
</div>
