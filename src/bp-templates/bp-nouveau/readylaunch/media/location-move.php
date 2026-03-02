<?php
/**
 * ReadyLaunch - Location Move template.
 *
 * This template handles the location/album selection for moving media.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$bp_album_id = bp_get_album_id();
$bp_album_id = ! empty( $bp_album_id ) ? $bp_album_id : 0;
?>
<div class="bb-rl-dropdown-wrap">
	<div class="bb-rl-location-album-list-wrap-main <?php echo wp_is_mobile() ? 'is-mobile' : ''; ?>">
		<span class="bb-rl-no-album-exists" style="display: none;"><?php esc_html_e( 'No albums found. Please create and select album.', 'buddyboss' ); ?></span>

		<input type="hidden" class="bb-rl-album-destination" value="<?php esc_html_e( 'Select Album', 'buddyboss' ); ?>" readonly />
		<div class="bb-rl-location-album-list-wrap">
			<h4><span class="bb-rl-where-to-move-profile-or-group-media"></span></h4>
			<div class="bb-rl-breadcrumbs-append-ul-li">
				<div class="breadcrumb">
					<div class="item">
						<span data-id="0"><?php esc_html_e( 'Albums', 'buddyboss' ); ?></span>
					</div>
				</div>
			</div>
		</div> <!-- .bb-rl-location-album-list-wrap -->
		<input type="hidden" class="bb-rl-album-create-from" value="profile" readonly />
		<input type="hidden" class="bb-rl-album-selected-id" value="<?php echo esc_attr( $bp_album_id ); ?>" data-value="<?php echo esc_attr( $bp_album_id ); ?>" readonly />
	</div>
</div>
