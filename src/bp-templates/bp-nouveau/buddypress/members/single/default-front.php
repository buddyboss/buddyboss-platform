<?php
/**
 * BP Nouveau Default user's front template.
 *
 * @since 3.0.0
 * @version 3.1.0
 */
?>

<div class="member-front-page">

	<?php if ( ! is_customize_preview() && bp_current_user_can( 'bp_moderate' ) && ! is_active_sidebar( 'sidebar-buddypress-members' ) ) : ?>

		<div class="bp-feedback custom-homepage-info info">
			<strong><?php esc_html_e( 'Configure the member dashboard', 'buddyboss' ); ?></strong>
			<button type="button" class="bp-tooltip" data-bp-tooltip="<?php echo esc_attr_x( 'Close', 'button', 'buddyboss' ); ?>" aria-label="<?php esc_attr_e( 'Close this notice', 'buddyboss' ); ?>" data-bp-close="remove"><span class="dashicons dashicons-dismiss" aria-hidden="true"></span></button><br/>
			<?php
			printf(
				esc_html__( 'You can enable or disable the %1$s or add %2$s to it.', 'buddyboss' ),
				bp_nouveau_members_get_customizer_option_link(),
				bp_nouveau_members_get_customizer_widgets_link()
			);
			?>
		</div>

	<?php endif; ?>

	<?php if ( is_active_sidebar( 'sidebar-buddypress-members' ) ) : ?>

		<div id="member-front-widgets" class="bp-sidebar bp-widget-area" role="complementary">
			<?php dynamic_sidebar( 'sidebar-buddypress-members' ); ?>
		</div><!-- .bp-sidebar.bp-widget-area -->

	<?php endif; ?>

</div>
