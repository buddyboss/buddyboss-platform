<?php
/**
 * BuddyBoss - Media Albums Create
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php

$id = bp_get_media_attachment_id();

?>

<div class="bp-media-move-file-folder" id="<?php echo 'bp-media-move-file-' . $id; ?>" style="display: none;">
    <transition name="modal">
        <div class="modal-mask bb-white bbm-model-wrap">
            <div class="modal-wrapper">
                <div id="boss-media-create-album-popup" class="modal-container">

                    <header class="bb-model-header">
                        <h4><?php _e( 'Move file', 'buddyboss' ); ?></h4>
                        <a class="ac-document-close-button" href="#"><span class="dashicons dashicons-no-alt"></span></a>
                    </header>

                    <footer class="bb-model-footer">
                        <?php if ( ! bp_is_group() ) :
                            $ul = bp_media_user_document_folder_tree_view_li_html( bp_loggedin_user_id() );
	                        if ( '' !== $ul ) {
		                        ?>
		                        <div class="bb-field-wrap">
		                        <div class="bb-dropdown-wrap">
                                    <label for="bb-folder-location" class="bb-label">Destination Folder</label>
                                    <div class="location-folder-list-wrap-main">
                                        <input type="text" class="bb-folder-destination" value="Select Folder" readonly/>
                                        <div class="location-folder-list-wrap">
                                            <span class="location-folder-back"><i class="dashicons dashicons-arrow-left-alt2"></i></span>
                                            <span class="location-folder-title">Documents</span>
                                            <?php echo $ul; ?>
                                        </div> <!-- .location-folder-list-wrap -->
                                        <input type="hidden" class="bb-folder-selected-id" value="" readonly/>
                                    </div>
		                        </div>
		                        </div><?php
	                        }
	                        ?>
                        <?php endif; ?>
                        <a class="button bp-document-move" id="<?php echo $id; ?>" href="#"><?php _e( 'Move', 'buddyboss' ); ?></a>
                    </footer>

                </div>
            </div>
        </div>
    </transition>
</div>
