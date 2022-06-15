<?php
/**
 * BuddyBoss - Video Create Album
 *
 * This template can be overridden by copying it to yourtheme/buddypress/video/video-create-album.php.
 *
 * @since   BuddyBoss 1.7.0
 * @package BuddyBoss\Core
 * @version 1.7.0
 */

?>

<div class="create-popup-album-wrap popup-on-fly-create-album" style="display: none;">
	<div class="bb-field-wrap">
		<label for="new_album_name_input" class="bb-label"><?php esc_attr_e( 'Album Title', 'buddyboss' ); ?></label>
		<input id="new_album_name_input" class="popup-on-fly-create-album-title" value="" type="text" placeholder="<?php esc_attr_e( 'Enter Album Title', 'buddyboss' ); ?>">
	</div>

	<?php
	if ( ! bp_is_group() ) :
		bp_get_template_part( 'video/video-privacy' );
	endif;
	?>

    <div class="db-modal-buttons">
		<a class="close-create-popup-album" href="#"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
		<a class="button bp-video-create-popup-album-submit" href="#"><?php esc_html_e( 'Create', 'buddyboss' ); ?></a>
	</div>
</div>
