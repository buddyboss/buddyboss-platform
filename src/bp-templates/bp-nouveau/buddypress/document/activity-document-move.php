<?php
/**
 * BuddyBoss - Document Activity Folder Move
 *
 * @since BuddyBoss 1.0.0
 * @package BuddyBoss\Core
 */

$group_id  = bp_get_document_group_id();
$parent_id = bp_get_document_parent_id();
if ( $group_id > 0 ) {
	$move_id   = $group_id;
	$move_type = 'group';
} else {
	$move_id   = bp_get_document_user_id();
	$move_type = 'profile';
}

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
							<a href="#" class="bp-document-open-create-popup-folder"><?php esc_html_e( 'Create Folder', 'buddyboss' ); ?></a>
							<div class="location-folder-list-wrap-main <?php echo wp_is_mobile() ? 'is-mobile' : ''; ?>">
								<input type="hidden" class="bb-folder-destination" value="<?php esc_html_e( 'Select Folder', 'buddyboss' ); ?>" readonly/>
								<div class="location-folder-list-wrap">
									<span class="location-folder-back"><i class="bb-icon-angle-left"></i></span>
									<span class="location-folder-title"><?php esc_html_e( 'Documents', 'buddyboss' ); ?></span>
								</div> <!-- .location-folder-list-wrap -->
								<div class="ac_document_search_folder_list" style="display: none;">
									<ul class="location-folder-list"></ul>
								</div>
								<input type="hidden" class="bb-folder-selected-id" value="0" readonly/>
							</div>
							<div class="create-popup-folder-wrap" style="display: none;">
								<input class="" type="text" placeholder="<?php esc_attr_e( 'Folder name', 'buddyboss' ); ?>" id="new_folder_name_input">
								<input type="hidden" value="<?php echo esc_attr( $move_id ); ?>" class="where-to-id">
								<input type="hidden" value="<?php echo esc_attr( $move_type ); ?>" class="where-to-create">
								<input type="hidden" value="0" class="where-to-parent">
								<div class="db-modal-buttons">
									<a class="close-create-popup-folder" href="#"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
									<a class="button bp-document-create-popup-folder-submit" href="#"><?php esc_html_e( 'Create', 'buddyboss' ); ?></a>
								</div>
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
