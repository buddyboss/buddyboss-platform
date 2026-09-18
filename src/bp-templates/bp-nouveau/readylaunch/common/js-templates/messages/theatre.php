<?php
/**
 * ReadyLaunch - The template for media and video theater.
 *
 * @since    BuddyBoss 2.9.00
 * @version  1.0.0
 */

?>
<div class="bb-rl-media-video-model-wrapper bb-rl-internal-model media-video bb-rl-media-video-theatre" style="display: none;" id="buddypress">
	<div id="bb-rl-media-video-model-container" class="bb-rl-media-model-container">
		<div class="bb-rl-media-model-header">
			<h2></h2>
			<a data-balloon-pos="left" data-balloon="<?php esc_attr_e( 'Toggle Sidebar', 'buddyboss' ); ?>" class="bb-rl-toggle-theatre-sidebar" href="#">
				<i class="bb-icons-rl-sidebar-simple"></i>
			</a>
			<a data-balloon-pos="left" data-balloon="<?php esc_attr_e( 'Close', 'buddyboss' ); ?>" class="bb-rl-close-media-theatre bb-rl-close-model" href="#">
				<i class="bb-icons-rl-x"></i>
			</a>
		</div>
		<div class="bb-rl-media-model-inner">
			<div class="bb-rl-media-section">
				<a class="bb-rl-theater-command bb-rl-prev-media" href="#previous" aria-label="<?php esc_attr_e( 'Previous', 'buddyboss' ); ?>">
					<i class="bb-icons-rl-caret-left"></i>
				</a>
				<a class="bb-rl-theater-command bb-rl-next-media" href="#next" aria-label="<?php esc_attr_e( 'Next', 'buddyboss' ); ?>">
					<i class="bb-icons-rl-caret-right"></i>
				</a>
				<figure class="">
					<img src="" alt="" />
				</figure>
				<div class="bb-rl-dropdown-wrap bb-rl-media-only-privacy">
					<div class="bb-media-privacy-wrap" style="display: none;">
						<span class="bp-tooltip privacy-wrap" data-bp-tooltip-pos="left" data-bp-tooltip="">
							<span class="privacy selected"></span>
						</span>
						<ul class="media-privacy">
							<?php
							foreach ( bp_media_get_visibility_levels() as $item_key => $privacy_item ) {
								?>
								<li data-value="<?php echo esc_attr( $item_key ); ?>" class="<?php echo esc_attr( $item_key ); ?>">
									<?php echo esc_attr( $privacy_item ); ?>
								</li>
								<?php
							}
							?>
						</ul>
					</div>
				</div>
			</div>
			<div class="bb-media-info-section media">
				<ul class="bb-rl-activity-list bb-rl-item-list bb-rl-list">
					<span><i class="bb-rl-loader"></i></span>
				</ul>
			</div>
		</div>
	</div>
</div>
