<?php
/**
 * BuddyBoss - Document Location Move
 *
 * @since BuddyBoss 1.4.0
 * @package BuddyBoss\Core
 */


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
		</div> <!-- .location-album-list-wrap -->
		<input type="hidden" class="bb-media-create-from" value="profile" readonly/>
		<input type="hidden" class="bb-media-selected-id" value="0" readonly/>
	</div>
</div>
