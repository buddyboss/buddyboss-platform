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
		<Legend><?php _e( 'Protected', 'buddyboss-platform' ); ?></legend>

		<?php echo get_the_password_form(); ?>

	</fieldset>
</div>
