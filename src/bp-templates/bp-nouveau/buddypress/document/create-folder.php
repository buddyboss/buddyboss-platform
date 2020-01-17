<?php
/**
 * BuddyBoss - Media Albums Create
 *
 * @since BuddyBoss 1.0.0
 */
?>

<div id="bp-media-create-folder" style="display: none;">
    <transition name="modal">
        <div class="modal-mask bb-white bbm-model-wrap">
            <div class="modal-wrapper">
                <div id="boss-media-create-album-popup" class="modal-container">

                    <header class="bb-model-header">
                        <h4><?php _e( 'Create Folder', 'buddyboss' ); ?></h4>
                        <a class="bb-model-close-button" id="bp-media-create-folder-close" href="#"><span class="dashicons dashicons-no-alt"></span></a>
                    </header>

                    <div class="bb-field-wrap">
                        <label for="bb-album-title" class="bb-label"><?php _e( 'Title', 'buddyboss' ); ?></label>
                        <input id="bb-album-title" type="text" placeholder="<?php _e( 'Enter Folder Title', 'buddyboss' ); ?>" />
                    </div>

                    <div class="bb-field-wrap">
                        <div class="media-uploader-wrapper">
                            <div class="dropzone" id="media-uploader-folder"></div>
                        </div>
                    </div>

                    <footer class="bb-model-footer">
                        <?php if ( ! bp_is_group() ) : ?>

                            <div class="bb-field-wrap">
                                <div class="bb-dropdown-wrap">
                                    <?php $privacy_options = BP_Document_Privacy::instance()->get_visibility_options(); ?>
                                    <select id="bb-folder-privacy">
                                        <?php foreach ( $privacy_options as $k => $option ) {
                                            ?>
                                            <option value="<?php echo $k; ?>"><?php echo $option; ?></option>
                                            <?php
                                        } ?>
                                    </select>
                                </div>
                            </div>

	                        <?php
                            $ul = bp_document_user_document_folder_tree_view_li_html( bp_loggedin_user_id() );
                            if ( '' !== $ul ) {
	                            ?>
	                            <div class="bb-field-wrap">
	                            <div class="bb-dropdown-wrap">
		                            <label for="bb-folder-location" class="bb-label"><?php _e( 'Destination Folder', 'buddyboss' ); ?></label>

                                    <div class="location-folder-list-wrap-main">
                                        <input type="text" class="bb-folder-destination" value="<?php _e( 'Select Folder', 'buddyboss' ); ?>" readonly/>

                                        <div class="location-folder-list-wrap">
                                            <span class="location-folder-back"><i class="dashicons dashicons-arrow-left-alt2"></i></span>
                                            <span class="location-folder-title"><?php _e( 'Documents', 'buddyboss' ); ?></span>
                                            <?php echo $ul; ?>
                                        </div><!-- .location-folder-list-wrap -->
                                        <input type="hidden" class="bb-folder-selected-id" value="" readonly/>
                                    </div><!-- .location-folder-list-wrap-main -->
	                            </div>
	                            </div><?php
                            }
	                        ?>

                        <?php endif; ?>
                        <a class="button" id="bp-media-create-folder-submit" href="#"><?php _e( 'Create Folder', 'buddyboss' ); ?></a>
                    </footer>

                </div>
            </div>
        </div>
    </transition>
</div>
