<?php
/**
 * ReadyLaunch - Video Theatre
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core
 *
 * @version 1.0.0
 */

?>
<div class="bb-rl-media-model-wrapper bb-rl-internal-model video bb-rl-video-theatre" style="display: none;" id="buddypress">
	<a data-balloon-pos="left" data-balloon="<?php esc_html_e( 'Close', 'buddyboss' ); ?>" class="bb-rl-close-media-theatre bb-rl-close-model" href="#">
		<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14">
			<path fill="none" stroke="#FFF" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 1L1 13m12 0L1 1" opacity=".7" />
		</svg>
	</a>
	<div id="bb-rl-media-model-container" class="bb-rl-media-model-container">
		<div class="bb-rl-media-model-inner">
			<div class="bb-rl-media-section">
				<a class="bb-rl-theater-command bb-rl-prev-media" href="#previous">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="30">
						<path fill="none" stroke="#FFF" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 29L1 15 15 1" opacity=".7" />
					</svg>
				</a>
				<a class="bb-rl-theater-command bb-rl-next-media" href="#next">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="30">
						<path fill="none" stroke="#FFF" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1l14 14L1 29" opacity=".7" />
					</svg>
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
								<li data-value="<?php echo esc_attr( $item_key ); ?>" class="<?php echo esc_attr( $item_key ); ?>"><?php echo esc_attr( $privacy_item ); ?></li>
								<?php
							}
							?>
						</ul>
					</div>
				</div>
			</div>
			<div class="bb-media-info-section media">
				<ul class="bb-rl-activity-list bb-rl-item-list bb-rl-list">
					<span><i class="bb-icon-spin5 animate-spin"></i></span>
				</ul>
			</div>
		</div>
	</div>
</div>
