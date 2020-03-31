<?php
/**
 * BuddyBoss - Activity Document Folder Move
 *
 * @since BuddyBoss 1.0.0
 */

?>

<div class="bp-media-move-folder" id="bp-media-move-folder" style="display: none;">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap">
			<div class="modal-wrapper">
				<div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI">
					<header class="bb-model-header">
						<h4><?php esc_html_e( 'Move ', 'buddyboss' ); ?> <span class="target_name"></span> <?php esc_html_e( ' to ', 'buddyboss' ); ?><span class="target_folder">...</span></h4>
					</header>
					<?php
					if ( ! bp_is_group() ) :
						$ul = bp_document_user_document_folder_tree_view_li_html( bp_loggedin_user_id() );
						if ( '' !== $ul ) {
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
											<?php echo $ul; ?>
										</div> <!-- .location-folder-list-wrap -->
										<div class="ac_document_search_folder_list" style="display: none;">
											<ul class="location-folder-list"></ul>
										</div>
										<input type="hidden" class="bb-folder-selected-id" value="" readonly/>
									</div>
								</div>
							</div>
							<?php
						}
                    endif; ?>
					<footer class="bb-model-footer">
						<a class="ac-folder-close-button" href="#"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
						<a class="button bp-folder-move" id="" href="#"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div>
