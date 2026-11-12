<?php
/**
 * ReadyLaunch - Members Loop template.
 *
 * This template handles displaying the members directory listing with pagination.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bboss_nouveau_before_loop(); ?>

<?php
$footer_buttons_class = ( bboss_is_active( 'friends' ) && bboss_is_active( 'messages' ) ) ? ' footer-buttons-on' : '';

$is_follow_active = bboss_is_active( 'activity' ) && function_exists( 'bboss_is_activity_follow_active' ) && bboss_is_activity_follow_active();
$follow_class     = $is_follow_active ? ' follow-active' : '';

// Member directories elements.
$enabled_online_status = ! function_exists( 'bboss_enabled_member_directory_element' ) || bboss_enabled_member_directory_element( 'online-status' );
$enabled_profile_type  = ! function_exists( 'bboss_enabled_member_directory_element' ) || bboss_enabled_member_directory_element( 'profile-type' );
$enabled_followers     = ! function_exists( 'bboss_enabled_member_directory_element' ) || bboss_enabled_member_directory_element( 'followers' );
$enabled_last_active   = ! function_exists( 'bboss_enabled_member_directory_element' ) || bboss_enabled_member_directory_element( 'last-active' );
$enabled_joined_date   = ! function_exists( 'bboss_enabled_member_directory_element' ) || bboss_enabled_member_directory_element( 'joined-date' );
?>

<?php if ( bboss_get_current_member_type() ) : ?>
	<div class="bp-feedback info">
		<span class="bp-icon" aria-hidden="true"></span>
		<p><?php bboss_current_member_type_message(); ?></p>
	</div>
<?php endif; ?>

<?php
// Build query string with URL scope parameter support (for page requests = 1).
$members_query_string = bboss_ajax_querystring( 'members' );

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( ! empty( $_GET['bb-rl-scope'] ) ) {
	$query_args        = bboss_parse_args( $members_query_string );
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$url_scope         = sanitize_text_field( wp_unslash( $_GET['bb-rl-scope'] ) );
	$logged_in_user_id = bboss_loggedin_user_id();

	// Set scope and get user IDs for followers/following.
	$query_args['scope'] = $url_scope;

	if ( 'following' === $url_scope && function_exists( 'bboss_get_following_ids' ) ) {
		$query_args['include'] = bboss_get_following_ids( array( 'user_id' => $logged_in_user_id ) );
		unset( $query_args['user_id'] );
	} elseif ( 'followers' === $url_scope && function_exists( 'bboss_get_follower_ids' ) ) {
		$query_args['include'] = bboss_get_follower_ids( array( 'user_id' => $logged_in_user_id ) );
		unset( $query_args['user_id'] );
	}

	$members_query_string = http_build_query( $query_args );
}
?>

<?php if ( bboss_has_members( $members_query_string ) ) : ?>

	<ul id="members-list" class="<?php bboss_nouveau_loop_classes(); ?>">

		<?php
		while ( bboss_members() ) :
			bboss_the_member();

			$bp_get_member_user_id   = bboss_get_member_user_id();
			$bp_get_member_permalink = bboss_get_member_permalink();

			// Check if members_list_item has content.
			ob_start();
			bboss_nouveau_member_hook( '', 'members_list_item' );
			$members_list_item_content = ob_get_clean();
			$member_loop_has_content   = ! empty( $members_list_item_content );

			// Get member followers element.
			$followers_count = '';
			if ( $enabled_followers && function_exists( 'bboss_get_followers_count' ) ) {
				ob_start();
				bboss_get_followers_count( $bp_get_member_user_id );
				$followers_count = ob_get_clean();
			}

			// Member joined data.
			add_filter( 'bb_get_member_joined_date', 'BBoss_Readylaunch::bb_rl_modify_member_joined_date', 10, 2 );
			$member_joined_date = bboss_get_member_joined_date( $bp_get_member_user_id );
			remove_filter( 'bb_get_member_joined_date', 'BBoss_Readylaunch::bb_rl_modify_member_joined_date', 10, 2 );

			// Member last activity.
			$member_last_activity = bboss_get_last_activity( $bp_get_member_user_id );

			// Primary and secondary profile action buttons.
			$profile_actions = bboss_member_directories_get_profile_actions( $bp_get_member_user_id );

			// Member switch button.
			$member_switch_button = bboss_get_add_switch_button( $bp_get_member_user_id );

			$member_block_button  = '';
			$member_report_button = '';

			if ( bboss_is_active( 'moderation' ) && is_user_logged_in() ) {
				// Member report button.
				$report_button = bboss_member_get_report_link(
					array(
						'button_element' => 'a',
						'position'       => 30,
						'report_user'    => true,
						'parent_attr'    => array(
							'id'    => 'user-report-' . $bp_get_member_user_id,
							'class' => '',
						),
						'button_attr'    => array(
							'data-bp-content-id'   => $bp_get_member_user_id,
							'data-bp-content-type' => BBoss_Moderation_Members::$moderation_type_report,
							'data-reported_type'   => bboss_moderation_get_report_type( BBoss_Moderation_Members::$moderation_type_report, $bp_get_member_user_id ),
						),

					)
				);
				$member_report_button = ! is_super_admin( $bp_get_member_user_id ) ? bboss_get_button( $report_button ) : '';

				// Member block button.
				$block_button = bboss_member_get_report_link(
					array(
						'button_element' => 'a',
						'position'       => 30,
						'parent_attr'    => array(
							'id'    => 'user-block-' . $bp_get_member_user_id,
							'class' => '',
						),
						'button_attr'    => array(
							'data-bp-content-id'   => $bp_get_member_user_id,
							'data-bp-content-type' => BBoss_Moderation_Members::$moderation_type,
							'data-reported_type'   => bboss_moderation_get_report_type( BBoss_Moderation_Members::$moderation_type, $bp_get_member_user_id ),
						),

					)
				);
				$member_block_button = ! is_super_admin( $bp_get_member_user_id ) ? bboss_get_button( $block_button ) : '';
			}

			// Get Primary action.
			$primary_action_btn = function_exists( 'bboss_get_member_directory_primary_action' ) ? bboss_get_member_directory_primary_action() : '';
			?>
			<li <?php bboss_member_class( array( 'item-entry' ) ); ?> data-bp-item-id="<?php bboss_member_user_id(); ?>" data-bp-item-component="members">
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
							$moderation_class = function_exists( 'bboss_moderation_is_user_suspended' ) && bboss_moderation_is_user_suspended( $bp_get_member_user_id ) ? 'bp-user-suspended' : '';
							$moderation_class = function_exists( 'bboss_moderation_is_user_blocked' ) && bboss_moderation_is_user_blocked( $bp_get_member_user_id ) ? $moderation_class . ' bp-user-blocked' : $moderation_class;
							?>
							<a href="<?php echo esc_url( $bp_get_member_permalink ); ?>" class="<?php echo esc_attr( $moderation_class ); ?>">
								<?php
								if ( $enabled_online_status ) {
									bboss_user_presence_html( $bp_get_member_user_id );
								}
								bboss_member_avatar( bboss_nouveau_avatar_args() );
								?>
							</a>
						</div>

						<div class="item">

							<div class="item-block">

								<div class="bb-rl-item-block-heading">
									<?php
									$user_member_type = '';
									if ( $enabled_profile_type && function_exists( 'bboss_member_type_enable_disable' ) && true === bboss_member_type_enable_disable() && true === bboss_member_type_display_on_profile() ) {
										$user_member_type = bboss_get_user_member_type( $bp_get_member_user_id );
									}

									if ( ! empty( $user_member_type ) ) {
										echo '<p class="item-meta member-type only-grid-view">' . wp_kses_post( $user_member_type ) . '</p>';
									}
									?>

									<h2 class="list-title member-name">
										<a href="<?php echo esc_url( $bp_get_member_permalink ); ?>"><?php bboss_member_name(); ?></a>
									</h2>

									<?php
									if ( ! empty( $user_member_type ) ) {
										echo '<p class="item-meta member-type only-list-view">' . wp_kses_post( $user_member_type ) . '</p>';
									}
									?>
								</div>

								<?php
									bboss_nouveau_member_hook( '', 'members_list_meta' );
								?>

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
							<?php bboss_nouveau_member_hook( '', 'members_list_item' ); ?>
						</div>
					</div>

					<?php if ( ! empty( $member_switch_button ) || ! empty( $member_report_button ) || ! empty( $member_block_button ) ) { ?>
					<div class="bb_more_options member-dropdown bb-rl-context-wrap">
						<a href="#" class="bb-rl-context-btn bb_more_options_action bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'More Options', 'buddyboss' ); ?>" aria-label="<?php esc_html_e( 'More Options', 'buddyboss' ); ?>">
							<i class="bb-icons-rl-dots-three"></i>
							<span class="bp-screen-reader-text"><?php esc_html_e( 'More options', 'buddyboss' ); ?></span>
						</a>
						<div class="bb_more_options_list bb_more_dropdown bb-rl-context-dropdown">
							<?php bboss_get_template_part( 'common/more-options-view' ); ?>
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
	bboss_nouveau_pagination( 'bottom' );
else :
	bboss_nouveau_user_feedback( 'members-loop-none' );
endif;

bboss_nouveau_after_loop();
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
							<?php if ( bboss_is_active( 'activity' ) ) : ?>
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
							<?php if ( bboss_is_active( 'groups' ) ) : ?>
							<li>
								<?php
								esc_html_e( 'Invite this member to groups', 'buddyboss' );
								?>
							</li>
							<?php endif; ?>
							<?php if ( bboss_is_active( 'messages' ) ) : ?>
							<li>
								<?php
								esc_html_e( 'Message this member', 'buddyboss' );
								?>
							</li>
							<?php endif; ?>
							<?php if ( bboss_is_active( 'friends' ) ) : ?>
							<li>
								<?php
								esc_html_e( 'Add this member as a connection', 'buddyboss' );
								?>
							</li>
							<?php endif; ?>
						</ul>

						<div class="notice notice--plain notice--warning">
							<?php if ( bboss_is_active( 'friends' ) ) : ?>
								<?php
								printf(
									/* translators: 1: "Please note:" label, 2: notice message text. */
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
