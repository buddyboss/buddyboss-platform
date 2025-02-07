<?php
/**
 * BuddyBoss - Groups Cover Photo Header.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/cover-image-header.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

$group_link               = bp_get_group_permalink();
$admin_link               = trailingslashit( $group_link . 'admin' );
$group_avatar             = trailingslashit( $admin_link . 'group-avatar' );
$group_cover_link         = trailingslashit( $admin_link . 'group-cover-image' );
$group_cover_width        = bb_get_group_cover_image_width();
$group_cover_height       = bb_get_group_cover_image_height();
$group_cover_image        = bp_attachments_get_attachment(
	'url',
	array(
		'object_dir' => 'groups',
		'item_id'    => bp_get_group_id(),
	)
);
$has_cover_image          = '';
$has_cover_image_position = '';
$has_default_cover        = bb_attachment_get_cover_image_class( bp_get_group_id(), 'group' );
?>

<div id="cover-image-container" class="bb-rl-group-cover">

	<?php
	if ( ! empty( $group_cover_image ) ) {
		$group_cover_position = groups_get_groupmeta( bp_get_current_group_id(), 'bp_cover_position', true );
		$has_cover_image      = ' has-cover-image';
		if ( '' !== $group_cover_position ) {
			$has_cover_image_position = ' has-position';
		}
	}
	?>

	<div id="header-cover-image" class="<?php echo esc_attr( 'cover-' . $group_cover_height . ' width-' . $group_cover_width . $has_cover_image_position . $has_cover_image . $has_default_cover ); ?> bb-rl-header-cover">
		<?php
		if ( bp_group_use_cover_image_header() ) {

			if ( ! empty( $group_cover_image ) ) {
				?>
				<img class="header-cover-img" src="<?php echo esc_url( $group_cover_image ); ?>" <?php echo ( '' !== $group_cover_position ) ? ' data-top="' . esc_attr( $group_cover_position ) . '"' : ''; ?> <?php echo ( '' !== $group_cover_position ) ? ' style="top: ' . esc_attr( $group_cover_position ) . 'px"' : ''; ?> alt="" />
				<?php
			}
			?>

			<?php if ( bp_is_item_admin() ) { ?>
				<a href="<?php echo esc_url( $group_cover_link ); ?>" class="link-change-cover-image bp-tooltip bb-rl-group-cover-ctrl" data-bp-tooltip-pos="right" data-bp-tooltip="<?php esc_attr_e( 'Change Cover Photo', 'buddyboss' ); ?>">
					<i class="bb-icons-rl-camera"></i>
				</a>
			<?php } ?>

			<?php if ( ! empty( $group_cover_image ) && bp_is_item_admin() && bp_attachments_get_group_has_cover_image( bp_get_group_id() ) ) { ?>
				<a href="#" class="position-change-cover-image bp-tooltip bb-rl-group-cover-ctrl" data-bp-tooltip-pos="right" data-bp-tooltip="<?php esc_attr_e( 'Reposition Cover Photo', 'buddyboss' ); ?>">
					<i class="bb-icons-rl-arrows-out-cardinal"></i>
				</a>
				<div class="header-cover-reposition-wrap">
					<a href="#" class="button small cover-image-cancel"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
					<a href="#" class="button small cover-image-save"><?php esc_html_e( 'Save Changes', 'buddyboss' ); ?></a>
					<span class="drag-element-helper"><i class="bb-icons-rl-list"></i><?php esc_html_e( 'Drag to move cover photo', 'buddyboss' ); ?></span>
					<img src="<?php echo esc_url( $group_cover_image ); ?>" alt="<?php esc_attr_e( 'Cover photo', 'buddyboss' ); ?>" />
				</div>
			<?php } ?>
		<?php } ?>
	</div>
</div><!-- #cover-image-container -->
