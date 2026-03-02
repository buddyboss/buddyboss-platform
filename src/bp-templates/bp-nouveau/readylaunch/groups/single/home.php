<?php
/**
 * ReadyLaunch - Groups Home template.
 *
 * This template handles the main group home page layout
 * with header, navigation, and content sections.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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
		<div class="bb-rl-groups-single-wrapper" data-bp-item-id="<?php echo esc_attr( bp_get_current_group_id() ); ?>" data-bp-item-component="<?php echo esc_attr( bp_current_component() ); ?>">

			<div class="bb-rl-secondary-header flex flex-column">
				<div class="bb-rl-group-info-wrap flex items-start justify-between <?php echo esc_attr( BB_Readylaunch::bb_is_group_admin() ? 'bb-rl-no-border' : '' ); ?>">
					<div class="bb-rl-group-info flex items-center">
						<?php
						if ( BB_Readylaunch::bb_is_group_admin() ) {
							echo '<a href="' . esc_url( bp_get_group_permalink() ) . '" class="bb-rl-group-link"><i class="bb-icons-rl-arrow-left"></i><span class="bb-rl-screen-reader-text">' . esc_html__( 'Back', 'buddyboss' ) . '</span></a>';
						}

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

					<?php
					if ( ! BB_Readylaunch::bb_is_group_admin() ) {
						?>
							<div class="bb-rl-group-extra-info" >
								<?php
									add_action( 'bb_groups_members_after', 'BB_Group_Readylaunch::bb_readylaunch_invite', 10, 1 );
									bb_groups_members();
									remove_action( 'bb_groups_members_after', 'BB_Group_Readylaunch::bb_readylaunch_invite', 10, 1 );

									add_filter( 'bp_nouveau_get_groups_buttons', 'BB_Group_Readylaunch::bb_rl_group_buttons', 99, 1 );
									bb_group_single_header_actions();
									remove_filter( 'bp_nouveau_get_groups_buttons', 'BB_Group_Readylaunch::bb_rl_group_buttons', 99, 1 );
								?>

								<!-- Leave Group confirmation popup -->
								<div class="bb-leave-group-popup bb-action-popup" style="display: none">
									<transition name="modal">
										<div class="bb-rl-modal-mask bb-white bbm-model-wrap">
											<div class="bb-rl-modal-wrapper">
												<div class="modal-container">
													<header class="bb-model-header">
														<h4><span class="target_name"><?php esc_html_e( 'Leave Group', 'buddyboss' ); ?></span></h4>
														<a class="bb-close-leave-group bb-model-close-button" href="#">
															<span class="bb-icon-l bb-icon-times"></span>
														</a>
													</header>
													<div class="bb-leave-group-content bb-action-popup-content">
														<p><?php esc_html_e( 'Are you sure you want to leave ', 'buddyboss' ); ?><span class="bb-group-name"></span>?</p>
													</div>
													<footer class="bb-model-footer flex align-items-center">
														<a class="bb-close-leave-group bb-close-action-popup bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small" href="#"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
														<a class="button push-right bb-confirm-leave-group bb-rl-button bb-rl-button--brandFill bb-rl-button--small" href="#"><?php esc_html_e( 'Confirm', 'buddyboss' ); ?></a>
													</footer>

												</div>
											</div>
										</div>
									</transition>
								</div> <!-- .bb-leave-group-popup -->
							</div>
						<?php
					}
					?>

				</div>
			</div>

			<div class="bb-rl-group-section">
				<?php
				if (
					bp_group_use_cover_image_header() &&
					function_exists( 'bp_attachments_get_group_has_cover_image' ) &&
					bp_attachments_get_group_has_cover_image( bp_get_current_group_id() ) &&
					! BB_Readylaunch::bb_is_group_admin()
				) {
					bp_get_template_part( 'groups/single/cover-image-header' );
				}
				?>
				<div class="bb-rl-group-details bb-rl-details-entry">
					<div class="bb-rl-content-wrapper">

						<div class="bb-rl-primary-container">
							<?php if ( ! BB_Readylaunch::bb_is_group_admin() ) { ?>
								<div class="bb-rl-secondary-header flex flex-column">
									<?php bp_get_template_part( 'groups/single/parts/item-nav' ); ?>
								</div>
							<?php } ?>
							<div id="item-body" class="item-body">
								<?php
								if ( bp_is_group_subgroups() ) {
								echo $template_content; // phpcs:ignore
								} else {
									bp_nouveau_group_template_part();
								}
								?>
							</div><!-- #item-body -->
						</div>

						<?php if ( ! BB_Readylaunch::bb_is_group_admin() ) { ?>
							<div class="bb-rl-secondary-container">
								<?php
									bp_get_template_part( 'sidebar/right-sidebar' );
								?>
							</div>
						<?php } ?>
					</div>
				</div><!-- // .bb-rl-group-details -->
			</div>

			</div>
		<?php
		bp_nouveau_group_hook( 'after', 'home_content' );

	endwhile;
}
