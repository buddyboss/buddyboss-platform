<?php
/**
 * BuddyBoss - Users Header
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */
?>

<div class="item-header-wrap">

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
		<?php bp_nouveau_member_hook( 'before', 'header_meta' ); ?>

		<?php if ( ( bp_is_active( 'activity' ) && bp_activity_do_mentions() ) || bp_nouveau_member_has_meta() ) : ?>
			<div class="item-meta">
				<?php
				if ( true === bp_member_type_enable_disable() && true === bp_member_type_display_on_profile() ) {
					echo bp_get_user_member_type( bp_displayed_user_id() );
				} elseif ( bp_is_active( 'activity' ) && bp_activity_do_mentions() ) {
					?>
					<span class="mention-name">@<?php bp_displayed_user_mentionname(); ?></span>
					<?php
				}
				?>

				<?php if ( bp_is_active( 'activity' ) && bp_activity_do_mentions() && bp_nouveau_member_has_meta() && '' !== bp_get_user_member_type( bp_displayed_user_id() ) ) : ?>
					<span class="separator">&bull;</span>
				<?php endif; ?>

				<?php bp_nouveau_member_hook( 'before', 'header_meta' ); ?>

				<?php if ( bp_nouveau_member_has_meta() ) : ?>
					<?php bp_nouveau_member_meta(); ?>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php echo bp_get_user_social_networks_urls(); ?>

		<?php bp_nouveau_member_header_buttons( array( 'container_classes' => array( 'member-header-actions' ) ) ); ?>
		<?php bp_nouveau_member_header_bubble_buttons( array( 'container_classes' => array( 'bb_more_options' ), ) ); ?>
	</div><!-- #item-header-content -->

</div><!-- .item-header-wrap -->
