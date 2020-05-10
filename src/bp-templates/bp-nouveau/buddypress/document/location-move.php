<?php
/**
 * BuddyBoss - Document Location Move
 *
 * @since BuddyBoss 1.0.0
 * @package BuddyBoss\Core
 */

if ( bp_is_group_document() || bp_is_group_folders() ) {
	$group_id         = bp_get_current_group_id();
	$ul = bp_document_user_document_folder_tree_view_li_html( 0, $group_id );
} elseif ( bp_is_user_document() || bp_is_user_folders() ) {
	$ul = bp_document_user_document_folder_tree_view_li_html( bp_loggedin_user_id() );
} elseif ( bp_is_document_directory() ) {
	$ul = bp_document_user_document_folder_tree_view_li_html( bp_loggedin_user_id() );
}

?>
<div class="bb-dropdown-wrap">
	<div class="location-folder-list-wrap-main <?php echo wp_is_mobile() ? 'is-mobile' : ''; ?>">
		<input type="hidden" class="bb-folder-destination" value="<?php esc_html_e( 'Select Folder', 'buddyboss' ); ?>" readonly/>
		<div class="location-folder-list-wrap">
			<span class="location-folder-back"><i class="bb-icon-angle-right"></i></span>
			<span class="location-folder-title"><?php esc_html_e( 'Documents', 'buddyboss' ); ?></span>
			<?php
			if ( '' !== $ul ) {
				echo wp_kses_post( $ul );
			}
			?>
		</div> <!-- .location-folder-list-wrap -->
		<div class="ac_document_search_folder_list" style="display: none;">
			<ul class="location-folder-list"></ul>
		</div>
		<input type="hidden" class="bb-folder-selected-id" value="0" readonly/>
	</div>
</div>
