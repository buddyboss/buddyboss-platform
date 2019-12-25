<?php
/**
 * BuddyBoss - Media Albums Create
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php
global $media_album_template;
$album_id = 0;
if  ( function_exists( 'bp_is_group_single' ) && bp_is_group_single() && bp_is_group_document_folder() ) {
	$action_variables = bp_action_variables();
	$album_id = (int) $action_variables[1];
} else  {
	$album_id = (int) bp_action_variable( 0 );
}

?>

<div id="bp-media-create-child-folder" style="display: none;">
    <transition name="modal">
        <div class="modal-mask bb-white bbm-model-wrap">
            <div class="modal-wrapper">
                <div id="boss-media-create-album-popup" class="modal-container">

                    <header class="bb-model-header">
                        <h4><?php _e( 'Create Folder', 'buddyboss' ); ?></h4>
                        <a class="bb-model-close-button" id="bp-media-create-folder-close" href="#"><span class="dashicons dashicons-no-alt"></span></a>
                    </header>

                    <div class="bb-field-wrap">
                        <label for="bb-album-child-title" class="bb-label"><?php _e( 'Title', 'buddyboss' ); ?></label>
                        <input id="bb-album-child-title" type="text" placeholder="<?php _e( 'Enter Folder Title', 'buddyboss' ); ?>" />
                    </div>

                    <div class="bb-field-wrap">
                        <div class="media-uploader-wrapper">
                            <div class="dropzone" id="media-uploader-child-folder"></div>
                        </div>
                    </div>

                    <footer class="bb-model-footer">
                        <?php if ( ! bp_is_group() ) : ?>

                            <div class="bb-field-wrap">
                                <div class="bb-dropdown-wrap">
                                    <?php $privacy_options = BP_Media_Privacy::instance()->get_visibility_options(); ?>
                                    <select id="bb-folder-child-privacy">
                                        <?php foreach ( $privacy_options as $k => $option ) {
                                            ?>
                                            <option value="<?php echo $k; ?>"><?php echo $option; ?></option>
                                            <?php
                                        } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="bb-field-wrap">
                                <div class="bb-dropdown-wrap">
                                    <label for="bb-folder-location" class="bb-label">Destination Folder</label>
                                    <select id="bb-folder-location">
                                        <option class="folder_l_1" value="folder">Documents</option>
                                        <option class="folder_l_1_1" value="sub_folder">folder</option>
                                        <option class="folder_l_1_1" value="sub_sub_folder">subfolder</option>
                                        <option class="folder_l_1_1" value="sub_sub_folder">subfolder</option>
                                        <option class="folder_l_1_1_1" value="sub_sub_sub_folder">subsubfolder</option>
                                        <option class="folder_l_1" value="sub_folder">folder</option>
                                        <option class="folder_l_1_1" value="sub_sub_folder">subfolder</option>
                                        <option class="folder_l_1_1" value="sub_sub_folder">subfolder</option>
                                    </select>
                                </div>
                            </div>

                        <?php endif; ?>
	                    <input type="hidden" class="parent_id" id="parent_id" name="parent_id" value="<?php echo esc_attr( $album_id ); ?>">
                        <a class="button" id="bp-media-create-child-folder-submit" href="#"><?php _e( 'Create Folder', 'buddyboss' ); ?></a>
                    </footer>

                </div>
            </div>
        </div>
    </transition>
</div>
