<?php
/**
 * ReadyLaunch - Member Header template.
 *
 * This template handles displaying the member profile header with actions and metadata.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

remove_filter( 'bp_get_add_follow_button', 'bboss_bp_get_add_follow_button' );

if ( ! bboss_is_user_messages() && ! bboss_is_user_settings() && ! bboss_is_user_notifications() && ! bboss_is_user_profile_edit() && ! bboss_is_user_change_avatar() && ! bboss_is_user_change_cover_image() ) :

	$profile_header_layout_style = bboss_get_profile_header_layout_style();
	$is_enabled_online_status    = bboss_enabled_profile_header_layout_element( 'online-status' );
	$is_enabled_profile_type     = bboss_enabled_profile_header_layout_element( 'profile-type' );
	$is_enabled_member_handle    = bboss_enabled_profile_header_layout_element( 'member-handle' );
	$is_enabled_joined_date      = bboss_enabled_profile_header_layout_element( 'joined-date' );
	$is_enabled_last_active      = bboss_enabled_profile_header_layout_element( 'last-active' );
	$is_enabled_followers        = bboss_enabled_profile_header_layout_element( 'followers' );
	$is_enabled_following        = bboss_enabled_profile_header_layout_element( 'following' );
	$is_enabled_social_networks  = bboss_enabled_profile_header_layout_element( 'social-networks' ) && function_exists( 'bboss_enabled_member_social_networks' ) && bboss_enabled_member_social_networks();

	$my_profile                     = '';
	$user_social_networks_urls      = '';
	$social_networks_urls_div_class = 'social-networks-hide';
	if ( $is_enabled_social_networks ) {

		add_filter( 'bp_get_user_social_networks_urls', 'bboss_get_user_social_networks_urls_with_visibility', 10, 3 );
		$user_social_networks_urls = bboss_get_user_social_networks_urls();
		remove_filter( 'bp_get_user_social_networks_urls', 'bboss_get_user_social_networks_urls_with_visibility', 10, 3 );

		if ( ! empty( $user_social_networks_urls ) ) {
			$social_networks_urls_div_class = 'network_profiles';
		}
	}

	$bp_is_my_profile          = bboss_is_my_profile();
	$bp_displayed_user_id      = bboss_displayed_user_id();
	$is_activity_enabled       = bboss_is_active( 'activity' );
	$bp_activity_do_mentions   = $is_activity_enabled && bboss_activity_do_mentions();
	$bp_get_last_activity      = bboss_get_last_activity();
	$bb_get_member_joined_date = bboss_get_member_joined_date();

	$member_type = '';
	if ( true === bboss_member_type_enable_disable() && true === bboss_member_type_display_on_profile() && $is_enabled_profile_type ) {
		$member_type = bboss_get_user_member_type( $bp_displayed_user_id );
	}

	if ( $bp_is_my_profile ) {
		$my_profile = 'my_profile';
	}

	$bp_nouveau = bboss_nouveau();
	?>

	<div id="bb-rl-profile-container" class="<?php echo esc_attr( $profile_header_layout_style . ' ' . $social_networks_urls_div_class . ' ' . $my_profile ); ?> bb-rl-profile-container">

		<div id="bb-rl-profile-item-header" class="bb-rl-profile-item-header item-header-wrap flex">
			<?php
			$moderation_class = function_exists( 'bboss_moderation_is_user_suspended' ) && bboss_moderation_is_user_suspended( $bp_displayed_user_id ) ? 'bp-user-suspended' : '';
			$moderation_class = function_exists( 'bboss_moderation_is_user_blocked' ) && bboss_moderation_is_user_blocked( $bp_displayed_user_id ) ? $moderation_class . ' bp-user-blocked' : $moderation_class;
			?>
			<div id="bb-rl-item-header-avatar" class="<?php echo esc_attr( $moderation_class ); ?> bb-rl-profile-avatar">
				<?php
				if ( $is_enabled_online_status ) {
					bboss_user_presence_html( $bp_displayed_user_id );
				}

				bboss_displayed_user_avatar( 'type=full' );
				?>
			</div><!-- #item-header-avatar -->

			<div id="bb-rl-item-header-content" class="bb-rl-profile-header-content">
				<div class="bb-user-content-wrap">
					<div class="flex flex-column member-title-wrap">
						<?php
						if ( ! empty( $member_type ) ) {
							echo wp_kses_post( $member_type );
						}
						?>
						<h2 class="user-nicename"><?php echo wp_kses_post( bboss_core_get_user_displayname( $bp_displayed_user_id ) ); ?></h2>
					</div>

					<?php
					bboss_nouveau_member_hook( 'before', 'header_meta' );
					if ( ( $is_activity_enabled && $bp_activity_do_mentions ) || $bp_get_last_activity || $bb_get_member_joined_date ) :
						?>
						<div class="item-meta">
							<?php
							$nickname_field_id = bboss_xprofile_nickname_field_id();
							$hidden_fields     = bboss_xprofile_get_hidden_fields_for_user();
							if ( $is_activity_enabled && $bp_activity_do_mentions && ! in_array( $nickname_field_id, $hidden_fields, true ) && $is_enabled_member_handle ) :
								?>
								<span class="mention-name">@<?php bboss_displayed_user_mentionname(); ?></span>
								<?php
							endif;
							if ( $is_activity_enabled && $bp_activity_do_mentions && $is_enabled_member_handle && $is_enabled_joined_date ) :
								?>
								<span class="separator">&bull;</span>
								<?php
							endif;
							if ( $bb_get_member_joined_date && $is_enabled_joined_date ) :
								echo wp_kses_post( $bb_get_member_joined_date );
							endif;
							if ( ( ( $is_activity_enabled && $bp_activity_do_mentions ) || $bb_get_member_joined_date ) && $bp_get_last_activity && $is_enabled_last_active && ( $is_enabled_member_handle || $is_enabled_joined_date ) ) :
								?>
								<span class="separator">&bull;</span>
								<?php
							endif;
							bboss_nouveau_member_hook( 'before', 'in_header_meta' );
							if ( $bp_get_last_activity && $is_enabled_last_active ) :
								echo wp_kses_post( $bp_get_last_activity );
							endif;
							?>
						</div>
						<?php
					endif;
					?>
				</div><!-- .bb-user-content-wrap -->

				<?php bboss_nouveau_member_hook( '', 'header_profile_meta' ); ?>

				<div class="bb-rl-member-header-actions-wrap">
					<?php
						$args = array(
							'container'         => 'div',
							'button_element'    => 'button',
							'container_classes' => array( 'bb-rl-member-header-actions', 'flex' ),
							'prefix_link_text'  => '<i></i>',
							'is_tooltips'       => false,
							'button_attr'       => array(
								'hover_type' => 'hover',
							),
							'type'              => 'profile',
						);

						add_filter( 'bp_nouveau_get_members_buttons', 'BBoss_Readylaunch::bb_rl_member_profile_buttons', 10, 3 );
						$members_buttons = bboss_nouveau_get_members_buttons( $args );
						remove_filter( 'bp_nouveau_get_members_buttons', 'BBoss_Readylaunch::bb_rl_member_profile_buttons', 10, 3 );

						$allowed_keys    = array( 'member_friendship', 'member_follow', 'private_message', 'edit_profile' );
						$members_buttons = array_intersect_key( $members_buttons, array_flip( $allowed_keys ) );
						$output          = join( ' ', $members_buttons );

						/**
						 * On the member's header we need to reset the group button's global
						 * once displayed as the friends component will use the member's loop
						 */
						if ( ! empty( $bp_nouveau->members->member_buttons ) ) {
							unset( $bp_nouveau->members->member_buttons );
						}

						ob_start();
						/**
						 * Fires in the member header actions section.
						 *
						 * @since BuddyBoss 2.9.00
						 */
						do_action( 'bp_member_header_actions' );
						$output .= ob_get_clean();

						if ( $output ) {
							bboss_nouveau_wrapper( array_merge( $args, array( 'output' => $output ) ) );
						}

						// More menu.
						$args = array(
							'container'         => 'div',
							'button_element'    => 'button',
							'container_classes' => array( 'bb-rl-more_options', 'bb-rl-header-dropdown', 'bb_more_options' ),
							'is_tooltips'       => false,
							'button_attr'       => array(
								'hover_type' => 'static',
							),
							'type'              => 'profile',
						);

						$members_buttons = bboss_nouveau_get_members_buttons( $args );
						$excluded_keys   = array( 'member_friendship', 'member_follow', 'private_message' );
						$members_buttons = array_diff_key( $members_buttons, array_flip( $excluded_keys ) );
						$output          = join( ' ', $members_buttons );

						/**
						 * On the member's header we need to reset the group button's global
						 * once displayed as the friends component will use the member's loop
						 */
						if ( ! empty( $bp_nouveau->members->member_buttons ) ) {
							unset( $bp_nouveau->members->member_buttons );
						}

						ob_start();
						/**
						 * Fires in the member header actions section.
						 *
						 * @since BuddyBoss 2.9.00
						 */
						do_action( 'bp_member_header_bubble_actions' );
						$output .= ob_get_clean();

						if ( $output ) {
							ob_start();
							bboss_get_template_part( 'common/more-options-view' );
							$template_part_content = ob_get_clean();

							$output = sprintf( '<a href="#" class="bb-rl-more_options_action bb_more_options_action" aria-label="%1$s"><i class="bb-icons-rl-dots-three"></i></a><div class="bb-rl-more_options_list bb_more_options_list bb_more_dropdown bb-rl-more_dropdown"> %2$s %3$s</div><div class="bb-rl-more_dropdown_overlay"></div>', esc_attr__( 'More Options', 'buddyboss-platform' ), $template_part_content, $output );

							bboss_nouveau_wrapper( array_merge( $args, array( 'output' => $output ) ) );
						}
						?>
				</div><!-- .bb-rl-member-header-actions-wrap -->
			</div><!-- #bb-rl-item-header-content -->
		</div><!-- #bb-rl-profile-item-header -->
	</div><!-- #bb-rl-profile-container -->
	<?php
	add_filter( 'bp_get_add_follow_button', 'bboss_bp_get_add_follow_button' );

