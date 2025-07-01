<?php
/**
 * ReadyLaunch - Member Profile Change Cover Image template.
 *
 * This template handles changing member profile cover photos.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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
