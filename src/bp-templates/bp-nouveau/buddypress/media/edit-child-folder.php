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

<div id="bp-media-edit-child-folder" style="display: none;">
    <transition name="modal">
        <div class="modal-mask bb-white bbm-model-wrap">
            <div class="modal-wrapper">
                <div id="boss-media-create-album-popup" class="modal-container">

                    <header class="bb-model-header">
                        <h4><?php _e( 'Edit Folder', 'buddyboss' ); ?> '<?php  bp_album_title(); ?>'</h4>
                        <a class="bb-model-close-button" id="bp-media-edit-folder-close" href="#"><span class="dashicons dashicons-no-alt"></span></a>
                    </header>

                    <div class="bb-field-wrap">
                        <label for="bb-album-child-title" class="bb-label"><?php _e( 'Rename Folder', 'buddyboss' ); ?></label>
                        <input id="bb-edit-album-child-title" type="text" value="<?php  bp_album_title(); ?>" placeholder="<?php _e( 'Enter Folder Title', 'buddyboss' ); ?>" />
                    </div>

                    <footer class="bb-model-footer">
                        <?php if ( ! bp_is_group() ) : ?>

                            <div class="bb-field-wrap">
                                <div class="bb-dropdown-wrap">
                                    <label for="bb-folder-location" class="bb-label">Move To</label>
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
                        <a class="button" id="bp-media-edit-child-folder-submit" href="#"><?php _e( 'Save', 'buddyboss' ); ?></a>
                    </footer>

                </div>
            </div>
        </div>
    </transition>
</div>
