<?php
/**
 * BuddyBoss - Groups Home
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/home.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

if ( bp_is_group_subgroups() ) {
	ob_start();
	bp_nouveau_group_template_part();
	$template_content = ob_get_contents();
	ob_end_clean();
}

if ( bp_has_groups() ) {
	while ( bp_groups() ) :
		bp_the_group();
		?>
		<div class="bb-rl-groups-single-wrapper">

			<div class="bb-rl-secondary-header flex flex-column">
				<div class="bb-rl-group-info-wrap flex items-start justify-between">
					<div class="bb-rl-group-info flex items-center">
						<?php
						if ( ! bp_disable_group_avatar_uploads() ) :
							$group_link       = bp_get_group_permalink();
							$admin_link       = trailingslashit( $group_link . 'admin' );
							$group_avatar     = trailingslashit( $admin_link . 'group-avatar' );
							$tooltip_position = bp_disable_group_cover_image_uploads() ? 'down' : 'up';
							?>
							<div id="bb-rl-item-header-avatar">
								<?php if ( bp_is_item_admin() ) { ?>
									<a href="<?php echo esc_url( $group_avatar ); ?>" class="link-change-profile-image bb-rl-tooltip flex justify-center items-center" data-bb-rl-tooltip-pos="up" data-bb-rl-tooltip="<?php esc_attr_e( 'Change Group Photo', 'buddyboss' ); ?>">
										<i class="bb-icons-rl-camera"></i>
									</a>
								<?php } ?>
								<?php bp_group_avatar(); ?>
							</div><!-- #item-header-avatar -->
						<?php endif; ?>
						<h2 class="bb-rl-group-title"><?php echo wp_kses_post( bp_get_group_name() ); ?></h2>
					</div>
					<div class="bb-rl-group-extra-info">
						<?php
							bb_groups_members();
							bb_group_single_header_actions();
						?>
					</div>

				</div>
				<?php bp_get_template_part( 'groups/single/parts/item-nav' ); ?>
			</div>
			<?php /* ?>
			<div id="item-header" role="complementary" data-bp-item-id="<?php bp_group_id(); ?>" data-bp-item-component="groups" class="groups-header single-headers">
				<?php bp_nouveau_group_header_template_part(); ?>
			</div><!-- #item-header -->

			<div class="bp-wrap">
				<?php
				// if ( ! bp_nouveau_is_object_nav_in_sidebar() ) {
				// bp_get_template_part( 'groups/single/parts/item-nav' );
				// }
				?>

				<div id="item-body" class="item-body">
					<?php
					// if ( bp_is_group_subgroups() ) {
					// echo $template_content; // phpcs:ignore
					// } else {
					// bp_nouveau_group_template_part();
					// }
					?>
				</div><!-- #item-body -->
			</div><!-- // .bp-wrap -->
 			<?php */ ?>
			</div>
		<?php
		bp_nouveau_group_hook( 'after', 'home_content' );

	endwhile;
}
