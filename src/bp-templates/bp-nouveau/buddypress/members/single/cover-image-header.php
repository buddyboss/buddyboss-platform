<?php
/**
 * BuddyBoss - Users Cover Photo Header
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
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

if ( ! empty( $cover_image_url ) ) {
	$cover_image_position = bp_get_user_meta( bp_displayed_user_id(), 'bp_cover_position', true );
	$has_cover_image      = ' has-cover-image';
	if ( '' !== $cover_image_position ) {
		$has_cover_image_position = 'has-position';
	}
}
?>

<div id="cover-image-container">
	<div id="header-cover-image" class="<?php echo esc_attr( $has_cover_image_position . $has_cover_image ); ?>">
		<?php
		if ( ! empty( $cover_image_url ) ) {
			echo '<img class="header-cover-img" src="' . esc_url( $cover_image_url ) . '"' . ( '' !== $cover_image_position ? ' data-top="' . esc_attr( $cover_image_position ) . '"' : '' ) . ( '' !== $cover_image_position ? ' style="top: ' . esc_attr( $cover_image_position ) . 'px"' : '' ) . ' alt="" />';
		}
		?>
		<?php if ( bp_is_my_profile() ) { ?>
			<a href="<?php echo bp_get_members_component_link( 'profile', 'change-cover-image' ); ?>" class="link-change-cover-image bp-tooltip" data-bp-tooltip-pos="right" data-bp-tooltip="<?php esc_attr_e( 'Change Cover Photo', 'buddyboss' ); ?>">
				<i class="bb-icon-edit-thin"></i>
			</a>

			<?php if ( ! empty( $cover_image_url ) ) { ?>
				<a href="#" class="position-change-cover-image bp-tooltip" data-bp-tooltip-pos="right" data-bp-tooltip="<?php esc_attr_e( 'Reposition Cover Photo', 'buddyboss' ); ?>">
					<i class="bb-icon-move"></i>
				</a>
				<div class="header-cover-reposition-wrap">
					<a href="#" class="button small cover-image-cancel"><?php _e( 'Cancel', 'buddyboss' ); ?></a>
					<a href="#" class="button small cover-image-save"><?php _e( 'Save Changes', 'buddyboss' ); ?></a>
					<span class="drag-element-helper"><i class="bb-icon-menu"></i><?php _e( 'Drag to move cover photo', 'buddyboss' ); ?></span>
					<img src="<?php echo esc_url( $cover_image_url ); ?>" alt="<?php _e( 'Cover photo', 'buddyboss' ); ?>" />
				</div>
			<?php } ?>

		<?php } ?>
	</div>

	<div id="item-header-cover-image" class="item-header-wrap">
		<div id="item-header-avatar">
			<?php if ( bp_is_my_profile() && ! bp_disable_avatar_uploads() ) { ?>
				<a href="<?php bp_members_component_link( 'profile', 'change-avatar' ); ?>" class="link-change-profile-image bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Change Profile Photo', 'buddyboss' ); ?>">
					<i class="bb-icon-edit-thin"></i>
				</a>
			<?php } ?>
			<?php bp_displayed_user_avatar( 'type=full' ); ?>
		</div><!-- #item-header-avatar -->

		<div id="item-header-content">
			<h2 class="user-nicename"><?php echo bp_core_get_user_displayname( bp_displayed_user_id() ); ?></h2>

			<?php
			$nickname_field_id = bp_xprofile_nickname_field_id();
			$hidden_fields     = bp_xprofile_get_hidden_fields_for_user();

			if ( ( bp_is_active( 'activity' ) && bp_activity_do_mentions() ) || bp_nouveau_member_has_meta() ) :
				?>
				<div class="item-meta">
					<?php
					if ( true === bp_member_type_enable_disable() && true === bp_member_type_display_on_profile() ) {
						echo bp_get_user_member_type( bp_displayed_user_id() );
					} elseif ( bp_is_active( 'activity' ) && bp_activity_do_mentions() && ! in_array( $nickname_field_id, $hidden_fields ) ) {
						?>
						<span class="mention-name">@<?php bp_displayed_user_mentionname(); ?></span>
						<?php
					}
					?>

					<?php if ( bp_is_active( 'activity' ) && bp_activity_do_mentions() && bp_nouveau_member_has_meta() && '' !== bp_get_user_member_type( bp_displayed_user_id() ) && ! in_array( $nickname_field_id, $hidden_fields ) ) : ?>
						<span class="separator">&bull;</span>
					<?php endif; ?>

					<?php bp_nouveau_member_hook( 'before', 'header_meta' ); ?>

					<?php if ( bp_nouveau_member_has_meta() ) : ?>
						<?php bp_nouveau_member_meta(); ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php echo bp_get_user_social_networks_urls(); ?>

			<?php
			bp_nouveau_member_header_buttons(
				array(
					'container'         => 'div',
					'button_element'    => 'button',
					'container_classes' => array( 'member-header-actions' ),
				)
			);
			?>

			<?php bp_nouveau_member_header_bubble_buttons(
				array(
					'container'         => 'div',
					'button_element'    => 'button',
					'container_classes' => array( 'bb_more_options' ),
				)
			); ?>

		</div><!-- #item-header-content -->
	</div><!-- #item-header-cover-image -->
</div><!-- #cover-image-container -->
