<?php
/**
 * BuddyBoss - Document Location Move
 *
 * @since BuddyBoss 1.4.0
 * @package BuddyBoss\Core
 */

$ul  = '';
if ( bp_is_group_media() || bp_is_group_albums() ) {
	$group_id         = bp_get_current_group_id();
	$ul = bp_media_user_media_folder_tree_view_li_html( 0, $group_id );
} elseif ( bp_is_user_media() ) {
	$ul = bp_media_user_media_folder_tree_view_li_html( bp_loggedin_user_id() );
} elseif ( bp_is_media_directory() ) {
	$ul = bp_media_user_media_folder_tree_view_li_html( bp_loggedin_user_id() );
} else {
	$ul = bp_media_user_media_folder_tree_view_li_html( bp_loggedin_user_id() ); // Temporary fix for Activity page
}

?>
<div class="bb-dropdown-wrap">
	<div class="location-folder-list-wrap-main <?php echo wp_is_mobile() ? 'is-mobile' : ''; ?>">
		<span class="no-folder-exists" style="display: none;"><?php esc_html_e( 'You have not created any album yet to move this photo into.', 'buddyboss' ); ?></span>

		<input type="hidden" class="bb-folder-destination" value="<?php esc_html_e( 'Select Album', 'buddyboss' ); ?>" readonly/>
		<div class="location-folder-list-wrap">
			<h4><span class="where-to-move-profile-or-group-document"></span></h4>
			<div class="breadcrumbs-append-ul-li">
				<div class="breadcrumb">
					<div class="item">
						<span data-id="0"><?php esc_html_e( 'Album', 'buddyboss' ); ?></span>
					</div>
				</div>
			</div>
			<?php
			if ( '' !== $ul ) {
				echo wp_kses_post( $ul );
			}
			?>
		</div> <!-- .location-folder-list-wrap -->
		<div class="ac_document_search_folder_list" style="display: none;">
			<ul class="location-folder-list"></ul>
		</div>
		<input type="hidden" class="bb-media-create-from" value="profile" readonly/>
		<input type="hidden" class="bb-media-selected-id" value="0" readonly/>
	</div>
</div>
