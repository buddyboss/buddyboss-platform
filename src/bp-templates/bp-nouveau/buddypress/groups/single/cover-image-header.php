<?php
/**
 * BuddyBoss - Groups Cover Photo Header.
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

$group_link = bp_get_group_permalink();
$admin_link = trailingslashit( $group_link . 'admin' );
$group_avatar = trailingslashit( $admin_link . 'group-avatar' );
$group_cover_link = trailingslashit( $admin_link . 'group-cover-image' );
?>

<div id="cover-image-container">

	<div id="header-cover-image">
		<?php if ( bp_is_item_admin() && bp_group_use_cover_image_header() ) { ?>
			<a href="<?php echo $group_cover_link; ?>" class="link-change-cover-image bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e('Change Cover Photo', 'buddyboss'); ?>">
				<i class="bb-icon-edit-thin"></i>
			</a>
		<?php } ?>
	</div>

	<div id="item-header-cover-image" class="item-header-wrap">
		<?php if ( ! bp_disable_group_avatar_uploads() ) : ?>
			<div id="item-header-avatar">
				<?php if ( bp_is_item_admin() ) { ?>
					<a href="<?php echo $group_avatar; ?>" class="link-change-profile-image bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e('Change Group Photo', 'buddyboss'); ?>">
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
