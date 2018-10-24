<?php
/**
 * BuddyBoss - Users Header
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */
?>

<div id="item-header-avatar">
	<?php if ( bp_is_my_profile() && ! bp_disable_avatar_uploads() ) { ?>
		<a href="<?php bp_members_component_link( 'profile', 'change-avatar' ); ?>" class="link-change-profile-image">
			<span class="bp-tooltip icon-wrap" data-bp-tooltip="<?php _e('Change Profile Photo', 'buddypress'); ?>"><span class="dashicons dashicons-camera"></span></span>
			<?php bp_displayed_user_avatar( 'type=full' ); ?>
		</a>
	<?php } else {
		bp_displayed_user_avatar( 'type=full' );
	} ?>
</div><!-- #item-header-avatar -->

<div id="item-header-content">

	<?php if ( bp_is_active( 'activity' ) && bp_activity_do_mentions() ) : ?>
		<h2 class="user-nicename">@<?php bp_displayed_user_mentionname(); ?></h2>
	<?php endif; ?>

	<?php bp_nouveau_member_hook( 'before', 'header_meta' ); ?>

	<?php if ( bp_nouveau_member_has_meta() ) : ?>
		<div class="item-meta">

			<?php bp_nouveau_member_meta(); ?>

		</div><!-- #item-meta -->
	<?php endif; ?>

	<?php bp_nouveau_member_header_buttons( array( 'container_classes' => array( 'member-header-actions' ) ) ); ?>
</div><!-- #item-header-content -->
