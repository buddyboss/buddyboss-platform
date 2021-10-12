<?php
/**
 * BuddyBoss - Groups Cover Photo Header.
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

$group_link               = bp_get_group_permalink();
$admin_link               = trailingslashit( $group_link . 'admin' );
$group_avatar             = trailingslashit( $admin_link . 'group-avatar' );
$group_cover_link         = trailingslashit( $admin_link . 'group-cover-image' );
$group_cover_image        = bp_attachments_get_attachment( 'url', array( 'object_dir' => 'groups', 'item_id'    => bp_get_group_id() ) );
$has_cover_image          = '';
$has_cover_image_position = '';

?>

<div id="cover-image-container">

	<?php
		if ( ! empty( $group_cover_image ) ) {
			$group_cover_position = groups_get_groupmeta( bp_get_current_group_id(), 'bp_cover_position', true );
			$has_cover_image = ' has-cover-image';
			if ( '' !== $group_cover_position ) {
				$has_cover_image_position = 'has-position';
			}
		}
	?>

	<div id="header-cover-image" class="<?php echo $has_cover_image_position; echo $has_cover_image; ?>">
		<?php if ( bp_group_use_cover_image_header() ) {

			if ( ! empty( $group_cover_image ) ) {
				echo '<img class="header-cover-img" src="' . esc_url( $group_cover_image ) . '"' . ( '' !== $group_cover_position ? ' data-top="' . $group_cover_position . '"' : '' ) . ( '' !== $group_cover_position ? ' style="top: ' . $group_cover_position . 'px"' : '' ) . ' alt="" />';
			}
			?>
			<?php if ( bp_is_item_admin() ) { ?>
				<a href="<?php echo $group_cover_link; ?>" class="link-change-cover-image bp-tooltip" data-bp-tooltip-pos="right" data-bp-tooltip="<?php esc_attr_e('Change Cover Photo', 'buddyboss'); ?>">
				<i class="bb-icon-edit-thin"></i>
			</a>
			<?php } ?>

			<?php if ( ! empty( $group_cover_image ) && bp_is_item_admin() ) { ?>
				<a href="#" class="position-change-cover-image bp-tooltip" data-bp-tooltip-pos="right" data-bp-tooltip="<?php esc_attr_e('Reposition Cover Photo', 'buddyboss'); ?>">
					<i class="bb-icon-move"></i>
				</a>
				<div class="header-cover-reposition-wrap">
					<a href="#" class="button small cover-image-cancel"><?php _e('Cancel', 'buddyboss'); ?></a>
					<a href="#" class="button small cover-image-save"><?php _e('Save Changes', 'buddyboss'); ?></a>
					<span class="drag-element-helper"><i class="bb-icon-menu"></i><?php _e('Drag to move cover photo', 'buddyboss'); ?></span>
					<img src="<?php echo esc_url( $group_cover_image );  ?>" alt="<?php _e('Cover photo', 'buddyboss'); ?>" />
				</div>
			<?php } ?>
		<?php } ?>
	</div>

	<div id="item-header-cover-image" class="item-header-wrap">
		<?php if ( ! bp_disable_group_avatar_uploads() ) : ?>
			<div id="item-header-avatar">
				<?php if ( bp_is_item_admin() ) { ?>
					<a href="<?php echo $group_avatar; ?>" class="link-change-profile-image bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e('Change Group Photo', 'buddyboss'); ?>">
						<i class="bb-icon-edit-thin"></i>
					</a>
				<?php } ?>
				<?php bp_group_avatar(); ?>
			</div><!-- #item-header-avatar -->
		<?php endif; ?>

		<?php if ( ! bp_nouveau_groups_front_page_description() ) : ?>
			<div id="item-header-content">

				<p class="highlight group-status bp-tooltip" data-bp-tooltip-length="large" data-bp-tooltip-pos="up" data-bp-tooltip="<?php echo esc_html( bp_get_group_status_description() ); ?>"><strong><?php echo wp_kses( bp_nouveau_group_meta()->status, array( 'span' => array( 'class' => array() ) ) ); ?></strong></p>
				<p class="activity">
					<a href="<?php echo esc_url( bp_get_group_permalink() . 'members' ); ?>"><?php echo esc_html( bp_get_group_member_count() ); ?></a>
				</p>

				<?php echo isset( bp_nouveau_group_meta()->group_type_list ) ? bp_nouveau_group_meta()->group_type_list : ''; ?>
				<?php bp_nouveau_group_hook( 'before', 'header_meta' ); ?>

				<?php if ( bp_nouveau_group_has_meta_extra() ) : ?>
					<div class="item-meta">

						<?php echo bp_nouveau_group_meta()->extra; ?>

					</div><!-- .item-meta -->
				<?php endif; ?>

				<?php bp_nouveau_group_header_buttons(); ?>

				<?php bb_nouveau_group_header_bubble_buttons(); ?>
			</div><!-- #item-header-content -->
		<?php endif; ?>

		<?php bp_get_template_part( 'groups/single/parts/header-item-actions' ); ?>

	</div><!-- #item-header-cover-image -->

</div><!-- #cover-image-container -->

<?php if ( ! bp_nouveau_groups_front_page_description() && bp_nouveau_group_has_meta( 'description' ) ) : ?>
	<div class="desc-wrap">
		<div class="group-description">
			<?php bp_group_description(); ?>
		</div><!-- //.group_description -->
	</div>
<?php endif; ?>
