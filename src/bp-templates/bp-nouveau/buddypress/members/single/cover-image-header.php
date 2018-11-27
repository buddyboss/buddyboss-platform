<?php
/**
 * BuddyBoss - Users Cover Image Header
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */
?>

<div id="cover-image-container">

	<div id="header-cover-image">
		<?php if ( bp_is_my_profile() ) { ?>
			<a href="<?php echo bp_get_members_component_link( 'profile', 'change-cover-image' ); ?>" class="link-change-cover-image bp-tooltip" data-bp-tooltip="<?php _e('Change Cover Image', 'buddyboss'); ?>">
				<span class="dashicons dashicons-edit"></span>
			</a>
		<?php } ?>
	</div>

	<div id="item-header-cover-image" class="item-header-wrap">
		<div id="item-header-avatar">
			<?php if ( bp_is_my_profile() && ! bp_disable_avatar_uploads() ) { ?>
				<a href="<?php bp_members_component_link( 'profile', 'change-avatar' ); ?>" class="link-change-profile-image bp-tooltip" data-bp-tooltip="<?php _e('Change Profile Photo', 'buddyboss'); ?>">
					<span class="dashicons dashicons-edit"></span>
				</a>
			<?php } ?>
			<?php bp_displayed_user_avatar( 'type=full' ); ?>
		</div><!-- #item-header-avatar -->

		<div id="item-header-content">

			<?php if ( bp_is_active( 'activity' ) && bp_activity_do_mentions() ) : ?>
				<h2 class="user-nicename">@<?php bp_displayed_user_mentionname(); ?></h2>
			<?php endif; ?>

			<?php
			bp_nouveau_member_header_buttons(
				array(
					'container'         => 'ul',
					'button_element'    => 'button',
					'container_classes' => array( 'member-header-actions' ),
				)
			);
			?>

			<?php bp_nouveau_member_hook( 'before', 'header_meta' ); ?>

			<?php

			if ( true === bp_member_type_enable_disable() ) {
				if ( true === bp_member_type_display_on_profile() ) {

					// Get the member type.
					$type = bp_get_member_type( bp_displayed_user_id() );

					// Output the
					if ( $type_obj = bp_get_member_type_object( $type ) ) {
						?>
						<span class="member-type">
							<?php echo esc_html( $type_obj->labels['singular_name'] ); ?>
						</span> &#149;<!-- #item-meta -->
						<?php
					} else {
						?>
						<span class="member-type">
							<?php echo esc_html( 'Member' ); ?>
						</span> &#149;<!-- #item-meta -->
						<?php
					}
				}
			}
			?>

			<?php if ( bp_nouveau_member_has_meta() ) : ?>
				<div class="item-meta">

					<?php bp_nouveau_member_meta(); ?>

				</div><!-- #item-meta -->
			<?php endif; ?>

		</div><!-- #item-header-content -->

	</div><!-- #item-header-cover-image -->

</div><!-- #cover-image-container -->
