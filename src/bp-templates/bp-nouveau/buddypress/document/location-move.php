<?php
/**
 * The template for document location move
 *
 * This template can be overridden by copying it to yourtheme/buddypress/document/location-move.php.
 *
 * @since   BuddyBoss 1.4.0
 * @package BuddyBoss\Core
 * @version 1.4.0
 */

$ul = '';
if ( bp_is_group_document() || bp_is_group_folders() ) {
	$group_id = bp_get_current_group_id();
	$ul       = bp_document_user_document_folder_tree_view_li_html( 0, $group_id );
} elseif ( bp_is_user_document() || bp_is_user_folders() ) {
	$ul = bp_document_user_document_folder_tree_view_li_html( bp_loggedin_user_id() );
} elseif ( bp_is_document_directory() ) {
	$ul = bp_document_user_document_folder_tree_view_li_html( bp_loggedin_user_id() );
}

?>
<div class="bb-dropdown-wrap">
	<div class="location-folder-list-wrap-main <?php echo wp_is_mobile() ? 'is-mobile' : ''; ?>">
		<span class="no-folder-exists" style="display: none;"><?php esc_html_e( 'No folders found. Please create and select folder.', 'buddyboss' ); ?></span>

		<input type="hidden" class="bb-folder-destination" value="<?php esc_html_e( 'Select Folder', 'buddyboss' ); ?>" readonly/>
		<div class="location-folder-list-wrap">
			<h4><span class="where-to-move-profile-or-group-document"></span></h4>
			<div class="breadcrumbs-append-ul-li">
				<div class="breadcrumb">
					<div class="item">
						<span data-id="0"><?php esc_html_e( 'Documents', 'buddyboss' ); ?></span>
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
		<input type="hidden" class="bb-folder-create-from" value="profile" readonly/>
		<input type="hidden" class="bb-folder-selected-id" value="<?php echo bp_get_document_folder_id() ? esc_attr( bp_get_document_folder_id() ) : esc_attr( '0' ); ?>" data-value="<?php echo bp_get_document_folder_id() ? esc_attr( bp_get_document_folder_id() ) : esc_attr( '0' ); ?>" readonly/>
	</div>
</div>
