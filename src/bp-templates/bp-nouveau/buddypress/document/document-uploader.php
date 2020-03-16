<?php
/**
 * BuddyBoss - Media Uploader
 *
 * @since BuddyBoss 1.0.0
 */
?>
<div id="bp-media-uploader" class="bp-media-document-uploader" style="display: none;">
    <transition name="modal">
        <div class="modal-mask bb-white bbm-model-wrap bbm-uploader-model-wrap">
            <div class="modal-wrapper">
                <div class="modal-container has-folderlocationUI">

                    <header class="bb-model-header bg-white">
                        <a href="#" class="bp-media-upload-tab selected" data-content="bp-dropzone-content" id="bp-media-uploader-modal-title"><?php esc_html_e( 'Upload', 'buddyboss' ); ?></a>

                        <span id="bp-media-uploader-modal-status-text" style="display: none;"></span>

                        <a class="bb-model-close-button" id="bp-media-uploader-close" href="#">
							<span class="dashicons dashicons-no-alt"></span>
						</a>
                    </header>

                    <div class="bb-field-steps bb-field-steps-1">

                        <div class="bb-field-wrap">
                            <div class="bb-dropzone-wrap bp-media-upload-tab-content" id="bp-dropzone-content">
                                <?php if ( bp_is_active('forums') && ! bbp_is_single_forum() && ! bbp_is_single_topic() && ! bp_is_messages_component() ) : ?>
                                <div class="media-uploader-post-content">
                                    <textarea name="bp-media-post-content" id="bp-media-post-content" placeholder="<?php bp_is_group() ? esc_html_e( 'Write something about your documents, to be shown on the group feed', 'buddyboss' ) : _e( 'Write something about your documents, to be shown on your timeline', 'buddyboss' ); ?>"></textarea>
                                </div>
                                <?php endif; ?>
                                <div class="media-uploader-wrapper">
                                    <div class="dropzone" id="media-uploader"></div>
                                </div>
                            </div>
                        </div>

                        <a id="bp-media-document-next" class="button bb-field-uploader-next bb-field-uploader-actions pull-right" href="#"><?php esc_html_e( 'Next', 'buddyboss' ); ?></a>

                    </div>

                    <div class="bb-field-steps bb-field-steps-2">

                        <?php
                        $ul = bp_document_user_document_folder_tree_view_li_html( bp_loggedin_user_id() );
                        if ( '' !== $ul ) {
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
                                            <?php echo $ul; ?>
                                        </div> <!-- .location-folder-list-wrap -->
                                        <div class="ac_document_search_folder_list" style="display: none;">
                                            <ul class="location-folder-list"></ul>
                                        </div>
                                        <input type="hidden" class="bb-folder-selected-id" value="" readonly/>
                                    </div>
                                </div>
                            </div><?php
                        }
                        ?>

                    </div>

                    <footer class="flex align-items-center bb-model-footer">
                        <a class="button outline" id="bp-media-document-add-more" style="display: none;" href="#">+ <?php esc_html_e( 'Add more documents', 'buddyboss' ); ?></a>
                        <a id="bp-media-document-prev" class="button bb-uploader-steps-prev bb-field-uploader-actions" href="#"><?php esc_html_e( 'previous', 'buddyboss' ); ?></a>
                        <a class="button push-right" id="bp-media-document-submit" style="display: none;" href="#"><?php esc_html_e( 'Done', 'buddyboss' ); ?></a>
                    </footer>

                </div>
            </div>
        </div>
    </transition>
</div>
