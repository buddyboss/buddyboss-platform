<?php
/**
 * ReadyLaunch - The template for document theater.
 *
 * @since   BuddyBoss 2.9.00
 * @package BuddyBoss\Core
 * @version 1.0.0
 */

?>
<div class="bb-rl-media-model-wrapper bb-rl-internal-model document bb-rl-document-theatre" style="display: none;" id="buddypress">
	<div id="bb-rl-media-model-container" class="bb-rl-media-model-container bb-document-theater">
		<div class="bb-rl-media-model-header">
			<h2></h2>
			<a data-balloon-pos="left" data-balloon="<?php esc_attr_e( 'Toggle Sidebar', 'buddyboss' ); ?>" class="bb-rl-toggle-theatre-sidebar" href="#">
				<i class="bb-icons-rl-sidebar-simple"></i>
			</a>
			<a data-balloon-pos="left" data-balloon="<?php esc_attr_e( 'Close', 'buddyboss' ); ?>" class="bb-rl-close-media-theatre bb-rl-close-model bb-close-document-theatre" href="#">
				<i class="bb-icons-rl-x"></i>
			</a>
		</div>
		<div class="bb-rl-media-model-inner">
			<div class="bb-rl-media-section bb-rl-document-section">
				<a class="bb-rl-theater-command bb-rl-prev-document" href="#previous" aria-label="<?php esc_attr_e( 'Previous', 'buddyboss' ); ?>">
					<i class="bb-icons-rl-caret-left"></i>
				</a>
				<a class="bb-rl-theater-command bb-rl-next-document" href="#next" aria-label="<?php esc_attr_e( 'Next', 'buddyboss' ); ?>">
					<i class="bb-icons-rl-caret-right"></i>
				</a>
				<div class="document-preview"></div>
				<div class="bb-rl-dropdown-wrap bb-rl-media-only-privacy">
					<div class="bb-document-privacy-wrap" style="display: none;">
						<span class="bp-tooltip privacy-wrap" data-bp-tooltip-pos="left" data-bp-tooltip="">
							<span class="privacy selected"></span>
						</span>
						<ul class="document-privacy">
							<?php
							if ( bp_is_active( 'media' ) && function_exists( 'bp_document_get_visibility_levels' ) ) {
								foreach ( bp_document_get_visibility_levels() as $item_key => $privacy_item ) {
									if ( 'grouponly' === $item_key ) {
										continue;
									}
									?>
									<li data-value="<?php echo esc_attr( $item_key ); ?>" class="<?php echo esc_attr( $item_key ); ?>"><?php echo esc_attr( $privacy_item ); ?></li>
									<?php
								}
							}
							?>
						</ul>
					</div>
				</div>
			</div>
			<div class="bb-media-info-section document">
				<ul class="bb-rl-activity-list bb-rl-item-list bb-rl-list">
					<span><i class="bb-rl-loader"></i></span>
				</ul>
			</div>
		</div>
	</div>
</div>
