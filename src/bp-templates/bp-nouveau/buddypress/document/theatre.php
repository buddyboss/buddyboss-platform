<?php
/**
 * The template for document theatre
 *
 * This template can be overridden by copying it to yourtheme/buddypress/document/theatre.php.
 *
 * @since   BuddyBoss 1.4.0
 * @package BuddyBoss\Core
 * @version 1.4.0
 */

?>
<div class="bb-media-model-wrapper bb-internal-model document document-theatre" style="display: none;" id="buddypress">

	<a data-balloon-pos="left" data-balloon="<?php esc_html_e( 'Close', 'buddyboss' ); ?>" class="bb-close-media-theatre bb-close-model bb-close-document-theatre" href="#"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"><path fill="none" stroke="#FFF" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 1L1 13m12 0L1 1" opacity=".7"/></svg></a>

	<div id="bb-media-model-container" class="bb-media-model-container bb-document-theater">
		<div class="bb-media-model-inner">
			<div class="bb-media-section bb-document-section">

				<a class="theater-command bb-prev-document" href="#previous">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="30"><path fill="none" stroke="#FFF" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 29L1 15 15 1" opacity=".7"/></svg>
				</a>

				<a class="theater-command bb-next-document" href="#next">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="30"><path fill="none" stroke="#FFF" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1l14 14L1 29" opacity=".7"/></svg>
				</a>

				<div class="document-preview"></div>

				<div class="bb-dropdown-wrap bb-media-only-privacy">
					<div class="bb-document-privacy-wrap" style="display: none;">
						<span class="bp-tooltip privacy-wrap" data-bp-tooltip-pos="left" data-bp-tooltip=""><span class="privacy selected"></span></span>
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
				<ul class="activity-list item-list bp-list"><span><i class="bb-icon-spin5 animate-spin"></i></span></ul>
			</div>
		</div>
	</div>

</div>
