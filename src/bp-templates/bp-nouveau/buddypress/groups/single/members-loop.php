<?php
/**
 * Group Members Loop template
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/members-loop.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

$footer_buttons_class = ( bp_is_active( 'friends' ) && bp_is_active( 'messages' ) ) ? 'footer-buttons-on' : '';

$is_follow_active = bp_is_active( 'activity' ) && function_exists( 'bp_is_activity_follow_active' ) && bp_is_activity_follow_active();
$follow_class     = $is_follow_active ? 'follow-active' : '';

// Member directories elements.
$enabled_online_status = ! function_exists( 'bb_enabled_member_directory_element' ) || bb_enabled_member_directory_element( 'online-status' );
$enabled_profile_type  = ! function_exists( 'bb_enabled_member_directory_element' ) || bb_enabled_member_directory_element( 'profile-type' );
$enabled_followers     = ! function_exists( 'bb_enabled_member_directory_element' ) || bb_enabled_member_directory_element( 'followers' );
$enabled_last_active   = ! function_exists( 'bb_enabled_member_directory_element' ) || bb_enabled_member_directory_element( 'last-active' );
$enabled_joined_date   = ! function_exists( 'bb_enabled_member_directory_element' ) || bb_enabled_member_directory_element( 'joined-date' );
?>

<?php if ( bp_group_has_members( bp_ajax_querystring( 'group_members' ) . '&type=group_role' ) ) : ?>

	<?php bp_nouveau_group_hook( 'before', 'members_content' ); ?>

	<?php bp_nouveau_pagination( 'top' ); ?>

	<?php bp_nouveau_group_hook( 'before', 'members_list' ); ?>

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

			// Get member followers element.
			$followers_count = '';
			if ( $enabled_followers && function_exists( 'bb_get_followers_count' ) ) {
				ob_start();
				bb_get_followers_count( bp_get_member_user_id() );
				$followers_count = ob_get_clean();
			}

			// Member joined data.
			$member_joined_date = bp_get_group_member_joined_since();

			// Member last activity.
			$member_last_activity = bp_get_last_activity( bp_get_member_user_id() );

			// Primary and secondary profile action buttons.
			$profile_actions = bb_member_directories_get_profile_actions( bp_get_member_user_id() );

			// Member switch button.
			$member_switch_button = bp_get_add_switch_button( bp_get_member_user_id() );

			// Get Primary action.
			$primary_action_btn = function_exists( 'bb_get_member_directory_primary_action' ) ? bb_get_member_directory_primary_action() : '';
			$is_blocked         = false;
			$moderation_class   = '';
			if ( bp_is_active( 'moderation' ) ) {
				if ( bp_moderation_is_user_suspended( bp_get_member_user_id() ) ) {
					$moderation_class .= 'bp-user-suspended';
				} elseif ( bb_moderation_is_user_blocked_by( bp_get_member_user_id() ) ) {
					$is_blocked       = true;
					$moderation_class .= ' bp-user-blocked';
				}
			}
			?>
			<li <?php bp_member_class( array( 'item-entry' ) ); ?> data-bp-item-id="<?php echo esc_attr( bp_get_group_member_id() ); ?>" data-bp-item-component="members">
				<div class="list-wrap <?php echo esc_attr( $footer_buttons_class ); ?> <?php echo esc_attr( $follow_class ); ?> <?php echo $member_loop_has_content ? esc_attr( ' has_hook_content' ) : esc_attr( '' ); ?> <?php echo ! empty( $profile_actions['secondary'] ) ? esc_attr( 'secondary-buttons' ) : esc_attr( 'no-secondary-buttons' ); ?> <?php echo ! empty( $primary_action_btn ) ? esc_attr( 'primary-button' ) : esc_attr( 'no-primary-buttons' ); ?>">

					<div class="list-wrap-inner">
						<div class="item-avatar">
							<a href="<?php bp_group_member_domain(); ?>" class="<?php echo esc_attr( $moderation_class ); ?>">
								<?php
								if ( $enabled_online_status ) {
									bb_user_presence_html( bp_get_group_member_id() );
								}
								bp_group_member_avatar();
								?>
							</a>
						</div>

						<div class="item">

							<div class="item-block">

								<?php
								if ( $enabled_profile_type && function_exists( 'bp_member_type_enable_disable' ) && true === bp_member_type_enable_disable() && true === bp_member_type_display_on_profile() ) {
									echo '<p class="item-meta member-type only-grid-view">' . wp_kses_post( bp_get_user_member_type( bp_get_member_user_id() ) ) . '</p>';
								}
								?>

								<h2 class="list-title member-name">
									<?php bp_group_member_link(); ?>
								</h2>

								<?php
								if ( $enabled_profile_type && function_exists( 'bp_member_type_enable_disable' ) && true === bp_member_type_enable_disable() && true === bp_member_type_display_on_profile() ) {
									echo '<p class="item-meta member-type only-list-view">' . wp_kses_post( bp_get_user_member_type( bp_get_member_user_id() ) ) . '</p>';
								}
								?>

								<?php
								if (
									! $is_blocked &&
									(
										(
											$enabled_last_active &&
											$member_last_activity
										) ||
										(
											$enabled_joined_date &&
											$member_joined_date
										)
									)
								) :
									?>
									<p class="item-meta last-activity">

										<?php
										if ( $enabled_joined_date ) :
											echo wp_kses_post( $member_joined_date );
										endif;
										?>

										<?php if ( ( $enabled_last_active && $member_last_activity ) && ( $enabled_joined_date && $member_joined_date ) ) : ?>
											<span class="separator">&bull;</span>
										<?php endif; ?>

										<?php
										if ( $enabled_last_active ) :
											echo wp_kses_post( $member_last_activity );
										endif;
										?>

									</p>
								<?php
								endif;
								?>
							</div>

							<?php
							if ( ! $is_blocked ) {
								?>
								<div class="flex align-items-center follow-container justify-center">
									<?php echo wp_kses_post( $followers_count ); ?>
								</div>
								<?php
							}
							?>

							<div class="flex only-grid-view align-items-center primary-action justify-center">
								<?php echo wp_kses_post( $profile_actions['primary'] ); ?>
							</div>
						</div><!-- // .item -->

						<div class="member-buttons-wrap">

							<?php if ( $profile_actions['secondary'] ) { ?>
								<div class="flex only-grid-view button-wrap member-button-wrap footer-button-wrap">
									<?php echo wp_kses_post( $profile_actions['secondary'] ); ?>
								</div>
							<?php } ?>

							<?php if ( $profile_actions['primary'] ) { ?>
								<div class="flex only-list-view align-items-center primary-action justify-center">
									<?php echo wp_kses_post( $profile_actions['primary'] ); ?>
								</div>
							<?php } ?>

						</div><!-- .member-buttons-wrap -->

					</div>

					<div class="bp-members-list-hook">
						<?php if ( $member_loop_has_content ) { ?>
							<a class="more-action-button" href="#"><i class="bb-icon-menu-dots-h"></i></a>
						<?php } ?>
						<div class="bp-members-list-hook-inner">
							<?php bp_nouveau_member_hook( '', 'members_list_item' ); ?>
						</div>
					</div>

					<?php if ( ! empty( $member_switch_button ) ) { ?>
					<div class="bb_more_options member-dropdown">
						<a href="#" class="bb_more_options_action">
							<i class="bb-icon-menu-dots-h"></i>
						</a>
						<div class="bb_more_options_list">
							<?php echo wp_kses_post( bp_get_add_switch_button( bp_get_member_user_id() ) ); ?>
						</div>
					</div><!-- .bb_more_options -->
					<?php } ?>
				</div>
			</li>

		<?php endwhile; ?>

	</ul>

	<?php bp_nouveau_group_hook( 'after', 'members_list' ); ?>

	<?php bp_nouveau_pagination( 'bottom' ); ?>

	<?php bp_nouveau_group_hook( 'after', 'members_content' ); ?>

	<?php
else :

	bp_nouveau_user_feedback( 'group-members-none' );

endif;
?>

<!-- Remove Connection confirmation popup -->
<div class="bb-remove-connection bb-action-popup" style="display: none">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap">
			<div class="modal-wrapper">
				<div class="modal-container">
					<header class="bb-model-header">
						<h4><span class="target_name"><?php echo esc_html__( 'Remove Connection', 'buddyboss' ); ?></span></h4>
						<a class="bb-close-remove-connection bb-model-close-button" href="#">
							<span class="bb-icon-l bb-icon-times"></span>
						</a>
					</header>
					<div class="bb-remove-connection-content bb-action-popup-content">
						<p>
							<?php
							echo sprintf(
								/* translators: %s: The member name with HTML tags */
								esc_html__( 'Are you sure you want to remove %s from your connections?', 'buddyboss' ),
								'<span class="bb-user-name"></span>'
							);
							?>
						</p>
					</div>
					<footer class="bb-model-footer flex align-items-center">
						<a class="bb-close-remove-connection bb-close-action-popup" href="#"><?php echo esc_html__( 'Cancel', 'buddyboss' ); ?></a>
						<a class="button push-right bb-confirm-remove-connection" href="#"><?php echo esc_html__( 'Confirm', 'buddyboss' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div> <!-- .bb-remove-connection -->
