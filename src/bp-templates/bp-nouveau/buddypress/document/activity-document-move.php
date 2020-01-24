<?php
/**
 * BuddyBoss - Media Albums Create
 *
 * @since BuddyBoss 1.0.0
 */
?>

<div class="bp-media-move-file" style="display: none;" id="bp-media-move-file-<?php bp_document_id(); ?>" data-activity-id="<?php  ?>">
    <transition name="modal">
        <div class="modal-mask bb-white bbm-model-wrap">
            <div class="modal-wrapper">
                <div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI">

                    <header class="bb-model-header">
	                    <h4><?php _e( 'Move ', 'buddyboss' ); ?> <span class="target_name"></span> <?php _e( ' to ', 'buddyboss' ); ?><span class="target_folder">...</span></h4>
                    </header>
                    <?php
                    if ( 0 === bp_get_document_group_id() ) {
	                    $ul = bp_document_user_document_folder_tree_view_li_html( bp_loggedin_user_id() );
                    } else {
	                    $ul = bp_document_user_document_folder_tree_view_li_html( bp_loggedin_user_id(), bp_get_document_group_id() );
                    }

                    if ( '' !== $ul ) {
	                    ?>
	                    <div class="bb-field-wrap bb-field-wrap-search">
		                    <input type="text" class="ac_document_search_folder" value="" placeholder="<?php _e( 'Search Folder', 'buddyboss' ); ?>" />
	                    </div>
	                    <div class="bb-field-wrap">
	                    <div class="bb-dropdown-wrap">
		                    <div class="location-folder-list-wrap-main <?php echo wp_is_mobile() ? 'is-mobile' : ''; ?>">
			                    <input type="hidden" class="bb-folder-destination" value="<?php _e( 'Select Folder', 'buddyboss' ); ?>" readonly/>
			                    <div class="location-folder-list-wrap">
				                    <span class="location-folder-back"><i class="bb-icon-angle-left"></i></span>
				                    <span class="location-folder-title"><?php _e( 'Documents', 'buddyboss' ); ?></span>
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

                    <footer class="bb-model-footer">
                        <a class="ac-document-close-button" href="#"><?php _e( 'Cancel', 'buddyboss' ); ?></a>
                        <a class="button bp-document-move bp-document-move-activity" id="<?php bp_document_id(); ?>" href="#"><?php _e( 'Move', 'buddyboss' ); ?></a>
                    </footer>

                </div>
            </div>
        </div>
    </transition>
</div>
