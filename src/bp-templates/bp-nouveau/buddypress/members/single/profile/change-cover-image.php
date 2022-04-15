<?php
/**
 * The template for members profile change cover photo
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/profile/change-cover-image.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

?>

<h2 class="screen-heading change-cover-image-screen"><?php esc_html_e( 'Change Cover Photo', 'buddyboss' ); ?></h2>

<?php bp_nouveau_member_hook( 'before', 'edit_cover_image' ); ?>

<p class="info bp-feedback">
	<span class="bp-icon" aria-hidden="true"></span>
	<span class="bp-help-text"><?php esc_html_e( 'Your Cover Photo will be used to customize the header of your profile.', 'buddyboss' ); ?></span>
</p>

<?php
// Load the cover photo UI
bp_attachments_get_template_part( 'cover-images/index' );

bp_nouveau_member_hook( 'after', 'edit_cover_image' );
