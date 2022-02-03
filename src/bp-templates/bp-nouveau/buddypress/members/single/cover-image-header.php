<?php
/**
 * The template for users cover photo header
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/cover-image-header.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

$has_cover_image          = '';
$has_cover_image_position = '';
$displayed_user           = bp_get_displayed_user();
$cover_image_url          = bp_attachments_get_attachment(
	'url',
	array(
		'object_dir' => 'members',
		'item_id'    => $displayed_user->id,
	)
);
$has_default_cover        = bb_attachment_get_cover_image_class( $displayed_user->id, 'user' );

if ( ! empty( $cover_image_url ) ) {
	$cover_image_position = bp_get_user_meta( bp_displayed_user_id(), 'bp_cover_position', true );
	$has_cover_image      = ' has-cover-image';
	if ( '' !== $cover_image_position ) {
		$has_cover_image_position = ' has-position';
	}
}

$profile_cover_width  = bb_get_profile_cover_image_width();
$profile_cover_height = bb_get_profile_cover_image_height();
?>

<?php if ( ! bp_is_user_messages() && ! bp_is_user_settings() && ! bp_is_user_notifications() && ! bp_is_user_profile_edit() && ! bp_is_user_change_avatar() && ! bp_is_user_change_cover_image() ) : ?>

	<div id="cover-image-container">
		<div id="header-cover-image" class="<?php echo esc_attr( 'cover-' . $profile_cover_height . ' width-' . $profile_cover_width . $has_cover_image_position . $has_cover_image . $has_default_cover ); ?>">
			<?php
			if ( ! empty( $cover_image_url ) ) {
				echo '<img class="header-cover-img" src="' . esc_url( $cover_image_url ) . '"' . ( '' !== $cover_image_position ? ' data-top="' . esc_attr( $cover_image_position ) . '"' : '' ) . ( '' !== $cover_image_position ? ' style="top: ' . esc_attr( $cover_image_position ) . 'px"' : '' ) . ' alt="" />';
			}
			?>
			<?php if ( bp_is_my_profile() ) { ?>
				<a href="<?php echo esc_url( bp_get_members_component_link( 'profile', 'change-cover-image' ) ); ?>" class="link-change-cover-image bp-tooltip" data-bp-tooltip-pos="right" data-bp-tooltip="<?php esc_attr_e( 'Change Cover Photo', 'buddyboss' ); ?>">
					<i class="bb-icon-edit-thin"></i>
				</a>

				<?php if ( ! empty( $cover_image_url ) && bp_attachments_get_user_has_cover_image( $displayed_user->id ) ) { ?>
					<a href="#" class="position-change-cover-image bp-tooltip" data-bp-tooltip-pos="right" data-bp-tooltip="<?php esc_attr_e( 'Reposition Cover Photo', 'buddyboss' ); ?>">
						<i class="bb-icon-move"></i>
					</a>
					<div class="header-cover-reposition-wrap">
						<a href="#" class="button small cover-image-cancel"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
						<a href="#" class="button small cover-image-save"><?php esc_html_e( 'Save Changes', 'buddyboss' ); ?></a>
						<span class="drag-element-helper"><i class="bb-icon-menu"></i><?php esc_html_e( 'Drag to move cover photo', 'buddyboss' ); ?></span>
						<img src="<?php echo esc_url( $cover_image_url ); ?>" alt="<?php esc_attr_e( 'Cover photo', 'buddyboss' ); ?>" />
					</div>
				<?php } ?>

			<?php } ?>
		</div>

		<?php $class = bp_disable_cover_image_uploads() ? 'bb-disable-cover-img' : 'bb-enable-cover-img'; ?>

		<div id="item-header-cover-image" class="item-header-wrap <?php echo esc_attr( $class ); ?>">

			<div id="item-header-avatar">
				<?php if ( bp_is_my_profile() && ! bp_disable_avatar_uploads() ) { ?>
					<a href="<?php bp_members_component_link( 'profile', 'change-avatar' ); ?>" class="link-change-profile-image bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Change Profile Photo', 'buddyboss' ); ?>">
						<i class="bb-icon-edit-thin"></i>
					</a>
				<?php } ?>
				<?php bp_displayed_user_avatar( 'type=full' ); ?>
			</div><!-- #item-header-avatar -->

			<div id="item-header-content">

				<div class="flex">

					<div class="bb-user-content-wrap">
						<div class="flex align-items-center member-title-wrap">
							<h2 class="user-nicename"><?php echo bp_core_get_user_displayname( bp_displayed_user_id() ); ?></h2>
							<?php
							if ( true === bp_member_type_enable_disable() && true === bp_member_type_display_on_profile() ) {
								echo bp_get_user_member_type( bp_displayed_user_id() );
							}
							?>
						</div>

						<?php bp_nouveau_member_hook( 'before', 'header_meta' ); ?>

						<?php if ( ( bp_is_active( 'activity' ) && bp_activity_do_mentions() ) || bp_nouveau_member_has_meta() ) : ?>
							<div class="item-meta">
								<?php
								$nickname_field_id = bp_xprofile_nickname_field_id();
								$hidden_fields     = bp_xprofile_get_hidden_fields_for_user();

								if ( bp_is_active( 'activity' ) && bp_activity_do_mentions() && ! in_array( $nickname_field_id, $hidden_fields, true ) ) :
									?>
									<span class="mention-name">@<?php bp_displayed_user_mentionname(); ?></span>
								<?php endif; ?>

								<?php if ( bp_is_active( 'activity' ) && bp_activity_do_mentions() && bp_nouveau_member_has_meta() && '' !== bp_get_user_member_type( bp_displayed_user_id() ) && ! in_array( $nickname_field_id, $hidden_fields, true ) ) : ?>
									<span class="separator">&bull;</span>
								<?php endif; ?>

								<?php
								bp_nouveau_member_hook( 'before', 'in_header_meta' );
								bp_nouveau_member_hook( 'before', 'header_meta' );

								if ( bp_nouveau_member_has_meta() ) :
									bp_nouveau_member_meta();
								endif;
								?>

							</div>
						<?php endif; ?>

						<?php if ( function_exists( 'bp_is_activity_follow_active' ) && bp_is_active( 'activity' ) && bp_is_activity_follow_active() ) { ?>
							<div class="flex align-items-top member-social">
								<div class="flex align-items-center">
									<?php bb_get_followers_count(); ?>
									<?php bb_get_following_count(); ?>
								</div>
								<?php echo bp_get_user_social_networks_urls(); ?>
							</div>
						<?php } else { ?>
							<div class="flex align-items-center">
								<?php echo bp_get_user_social_networks_urls(); ?>
							</div>
						<?php } ?>

					</div>

					<?php
					remove_filter( 'bp_get_add_friend_button', 'buddyboss_theme_bp_get_add_friend_button' );

					bp_nouveau_member_header_buttons(
						array(
							'container'         => 'div',
							'button_element'    => 'button',
							'container_classes' => array( 'member-header-actions' ),
						)
					);

					bp_nouveau_member_header_bubble_buttons(
						array(
							'container'         => 'div',
							'button_element'    => 'button',
							'container_classes' => array( 'bb_more_options' ),
						)
					);

					add_filter( 'bp_get_add_friend_button', 'buddyboss_theme_bp_get_add_friend_button' );
					?>

				</div>

			</div><!-- #item-header-content -->
		</div><!-- #item-header-cover-image -->
	</div><!-- #cover-image-container -->

	<?php
	add_filter( 'bp_get_add_follow_button', 'buddyboss_theme_bp_get_add_follow_button' );

endif;