endif;
?>

<!-- Remove Connection confirmation popup -->
<div class="bb-remove-connection bb-action-popup" style="display: none">
	<transition name="modal">
		<div class="bb-rl-modal-mask bb-white bbm-model-wrap">
			<div class="modal-wrapper">
				<div class="modal-container">
					<header class="bb-model-header">
						<h4><span class="target_name"><?php echo esc_html__( 'Remove Connection', 'buddyboss-platform' ); ?></span></h4>
						<a class="bb-close-remove-connection bb-model-close-button" href="#">
							<span class="bb-icon-l bb-icon-times"></span>
						</a>
					</header>
					<div class="bb-remove-connection-content bb-action-popup-content bb-rl-modal-content">
						<p>
							<?php
							printf(
								/* translators: %s: The member name with HTML tags. */
								esc_html__( 'Are you sure you want to remove %s from your connections?', 'buddyboss-platform' ),
								'<span class="bb-user-name"></span>'
							);
							?>
						</p>
					</div>
					<footer class="bb-model-footer flex align-items-center">
						<a class="bb-close-remove-connection bb-close-action-popup bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small" href="#"><?php echo esc_html__( 'Cancel', 'buddyboss-platform' ); ?></a>
						<a class="button push-right bb-confirm-remove-connection bb-rl-button bb-rl-button--brandFill bb-rl-button--small" href="#"><?php echo esc_html__( 'Confirm', 'buddyboss-platform' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div> <!-- .bb-remove-connection -->
