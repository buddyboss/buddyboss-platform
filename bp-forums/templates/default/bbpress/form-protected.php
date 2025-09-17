<?php

/**
 * Password Protected
 *
 * @package BuddyBoss\Theme
 */

?>

<div id="bbpress-forums">
	<fieldset class="bbp-form" id="bbp-protected">
		<Legend><?php _e( 'Protected', 'buddyboss' ); ?></legend>

		<?php echo get_the_password_form(); ?>

	</fieldset>
</div>
