<?php
/**
 * BuddyBoss - Document Activity Folder Move
 *
 * @since BuddyBoss 1.0.0
 * @package BuddyBoss\Core
 */

?>
<div class="bp-media-move-file" style="display: none;" id="bp-media-move-file-<?php bp_document_id(); ?>" data-activity-id="">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap">
			<div class="modal-wrapper">
				<div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI">
					<header class="bb-model-header">
						<h4><?php esc_html_e( 'Move ', 'buddyboss' ); ?> <span class="target_name"></span> <?php esc_html_e( ' to ', 'buddyboss' ); ?><span class="target_folder">...</span></h4>
					</header>
					<?php
						$ul = bp_document_user_document_folder_tree_view_li_html( bp_loggedin_user_id() );
					?>
					<div class="bb-field-wrap bb-field-wrap-search">
						<input type="text" class="ac_document_search_folder" value="" placeholder="<?php esc_html_e( 'Search Folder', 'buddyboss' ); ?>" />
					</div>
					<div class="bb-field-wrap">
						<div class="bb-dropdown-wrap">
							<div class="location-folder-list-wrap-main <?php echo wp_is_mobile() ? 'is-mobile' : ''; ?>">
								<input type="hidden" class="bb-folder-destination" value="<?php esc_html_e( 'Select Folder', 'buddyboss' ); ?>" readonly/>
								<div class="location-folder-list-wrap">
									<span class="location-folder-back"><i class="bb-icon-angle-left"></i></span>
									<span class="location-folder-title"><?php esc_html_e( 'Documents', 'buddyboss' ); ?></span>
									<?php
									if ( '' !== $ul ) {
										echo wp_kses_post( $ul );
									} else {
										?>
											<ul class="location-folder-list">
												<li data-id="0">
													<span class="selected disabled"><?php esc_html_e( 'Documents', 'buddyboss' ); ?></span>
												</li>
											</ul>
										<?php
									}
									?>
								</div> <!-- .location-folder-list-wrap -->
								<div class="ac_document_search_folder_list" style="display: none;">
									<ul class="location-folder-list"></ul>
								</div>
								<input type="hidden" class="bb-folder-selected-id" value="" readonly/>
							</div>
						</div>
					</div>
					<footer class="bb-model-footer">
						<a class="ac-document-close-button" href="#"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
						<a class="button bp-document-move bp-document-move-activity" id="<?php bp_document_id(); ?>" href="#"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div>
