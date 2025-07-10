<?php
/**
 * ReadyLaunch - Member Settings Subscriptions template.
 *
 * This template handles the subscription settings for members.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_nouveau_member_hook( 'before', 'settings_template' );

?>
	<?php bp_get_template_part( 'members/single/parts/notification-subnav' ); ?>

	<div class="subscription-views">
		<?php
		$types               = bb_get_subscriptions_types();
		$types_with_singular = bb_get_subscriptions_types( true );
		if ( ! empty( $types ) ) {
			foreach ( $types as $sub_type => $label ) {
				?>
				<div class="bb-accordion" data-type="<?php echo esc_attr( $sub_type ); ?>" data-plural-label="<?php echo esc_attr( $label ); ?>" data-singular-label="<?php echo ( ! empty( $types_with_singular[ $sub_type ] ) ) ? esc_attr( $types_with_singular[ $sub_type ] ) : ''; ?>">
					<div class="bb-accordion_head" id="bb-accordion-<?php echo esc_attr( $sub_type ); ?>">
						<h3 class="bb-accordion_title">
							<?php echo esc_html( $label ); ?>
						</h3>
						<button type="button" aria-expanded="true" class="bb-accordion_trigger" aria-controls="bb-accordion-section-<?php echo esc_attr( $sub_type ); ?>">
							<i class="bb-icons-rl-caret-down"></i>
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

bp_nouveau_member_hook( 'after', 'settings_template' );
