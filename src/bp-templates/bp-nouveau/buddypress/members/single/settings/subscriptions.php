<?php
/**
 * The template for members settings ( Subscription )
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/settings/subscriptions.php.
 *
 * @since BuddyBoss [BBVERSION]
 */

bp_nouveau_member_hook( 'before', 'settings_template' );

$data = bb_core_notification_preferences_data();
?>
	<h2 class="screen-heading email-settings-screen"><?php echo wp_kses_post( $data['screen_title'] ); ?></h2>

	<?php bp_get_template_part( 'members/single/parts/notification-subnav' ); ?>

	<div class="subscription-views">
		<?php
		$types = bb_get_subscriptions_types();
		if ( ! empty( $types ) ) {
			foreach ( $types as $sub_type => $label ) {
				?>
				<div class="bb-accordion" data-type="<?php echo esc_attr( $sub_type ); ?>">
					<div class="bb-accordion_head" id="bb-accordion-<?php echo esc_attr( $sub_type ); ?>">
						<h3 class="bb-accordion_title">
							<?php echo esc_html( $label ); ?>
						</h3>
						<button type="button" aria-expanded="true" class="bb-accordion_trigger" aria-controls="bb-accordion-section-<?php echo esc_attr( $sub_type ); ?>">
							<i class="bb-icon-lined bb-icon-angle-up"></i>
						</button>
					</div>

					<div id="bb-accordion-section-<?php echo esc_attr( $sub_type ); ?>" role="region" aria-labelledby="bb-accordion-<?php echo esc_attr( $sub_type ); ?>" class="bb-accordion_panel">
						<div class="subscription-items is_loading">
							<div class="subscription-items_loading">
								<div class="subscription-items-image_loading bb-loading-bg"></div>

								<div class="subscription-items-text_loading bb-loading-bg"></div>
							</div>
							<div class="subscription-items_loading">
								<div class="subscription-items-image_loading bb-loading-bg"></div>

								<div class="subscription-items-text_loading bb-loading-bg"></div>
							</div>
							<div class="subscription-items_loading">
								<div class="subscription-items-image_loading bb-loading-bg"></div>

								<div class="subscription-items-text_loading bb-loading-bg"></div>
							</div>
						</div>
					</div>
				</div><!-- .bb-accordion -->
				<?php
			}
		}
		?>
	</div>
<?php

/**
 * Split each js template to its own file. Easier for child theme to
 * overwrite individual parts.
 *
 * @version BuddyBoss [BBVERSION]
 */
$template_parts = apply_filters(
	'bb_member_subscriptions_js_template_parts',
	array(
		'bb-member-subscription-loading',
		'bb-subscription-item',
	)
);

foreach ( $template_parts as $template_part ) {
	bp_get_template_part( 'common/js-templates/members/settings/' . $template_part );
}

bp_nouveau_member_hook( 'after', 'settings_template' );
