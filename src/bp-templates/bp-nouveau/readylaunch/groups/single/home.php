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

			<div class="bb-rl-group-section">
				<?php
				if ( bp_group_use_cover_image_header() ) {
					bp_get_template_part( 'groups/single/cover-image-header' );
				}
				?>
				<div class="bb-rl-group-details">
					<div id="item-body" class="item-body">
						<?php
						if ( bp_is_group_subgroups() ) {
						echo $template_content; // phpcs:ignore
						} else {
							bp_nouveau_group_template_part();
						}
						?>
					</div><!-- #item-body -->
				</div><!-- // .bb-rl-group-details -->
			</div>

			</div>
		<?php
		bp_nouveau_group_hook( 'after', 'home_content' );

	endwhile;
}
