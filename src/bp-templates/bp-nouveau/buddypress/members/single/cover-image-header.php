<?php
/**
 * BuddyBoss - Users Cover Photo Header
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */
?>

<div id="cover-image-container">

	<div id="header-cover-image">
		<?php if ( bp_is_my_profile() ) { ?>
			<a href="<?php echo bp_get_members_component_link( 'profile', 'change-cover-image' ); ?>" class="link-change-cover-image bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e('Change Cover Photo', 'buddyboss'); ?>">
				<i class="bb-icon-edit-thin"></i>
			</a>
		<?php } ?>
	</div>

	<div id="item-header-cover-image" class="item-header-wrap">
		<div id="item-header-avatar">
			<?php if ( bp_is_my_profile() && ! bp_disable_avatar_uploads() ) { ?>
				<a href="<?php bp_members_component_link( 'profile', 'change-avatar' ); ?>" class="link-change-profile-image bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e('Change Profile Photo', 'buddyboss'); ?>">
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

			if ( ( bp_is_active( 'activity' ) && bp_activity_do_mentions() ) || bp_nouveau_member_has_meta() ) : ?>
				<div class="item-meta">
					<?php
					if ( true === bp_member_type_enable_disable() && true === bp_member_type_display_on_profile() ) {
						echo bp_get_user_member_type( bp_displayed_user_id() );
					} elseif ( bp_is_active( 'activity' ) && bp_activity_do_mentions() && ! in_array( $nickname_field_id, $hidden_fields ) ) { ?>
						<span class="mention-name">@<?php bp_displayed_user_mentionname(); ?></span><?php
					} ?>

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

		</div><!-- #item-header-content -->

	</div><!-- #item-header-cover-image -->

</div><!-- #cover-image-container -->
