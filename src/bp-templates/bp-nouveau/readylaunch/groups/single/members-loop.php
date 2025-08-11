<?php
/**
 * ReadyLaunch - Group Members Loop template.
 *
 * This template displays the list of group members with actions,
 * member types, last activity, and various interaction buttons.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$footer_buttons_class = ( bp_is_active( 'friends' ) && bp_is_active( 'messages' ) ) ? 'footer-buttons-on' : '';

$is_follow_active = bp_is_active( 'activity' ) && function_exists( 'bp_is_activity_follow_active' ) && bp_is_activity_follow_active();
$follow_class     = $is_follow_active ? 'follow-active' : '';

// Member directories elements.
$enabled_online_status = ! function_exists( 'bb_enabled_member_directory_element' ) || bb_enabled_member_directory_element( 'online-status' );
$enabled_profile_type  = ! function_exists( 'bb_enabled_member_directory_element' ) || bb_enabled_member_directory_element( 'profile-type' );
$enabled_followers     = ! function_exists( 'bb_enabled_member_directory_element' ) || bb_enabled_member_directory_element( 'followers' );
$enabled_last_active   = ! function_exists( 'bb_enabled_member_directory_element' ) || bb_enabled_member_directory_element( 'last-active' );
$enabled_joined_date   = ! function_exists( 'bb_enabled_member_directory_element' ) || bb_enabled_member_directory_element( 'joined-date' );

if ( bp_group_has_members( bp_ajax_querystring( 'group_members' ) . '&type=group_role' ) ) {
	add_filter( 'bp_organizer_plural_label_name', 'BB_Group_Readylaunch::bb_rl_add_count_after_label', 10, 3 );
	add_filter( 'bp_moderator_plural_label_name', 'BB_Group_Readylaunch::bb_rl_add_count_after_label', 10, 3 );
	add_filter( 'bp_member_plural_label_name', 'BB_Group_Readylaunch::bb_rl_add_count_after_label', 10, 3 );	
	?>

	<ul id="members-list" class="<?php bp_nouveau_loop_classes(); ?> members-list">
		<?php
		while ( bp_group_members() ) :
			bp_group_the_member();

			bp_group_member_section_title();

			// Check if members_list_item has content.
			ob_start();
			bp_nouveau_member_hook( '', 'members_list_item' );
			$members_list_item_content = ob_get_clean();
			$member_loop_has_content   = ! empty( $members_list_item_content );

			$member_user_id  = bp_get_member_user_id();
			$group_member_id = bp_get_group_member_id();

			// Get member followers element.
			$followers_count = '';
			if ( $enabled_followers && function_exists( 'bb_get_followers_count' ) ) {
				ob_start();
				bb_get_followers_count( $member_user_id );
				$followers_count = ob_get_clean();
			}

			// Member joined data.
			if ( class_exists( 'BB_Group_Readylaunch' ) ) {
				add_filter( 'bp_core_get_last_activity', 'BB_Group_Readylaunch::bb_rl_modify_group_member_joined_since', 10, 2 );
			}
			$member_joined_date = bp_get_group_member_joined_since();
			if ( class_exists( 'BB_Group_Readylaunch' ) ) {
				remove_filter( 'bp_core_get_last_activity', 'BB_Group_Readylaunch::bb_rl_modify_group_member_joined_since', 10, 2 );
			}

			// Member last activity.
			$member_last_activity = bp_get_last_activity( $member_user_id );

			// Primary and secondary profile action buttons.
			$profile_actions = bb_member_directories_get_profile_actions( $member_user_id );

			// Member switch button.
			$member_switch_button = bp_get_add_switch_button( $member_user_id );

			// Get Primary action.
			$primary_action_btn = function_exists( 'bb_get_member_directory_primary_action' ) ? bb_get_member_directory_primary_action() : '';
			$is_blocked         = false;
			$moderation_class   = '';
			if ( bp_is_active( 'moderation' ) ) {
				if ( bp_moderation_is_user_suspended( $member_user_id ) ) {
					$moderation_class .= 'bp-user-suspended';
				} elseif ( bb_moderation_is_user_blocked_by( $member_user_id ) ) {
					$is_blocked        = true;
					$moderation_class .= ' bp-user-blocked';
				}
			}

			$member_block_button  = '';
			$member_report_button = '';

			if ( bp_is_active( 'moderation' ) && is_user_logged_in() ) {
				// Member report button.
				$report_button = bp_member_get_report_link(
					array(
						'button_element' => 'a',
						'position'       => 30,
						'report_user'    => true,
						'parent_attr'    => array(
							'id'    => 'user-report-' . $member_user_id,
							'class' => '',
						),
						'button_attr'    => array(
							'data-bp-content-id'   => $member_user_id,
							'data-bp-content-type' => BP_Moderation_Members::$moderation_type_report,
							'data-reported_type'   => bp_moderation_get_report_type( BP_Moderation_Members::$moderation_type_report, $member_user_id ),
						),

					)
				);
				$member_report_button = ! is_super_admin( $member_user_id ) ? bp_get_button( $report_button ) : '';

				// Member block button.
				$block_button = bp_member_get_report_link(
					array(
						'button_element' => 'a',
						'position'       => 30,
						'parent_attr'    => array(
							'id'    => 'user-block-' . $member_user_id,
							'class' => '',
						),
						'button_attr'    => array(
							'data-bp-content-id'   => $member_user_id,
							'data-bp-content-type' => BP_Moderation_Members::$moderation_type,
							'data-reported_type'   => bp_moderation_get_report_type( BP_Moderation_Members::$moderation_type, $member_user_id ),
						),

					)
				);
				$member_block_button = ! is_super_admin( $member_user_id ) ? bp_get_button( $block_button ) : '';
			}

			$bp_get_member_permalink = bp_get_group_member_domain();
			?>
			<li <?php bp_member_class( array( 'item-entry' ) ); ?> data-bp-item-id="<?php bp_member_user_id(); ?>" data-bp-item-component="members">
				<div class="list-wrap
				<?php
					echo esc_attr( $footer_buttons_class ) .
						esc_attr( $follow_class ) .
						esc_attr( true === $member_loop_has_content ? ' has_hook_content' : '' ) .
						esc_attr( ! empty( $profile_actions['secondary'] ) ? ' secondary-buttons' : ' no-secondary-buttons' ) .
						esc_attr( ! empty( $primary_action_btn ) ? ' primary-button' : ' no-primary-buttons' );
				?>
				">

					<div class="list-wrap-inner">
						<div class="item-avatar">
							<?php
								$moderation_class = function_exists( 'bp_moderation_is_user_suspended' ) && bp_moderation_is_user_suspended( $member_user_id ) ? 'bp-user-suspended' : '';
								$moderation_class = function_exists( 'bp_moderation_is_user_blocked' ) && bp_moderation_is_user_blocked( $member_user_id ) ? $moderation_class . ' bp-user-blocked' : $moderation_class;
							?>
							<a href="<?php echo esc_url( $bp_get_member_permalink ); ?>" class="<?php echo esc_attr( $moderation_class ); ?>">
								<?php
								if ( $enabled_online_status ) {
									bb_user_presence_html( $member_user_id );
								}
									bp_member_avatar( bp_nouveau_avatar_args() );
								?>
							</a>
						</div>

						<div class="item">

							<div class="item-block">

								<div class="bb-rl-item-block-heading">
									<?php
										$user_member_type = '';
									if ( $enabled_profile_type && function_exists( 'bp_member_type_enable_disable' ) && true === bp_member_type_enable_disable() && true === bp_member_type_display_on_profile() ) {
										$user_member_type = bp_get_user_member_type( $member_user_id );
									}

									if ( ! empty( $user_member_type ) ) {
										echo '<p class="item-meta member-type only-grid-view">' . wp_kses_post( $user_member_type ) . '</p>';
									}
									?>

									<h2 class="list-title member-name">
										<a href="<?php echo esc_url( $bp_get_member_permalink ); ?>"><?php bp_member_name(); ?></a>
									</h2>

									<?php
									if ( ! empty( $user_member_type ) ) {
										echo '<p class="item-meta member-type only-list-view">' . wp_kses_post( $user_member_type ) . '</p>';
									}
									?>
								</div>

								<div class="bb-rl-item-block-assets">
									<?php
									if ( ( $enabled_last_active && $member_last_activity ) || ( $enabled_joined_date && $member_joined_date ) ) :

										echo '<p class="item-meta bb-rl-item-meta-asset">';
										if ( $enabled_joined_date ) {
											echo wp_kses_post( $member_joined_date );
										}

										if ( ( $enabled_last_active && $member_last_activity ) && ( $enabled_joined_date && $member_joined_date ) ) {
											echo '<span class="separator">&bull;</span>';
										}

										echo '<span class="only-grid-view">';
										if ( $enabled_last_active ) {
											echo wp_kses_post( $member_last_activity );
										}
										echo '</span>';
										echo '</p>';
										endif;
									?>

									<div class="flex align-items-center follow-container justify-center bb-rl-item-meta-asset">
										<?php echo wp_kses_post( $followers_count ); ?>
									</div>

									<div class="only-list-view bb-rl-last-activity bb-rl-item-meta-asset">
										<?php
										if ( $enabled_last_active ) {
											echo wp_kses_post( $member_last_activity );
										}
										?>
									</div>
								</div>
							</div>
						</div><!-- // .item -->

						<div class="bb-rl-member-buttons-wrap">

							<div class="bb-rl-item-actions flex items-center <?php echo empty( $profile_actions['primary'] ) ? 'bb-rl-idle-primary' : ''; ?>">
								<div class="bb-rl-secondary-actions flex items-center">
									<?php if ( ! empty( $profile_actions['secondary'] ) ) { ?>
										<div class="flex button-wrap member-button-wrap footer-button-wrap">
											<?php echo wp_kses_post( $profile_actions['secondary'] ); ?>
										</div>
									<?php } ?>
								</div>
								<div class="bb-rl-primary-actions">
									<?php if ( ! empty( $profile_actions['primary'] ) ) { ?>
										<div class="flex align-items-center primary-action justify-center">
											<?php echo wp_kses_post( $profile_actions['primary'] ); ?>
										</div>
									<?php } ?>
								</div>
							</div>

						</div><!-- .member-buttons-wrap -->

					</div>

					<div class="bp-members-list-hook">
						<?php if ( $member_loop_has_content ) { ?>
							<a class="more-action-button" href="#" aria-label="<?php esc_attr_e( 'More options', 'buddyboss' ); ?>"><i class="bb-icon-menu-dots-h"></i></a>
						<?php } ?>
						<div class="bp-members-list-hook-inner">
							<?php bp_nouveau_member_hook( '', 'members_list_item' ); ?>
						</div>
					</div>

					<?php if ( ! empty( $member_switch_button ) || ! empty( $member_report_button ) || ! empty( $member_block_button ) ) { ?>
						<div class="bb_more_options member-dropdown bb-rl-context-wrap">
							<a href="#" class="bb-rl-context-btn bb_more_options_action bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'More Options', 'buddyboss' ); ?>" aria-label="<?php esc_attr_e( 'More Options', 'buddyboss' ); ?>">
								<i class="bb-icons-rl-dots-three"></i>
								<span class="bp-screen-reader-text"><?php esc_html_e( 'More options', 'buddyboss' ); ?></span>
							</a>
							<div class="bb_more_options_list bb_more_dropdown bb-rl-context-dropdown">
								<?php bp_get_template_part( 'common/more-options-view' ); ?>
								<?php
									echo wp_kses_post( $member_switch_button );
									echo wp_kses_post( $member_report_button );
									echo wp_kses_post( $member_block_button );
								?>
							</div>
							<div class="bb_more_dropdown_overlay"></div>
						</div><!-- .bb_more_options -->
					<?php } ?>
				</div>
			</li>

		<?php endwhile; ?>
	</ul>

	<?php
	remove_filter( 'bp_organizer_plural_label_name', 'BB_Group_Readylaunch::bb_rl_add_count_after_label', 10 );
	remove_filter( 'bp_moderator_plural_label_name', 'BB_Group_Readylaunch::bb_rl_add_count_after_label', 10 );
	remove_filter( 'bp_member_plural_label_name', 'BB_Group_Readylaunch::bb_rl_add_count_after_label', 10 );
	bp_nouveau_pagination( 'bottom' );
} else {
	bp_nouveau_user_feedback( 'group-members-none' );
}
?>


<!-- Remove Connection confirmation popup -->
<div class="bb-remove-connection bb-action-popup bb-rl-modal" style="display: none;">
	<transition name="modal">
		<div class="bb-rl-modal-mask bb-white bbm-model-wrap">
			<div class="bb-rl-modal-wrapper">
				<div class="bb-rl-modal-container">
					<header class="bb-rl-modal-header">
						<h4><span class="target_name"><?php echo esc_html__( 'Remove Connection', 'buddyboss' ); ?></span></h4>
						<a class="bb-close-remove-connection bb-rl-modal-close-button" href="#">
							<span class="bb-icons-rl-x"></span>
						</a>
					</header>
					<div class="bb-remove-connection-content bb-action-popup-content bb-rl-modal-content">
						<p>
							<?php
								printf(
								/* translators: %s: The member name with HTML tags */
									esc_html__( 'Are you sure you want to remove %s from your connections?', 'buddyboss' ),
									'<span class="bb-user-name"></span>'
								);
								?>
						</p>
					</div>
					<footer class="bb-rl-modal-footer flex items-center">
						<a class="bb-close-remove-connection bb-close-action-popup bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small" href="#"><?php echo esc_html__( 'Cancel', 'buddyboss' ); ?></a>
						<a class="button push-right bb-confirm-remove-connection bb-rl-button bb-rl-button--brandFill bb-rl-button--small" href="#"><?php echo esc_html__( 'Confirm', 'buddyboss' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div> <!-- .bb-remove-connection -->

<!-- Block member popup -->
<div id="bb-rl-block-member" class="bb-rl-block-modal bb-rl-modal" style="display: none;">
	<transition name="modal">
		<div class="bb-rl-modal-mask">
			<div class="bb-rl-modal-wrapper">
				<div class="bb-rl-modal-container">
					<header class="bb-rl-modal-header">
						<h4><span class="target_name"><?php echo esc_html__( 'Block Member?', 'buddyboss' ); ?></span></h4>
						<a class="bb-rl-close-block-member bb-rl-modal-close-button" href="#">
							<span class="bb-icons-rl-x"></span>
						</a>
					</header>
					<div class="bb-rl-block-member-content bb-rl-modal-content">
						<p>
							<?php esc_html_e( 'Please confirm you want to block this member.', 'buddyboss' ); ?>
						</p>
						<div>
							<?php esc_html_e( 'You will no longer be able to:', 'buddyboss' ); ?>
						</div>
						<ul>
							<?php if ( bp_is_active( 'activity' ) ) : ?>
								<li>
									<?php
										esc_html_e( 'See blocked member\'s posts', 'buddyboss' );
									?>
								</li>
							<?php endif; ?>
							<li>
								<?php
									esc_html_e( 'Mention this member in posts', 'buddyboss' );
								?>
							</li>
							<?php if ( bp_is_active( 'groups' ) ) : ?>
								<li>
									<?php
										esc_html_e( 'Invite this member to groups', 'buddyboss' );
									?>
								</li>
							<?php endif; ?>
							<?php if ( bp_is_active( 'messages' ) ) : ?>
								<li>
									<?php
										esc_html_e( 'Message this member', 'buddyboss' );
									?>
								</li>
							<?php endif; ?>
							<?php if ( bp_is_active( 'friends' ) ) : ?>
								<li>
									<?php
										esc_html_e( 'Add this member as a connection', 'buddyboss' );
									?>
								</li>
							<?php endif; ?>
						</ul>

						<div class="notice notice--plain notice--warning">
							<?php if ( bp_is_active( 'friends' ) ) : ?>
								<?php
								printf(
									wp_kses( __( '<span>%1$s</span> %2$s', 'buddyboss' ), array( 'span' => array() ) ),
									esc_html__( 'Please note:', 'buddyboss' ),
									esc_html__( 'This action will also remove this member from your connections and send a report to the site admin.', 'buddyboss' )
								);
								?>
							<?php endif; ?>

							<?php esc_html_e( 'Please allow a few minutes for this process to complete.', 'buddyboss' ); ?>
						</div>
					</div>
					<footer class="bb-rl-modal-footer flex items-center">
						<a class="bb-rl-close-block-member bb-rl-close-modal bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small" href="#"><?php echo esc_html__( 'Cancel', 'buddyboss' ); ?></a>
						<input type="submit" name="block-member-submit" id="bb-rl-submit-block-member" form="bb-rl-block-member-form" value="Confirm" class="bb-rl-button bb-rl-button--brandFill bb-rl-button--small">
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div>
