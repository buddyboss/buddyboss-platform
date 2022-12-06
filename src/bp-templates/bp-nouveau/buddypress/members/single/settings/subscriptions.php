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

	<div class="bb-accordion">

		<div class="bb-accordion_head" id="bb-accordion-1">
			<h3 class="bb-accordion_title">
				<?php esc_html_e( 'Forums', 'buddyboss' ); ?>
			</h3>
			<button type="button" aria-expanded="true" class="bb-accordion_trigger" aria-controls="bb-accordion-section-1" >
				<i class="bb-icon-lined bb-icon-angle-up"></i>
			</button>
		</div>

		<div id="bb-accordion-section-1" role="region" aria-labelledby="bb-accordion-1" class="bb-accordion_panel">
			<ul class="subscription-items">
				<li>
					<a href="#" class="subscription-item_anchor">
						<div class="subscription-item_image">
							<img src="https://source.unsplash.com/user/c_v_r/100x100" alt="" />
						</div>
						<div class="subscription-item_detail">
							<span class="subscription-item_title">Ask Anything Random Here</span>
						</div>
					</a>
					<button type="button" class="subscription-item_remove" aria-label="<?php esc_html_e( 'Unsubscribe', 'buddyboss' ); ?>" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Unsubscribe', 'buddyboss' ) ?>">
						<i class="bb-icon-lined bb-icon-times"></i>
					</button>
					
				</li>
				<li>
					<a href="#" class="subscription-item_anchor">
						<div class="subscription-item_image">
							<img src="https://source.unsplash.com/user/c_v_r/100x100" alt="" />
						</div>
						<div class="subscription-item_detail">
							<span class="subscription-item_title">TV & Movies</span>
							<span class="subscription-item_meta">
								<i class="bb-icon-corner-right"></i>
								<strong>Entertainment</strong>
							</span>
						</div>
					</a>
					<button type="button" class="subscription-item_remove" aria-label="<?php esc_html_e( 'Unsubscribe', 'buddyboss' ); ?>" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Unsubscribe', 'buddyboss' ) ?>">
						<i class="bb-icon-lined bb-icon-times"></i>
					</button>
					
				</li>
			</ul>
		</div>

	</div><!-- .bb-accordion -->

	<div class="bb-accordion">

		<div class="bb-accordion_head" id="bb-accordion-2">
			<h3 class="bb-accordion_title">
				<?php esc_html_e( 'Discussions', 'buddyboss' ); ?>
			</h3>
			<button type="button" aria-expanded="true" class="bb-accordion_trigger" aria-controls="bb-accordion-section-2" >
				<i class="bb-icon-lined bb-icon-angle-up"></i>
			</button>
		</div>

		<div id="bb-accordion-section-2" role="region" aria-labelledby="bb-accordion-2" class="bb-accordion_panel">
			<ul class="subscription-items discussion_items">
				<li>
					<a href="#" class="subscription-item_anchor">
						<div class="subscription-item_image">
							<img src="https://source.unsplash.com/user/c_v_r/100x100" alt="" />
						</div>
						<div class="subscription-item_detail">
							<span class="subscription-item_title">What’s the biggest plot hole you’ve seen in a movie?</span>
							<span class="subscription-item_meta">Posted by <strong>John</strong> in <strong>TV & Movies</strong></span>
						</div>
					</a>
					<button type="button" class="subscription-item_remove" aria-label="<?php esc_html_e( 'Unsubscribe', 'buddyboss' ); ?>" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Unsubscribe', 'buddyboss' ) ?>">
						<i class="bb-icon-lined bb-icon-times"></i>
					</button>
					
				</li>

				<!--  Loading state -->
				<li>
					<a href="#" class="subscription-item_anchor">
						<div class="subscription-item_image">
							<img src="https://source.unsplash.com/user/c_v_r/100x100" alt="" />
						</div>
						<div class="subscription-item_detail">
							<span class="subscription-item_title">20 years of Tokyo's development through the lens of Peter M. Cook</span>
							<span class="subscription-item_meta">Posted by <strong>Sophie</strong> in <strong>Architecture Ideas</strong></span>
						</div>
					</a>
					<button type="button" class="subscription-item_remove is_loading" aria-label="<?php esc_html_e( 'Unsubscribe', 'buddyboss' ); ?>" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Unsubscribe', 'buddyboss' ) ?>">
						<i class="bb-icon-lined bb-icon-times"></i>
					</button>
					
				</li>

			</ul>

			<div class="bbp-pagination">
				<div class="bbp-pagination-links">
					<span aria-current="page" class="page-numbers current">1</span>
					<a class="page-numbers" href="#">2</a>
					<a class="next page-numbers" href="#">→</a>
				</div>
			</div>

		</div>

	</div><!-- .bb-accordion -->

	<div class="bb-accordion">

		<div class="bb-accordion_head" id="bb-accordion-1">
			<h3 class="bb-accordion_title">
				<?php esc_html_e( 'Forums', 'buddyboss' ); ?>
			</h3>
			<button type="button" aria-expanded="true" class="bb-accordion_trigger" aria-controls="bb-accordion-section-1" >
				<i class="bb-icon-lined bb-icon-angle-up"></i>
			</button>
		</div>

		<div id="bb-accordion-section-1" role="region" aria-labelledby="bb-accordion-1" class="bb-accordion_panel">
			<div class="subscription-items">
				<p>You are not currently subscribed to any forums.</p>
			</div>
		</div>

	</div><!-- .bb-accordion -->

	<div class="bb-accordion">

		<div class="bb-accordion_head" id="bb-accordion-1">
			<h3 class="bb-accordion_title">
				<?php esc_html_e( 'Forums', 'buddyboss' ); ?>
			</h3>
			<button type="button" aria-expanded="true" class="bb-accordion_trigger" aria-controls="bb-accordion-section-1" >
				<i class="bb-icon-lined bb-icon-angle-up"></i>
			</button>
		</div>

		<div id="bb-accordion-section-1" role="region" aria-labelledby="bb-accordion-1" class="bb-accordion_panel">
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
bp_nouveau_member_hook( 'after', 'settings_template' );
