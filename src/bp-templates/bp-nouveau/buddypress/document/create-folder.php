<?php
/**
 * BuddyBoss - Document Folder Create
 *
 * @since BuddyBoss 1.0.0
 * @package BuddyBoss\Core
 */

?>

<div id="bp-media-create-folder" style="display: none;">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap">
			<div class="modal-wrapper">
				<div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI">
					<header class="bb-model-header">
						<h4><?php esc_html_e( 'Create Folder', 'buddyboss' ); ?></h4>
						<a class="bb-model-close-button" id="bp-media-create-folder-close" href="#"><span class="dashicons dashicons-no-alt"></span></a>
					</header>
					<div class="bb-field-steps bb-field-steps-1">
						<div class="bb-field-wrap">
							<label for="bb-album-title" class="bb-label"><?php esc_html_e( 'Title', 'buddyboss' ); ?></label>
							<input id="bb-album-title" type="text" placeholder="<?php esc_html_e( 'Enter Folder Title', 'buddyboss' ); ?>" />
						</div>
						<div class="bb-field-wrap">
							<div class="media-uploader-wrapper">
								<div class="dropzone" id="media-uploader-folder"></div>
							</div>
						</div>
						<?php
						if ( ! bp_is_group() ) :
							?>
							<div class="bb-field-wrap">
								<label for="bb-folder-privacy" class="bb-label"><?php esc_html_e( 'Privacy', 'buddyboss' ); ?></label>
								<div class="bb-dropdown-wrap">
									<select id="bb-folder-privacy">
										<?php
										foreach ( bp_document_get_visibility_levels() as $key => $privacy ) :
											if ( 'grouponly' === $key ) {
												continue;
											}
											?>
											<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $privacy ); ?></option>
											<?php
										endforeach;
										?>
									</select>
								</div>
							</div>
							<?php
						endif;
						?>
						<a class="button bb-field-steps-next bb-field-steps-actions" href="#"><?php esc_html_e( 'Next', 'buddyboss' ); ?></a>
					</div>
					<div class="bb-field-steps bb-field-steps-2">
						<?php
							$ul = bp_document_user_document_folder_tree_view_li_html( bp_loggedin_user_id() );
						?>
						<label for="bb-album-child-title" class="bb-label"><?php esc_html_e( 'Destination Folder', 'buddyboss' ); ?></label>
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
									<input type="hidden" class="bb-folder-selected-id" value="0" readonly/>
								</div>
							</div>
						</div>
						<footer class="bb-model-footer">
							<a class="button bb-field-steps-previous bb-field-steps-actions" href="#"><?php esc_html_e( 'Previous', 'buddyboss' ); ?></a>
							<a class="button" id="bp-media-create-folder-submit" href="#"><?php esc_html_e( 'Create Folder', 'buddyboss' ); ?></a>
						</footer>
					</div>
				</div>
			</div>
		</div>
	</transition>
</div>
