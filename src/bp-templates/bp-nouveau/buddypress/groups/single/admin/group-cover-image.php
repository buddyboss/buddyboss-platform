<?php
/**
 * BP Nouveau Group's cover image template.
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

$group_link = bp_get_group_permalink();
$admin_link = trailingslashit( $group_link . 'admin' );
$group_avatar = trailingslashit( $admin_link . 'group-avatar' );
$group_cover_link = trailingslashit( $admin_link . 'group-cover-image' );
?>

<?php if ( bp_is_group_create() ) : ?>

	<h2 class="bp-screen-title creation-step-name">
		<?php esc_html_e( 'Upload Cover Image', 'buddyboss' ); ?>
	</h2>

	<div id="header-cover-image">
		<?php if ( bp_is_item_admin() && bp_group_use_cover_image_header() ) { ?>
			<a href="<?php echo $group_cover_link; ?>" class="link-change-cover-image">
				<span class="bp-tooltip icon-wrap" data-bp-tooltip="<?php _e('Change Cover Image', 'buddypress'); ?>"><span class="dashicons dashicons-camera"></span></span>
			</a>
		<?php } ?>
	</div>

<?php else : ?>

	<h2 class="bp-screen-title">
		<?php esc_html_e( 'Change Cover Image', 'buddyboss' ); ?>
	</h2>

<?php endif; ?>

<p><?php esc_html_e( 'The Cover Image will be used to customize the header of your group.', 'buddyboss' ); ?></p>

<?php
bp_attachments_get_template_part( 'cover-images/index' );
