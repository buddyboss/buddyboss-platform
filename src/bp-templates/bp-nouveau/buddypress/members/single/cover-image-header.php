<?php
/**
 * BuddyBoss - Users Cover Image Header
 *
 * @since 3.0.0
 * @version 3.0.0
 */
?>

<div id="cover-image-container">
	<div id="header-cover-image"></div>

	<div id="item-header-cover-image">
		<div id="item-header-avatar">
			<a href="<?php bp_displayed_user_link(); ?>">

				<?php bp_displayed_user_avatar( 'type=full' ); ?>

			</a>
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

			<?php if ( bp_nouveau_member_has_meta() ) : ?>
				<div class="item-meta">

					<?php bp_nouveau_member_meta(); ?>

				</div><!-- #item-meta -->
			<?php endif; ?>

			<?php if ( ! is_customize_preview() && bp_current_user_can( 'bp_moderate' ) && ! is_active_sidebar( 'sidebar-buddypress-members' ) ) : ?>

				<div class="bp-feedback custom-homepage-info info">
					<?php
					printf(
						esc_html__( 'You can enable or disable the %1$s or add %2$s to it.', 'buddyboss' ),
						bp_nouveau_members_get_customizer_option_link(),
						bp_nouveau_members_get_customizer_widgets_link()
					);
					?>
				</div>

			<?php endif; ?>

		</div><!-- #item-header-content -->

	</div><!-- #item-header-cover-image -->
</div><!-- #cover-image-container -->
