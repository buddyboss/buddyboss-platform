<?php
/**
 * The template for users cover photo header
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/cover-image-header.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

$bp_displayed_user_id     = bp_displayed_user_id();
$has_cover_image          = '';
$has_cover_image_position = '';
$has_default_cover        = bb_attachment_get_cover_image_class( $bp_displayed_user_id, 'user' );
$profile_cover_width      = bb_get_profile_cover_image_width();
$profile_cover_height     = bb_get_profile_cover_image_height();
$cover_image_url          = bp_attachments_get_attachment(
	'url',
	array(
		'object_dir' => 'members',
		'item_id'    => $bp_displayed_user_id,
	)
);

if ( ! empty( $cover_image_url ) ) {
	$cover_image_position = bp_get_user_meta( $bp_displayed_user_id, 'bp_cover_position', true );
	$has_cover_image      = ' has-cover-image';
	if ( '' !== $cover_image_position ) {
		$has_cover_image_position = ' has-position';
	}
}

$profile_header_layout_style = bb_get_profile_header_layout_style();
$is_enabled_online_status    = bb_enabled_profile_header_layout_element( 'online-status' );
$is_enabled_profile_type     = bb_enabled_profile_header_layout_element( 'profile-type' );
$is_enabled_member_handle    = bb_enabled_profile_header_layout_element( 'member-handle' );
$is_enabled_joined_date      = bb_enabled_profile_header_layout_element( 'joined-date' );
$is_enabled_last_active      = bb_enabled_profile_header_layout_element( 'last-active' );
$is_enabled_followers        = bb_enabled_profile_header_layout_element( 'followers' );
$is_enabled_following        = bb_enabled_profile_header_layout_element( 'following' );
$is_enabled_social_networks  = bb_enabled_profile_header_layout_element( 'social-networks' ) && function_exists( 'bb_enabled_member_social_networks' ) && bb_enabled_member_social_networks();

$my_profile                     = '';
$user_social_networks_urls      = '';
$social_networks_urls_div_class = 'social-networks-hide';
if ( $is_enabled_social_networks ) {

	add_filter( 'bp_get_user_social_networks_urls', 'bb_get_user_social_networks_urls_with_visibility', 10, 3 );
	$user_social_networks_urls = bp_get_user_social_networks_urls();
	remove_filter( 'bp_get_user_social_networks_urls', 'bb_get_user_social_networks_urls_with_visibility', 10, 3 );

	if ( ! empty( $user_social_networks_urls ) ) {
		$social_networks_urls_div_class = 'network_profiles';
	}
}

$bp_is_my_profile = bp_is_my_profile();
if ( $bp_is_my_profile ) {
	$my_profile = 'my_profile';
}

if ( ! bp_is_user_messages() && ! bp_is_user_settings() && ! bp_is_user_notifications() && ! bp_is_user_profile_edit() && ! bp_is_user_change_avatar() && ! bp_is_user_change_cover_image() ) :

	$is_activity_enabled       = bp_is_active( 'activity' );
	$bp_activity_do_mentions   = $is_activity_enabled && bp_activity_do_mentions();
	$bp_get_last_activity      = bp_get_last_activity();
	$bb_get_member_joined_date = bb_get_member_joined_date();

	$member_type = '';
	if ( true === bp_member_type_enable_disable() && true === bp_member_type_display_on_profile() && $is_enabled_profile_type ) {
		$member_type = bp_get_user_member_type( $bp_displayed_user_id );
	}
	?>

	<div id="bb-rl-profile-container" class="<?php echo esc_attr( $profile_header_layout_style . ' ' . $social_networks_urls_div_class . ' ' . $my_profile ); ?>">

		<div id="bb-rl-profile-item-header" class="item-header-wrap flex">
			<?php
			$moderation_class = function_exists( 'bp_moderation_is_user_suspended' ) && bp_moderation_is_user_suspended( $bp_displayed_user_id ) ? 'bp-user-suspended' : '';
			$moderation_class = function_exists( 'bp_moderation_is_user_blocked' ) && bp_moderation_is_user_blocked( $bp_displayed_user_id ) ? $moderation_class . ' bp-user-blocked' : $moderation_class;
			?>
			<div id="bb-rl-item-header-avatar" class="<?php echo esc_attr( $moderation_class ); ?>">
				<?php
				if ( $is_enabled_online_status ) {
					bb_user_presence_html( $bp_displayed_user_id );
				}

				if ( $bp_is_my_profile && ! bp_disable_avatar_uploads() ) {
					?>
					<a href="<?php bp_members_component_link( 'profile', 'change-avatar' ); ?>" class="link-change-profile-image bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Change Profile Photo', 'buddyboss' ); ?>">
						<i class="bb-icon-rf bb-icon-camera"></i>
					</a>
					<span class="link-change-overlay"></span>
					<?php
				}
				bp_displayed_user_avatar( 'type=full' );
				?>
			</div><!-- #item-header-avatar -->

			<div id="bb-rl-item-header-content">
				<div class="bb-user-content-wrap">
					<div class="flex flex-column member-title-wrap">
						<?php
							if ( ! empty( $member_type ) ) {
								echo wp_kses_post( $member_type );
							}
						?>
						<h2 class="user-nicename"><?php echo wp_kses_post( bp_core_get_user_displayname( $bp_displayed_user_id ) ); ?></h2>
					</div>

					<?php
					bp_nouveau_member_hook( 'before', 'header_meta' );
					if ( ( $is_activity_enabled && $bp_activity_do_mentions ) || $bp_get_last_activity || $bb_get_member_joined_date ) :
						?>
						<div class="item-meta">
							<?php
							$nickname_field_id = bp_xprofile_nickname_field_id();
							$hidden_fields     = bp_xprofile_get_hidden_fields_for_user();
							if ( $is_activity_enabled && $bp_activity_do_mentions && ! in_array( $nickname_field_id, $hidden_fields, true ) && $is_enabled_member_handle ) :
								?>
								<span class="mention-name">@<?php bp_displayed_user_mentionname(); ?></span>
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
							bp_nouveau_member_hook( 'before', 'in_header_meta' );
							if ( $bp_get_last_activity && $is_enabled_last_active ) :
								echo wp_kses_post( $bp_get_last_activity );
							endif;
							?>
						</div>
						<?php
					endif;
					?>
				</div><!-- .bb-user-content-wrap -->

				<div class="member-header-actions-wrap">
						<?php
						bp_nouveau_member_header_buttons(
							array(
								'container'         => 'div',
								'button_element'    => 'button',
								'container_classes' => array( 'member-header-actions' ),
								'prefix_link_text'  => '<i></i>',
								'is_tooltips'       => false,
								'button_attr'       => array(
									'hover_type' => 'hover',
								),
							)
						);

						bp_nouveau_member_header_bubble_buttons(
							array(
								'container'         => 'div',
								'button_element'    => 'button',
								'container_classes' => array( 'bb_more_options', 'header-dropdown' ),
								'is_tooltips'       => false,
								'button_attr'       => array(
									'hover_type' => 'static',
								),
							)
						);
						?>
				</div><!-- .member-header-actions-wrap -->
			</div><!-- #item-header-content -->
		</div><!-- #item-header-cover-image -->
	</div><!-- #cover-image-container -->

	<?php
	add_filter( 'bp_get_add_follow_button', 'bb_bp_get_add_follow_button' );

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
								/* translators: %s: The member name with HTML tags. */
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
