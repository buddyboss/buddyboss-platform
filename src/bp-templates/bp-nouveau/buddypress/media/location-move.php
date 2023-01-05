<?php
/**
 * The template for media location move
 *
 * This template can be overridden by copying it to yourtheme/buddypress/media/location-move.php.
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Core
 * @version 1.5.6
 */

?>
<div class="bb-dropdown-wrap">
	<div class="location-album-list-wrap-main <?php echo wp_is_mobile() ? 'is-mobile' : ''; ?>">
		<span class="no-album-exists" style="display: none;"><?php esc_html_e( 'No albums found. Please create and select album.', 'buddyboss' ); ?></span>

		<input type="hidden" class="bb-album-destination" value="<?php esc_html_e( 'Select Album', 'buddyboss' ); ?>" readonly/>
		<div class="location-album-list-wrap">
			<h4><span class="where-to-move-profile-or-group-media"></span></h4>
			<div class="breadcrumbs-append-ul-li">
				<div class="breadcrumb">
					<div class="item">
						<span data-id="0"><?php esc_html_e( 'Albums', 'buddyboss' ); ?></span>
					</div>
				</div>
			</div>
		</div> <!-- .location-album-list-wrap -->
		<input type="hidden" class="bb-album-create-from" value="profile" readonly/>
		<input type="hidden" class="bb-album-selected-id" value="<?php echo bp_get_album_id() ? esc_attr( bp_get_album_id() ) : '0'; ?>" data-value="<?php echo bp_get_album_id() ? esc_attr( bp_get_album_id() ) : '0'; ?>" readonly/>
	</div>
</div>
