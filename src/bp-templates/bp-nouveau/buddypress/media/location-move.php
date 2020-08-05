<?php
/**
 * BuddyBoss - Document Location Move
 *
 * @since BuddyBoss 1.4.0
 * @package BuddyBoss\Core
 */

$ul  = '';
//if ( bp_is_group_media() || bp_is_group_albums() ) {
//	$group_id         = bp_get_current_group_id();
//	$ul = bp_media_user_media_album_tree_view_li_html( 0, $group_id );
//} elseif ( bp_is_user_media() ) {
//	$ul = bp_media_user_media_album_tree_view_li_html( bp_loggedin_user_id() );
//} elseif ( bp_is_media_directory() ) {
//	$ul = bp_media_user_media_album_tree_view_li_html( bp_loggedin_user_id() );
//}
?>
<div class="bb-dropdown-wrap">
	<div class="location-album-list-wrap-main <?php echo wp_is_mobile() ? 'is-mobile' : ''; ?>">
		<span class="no-album-exists" style="display: none;"><?php esc_html_e( 'You have not created any album yet to move this photo into.', 'buddyboss' ); ?></span>

		<input type="hidden" class="bb-album-destination" value="<?php esc_html_e( 'Select Album', 'buddyboss' ); ?>" readonly/>
		<div class="location-album-list-wrap">
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
		</div> <!-- .location-album-list-wrap -->
		<input type="hidden" class="bb-media-create-from" value="profile" readonly/>
		<input type="hidden" class="bb-media-selected-id" value="0" readonly/>
	</div>
</div>
