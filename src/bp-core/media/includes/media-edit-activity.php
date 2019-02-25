<?php
/**
 * @package WordPress
 * @subpackage BuddyBoss Media
 *
 * BuddyPress Edit Activity and BuddyBoss Media compatibility
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Conjure up media edit preview for activity edit
 * and show in beneath the textarea so user can remove it
 */
function bb_buddypress_edit_activity_media_content() {

	$activity_id = $_REQUEST['activity_id'];
	$buddyboss_media_ids = bp_activity_get_meta( $activity_id, 'buddyboss_media_aid', true );

	if ( empty( $buddyboss_media_ids ) ) die(); ?>

	<?php foreach( $buddyboss_media_ids as $media_id ): ?>
		<div class="file" data-media-id="<?php echo $media_id ?>">
			<img src="<?php echo wp_get_attachment_image_url( $media_id ); ?>"><a onclick="return window.BuddyBoss_Edit_Media_Uploader.editActivityRemoveMedia(this);" class="delete">+</a>
		</div>
	<?php endforeach; ?>

	<?php

	die();
}

add_action( 'wp_ajax_buddypress_edit_activity_media_content', 'bb_buddypress_edit_activity_media_content' );

/**
 * Update activity media
 */
function bb_buddypress_save_activity_media() {
	$activity_id = $_REQUEST['activity_id'];
	$buddyboss_media_ids = $_REQUEST['buddyboss_media_aid'];

	bp_activity_update_meta( $activity_id, 'buddyboss_media_aid', $buddyboss_media_ids );

}
add_action( 'bea_before_save_activity_content', 'bb_buddypress_save_activity_media' );

/**
 * Append media html markup in activity content for backward compatibility
 * in case user deactivate the media plugin
 * @param $content
 * @param $activity_id
 * @return string
 */
function bea_activity_content( $content, $activity_id  ) {
	$buddyboss_media_ids = $_REQUEST['buddyboss_media_aid'];
	$content .= bbm_generate_media_activity_content( $buddyboss_media_ids );
	return $content;
}
add_filter( 'bea_activity_content', 'bea_activity_content', 10, 2 );

function buddyboss_media_print_edit_media_wrapper() {
	?>
	<div id="buddyboss-edit-media-add-photo">

		<!-- Fake add photo button will be clicked from js -->
		<button type="button" class="buddyboss-activity-media-add-photo-button" id="buddyboss-media-open-uploader-button" style="display:none;"></button>
		<button type="button" id="browse-file-button" class="browse-file-button buddyboss-edit-media-add-photo-button"></button>

		<div class="buddyboss-media-progress">
			<div class="buddyboss-media-progress-value">0%</div>
			<progress class="buddyboss-media-progress-bar" value="0" max="100"></progress>
		</div>

		<div id="buddyboss-media-photo-uploader"></div>
	</div><!-- #buddyboss-media-add-photo -->
	<?php
}

add_action( 'bb_before_print_edit_activity_template', 'buddyboss_media_print_edit_media_wrapper', 10 );

/**
 * A template for add new media while editing an activity
 * Add media wrapper inside edit activity form
 * @todo need to unneeded html from add new media wrapper
 */
function buddyboss_media_print_add_media_wrapper() {
	?>

	<div id="buddyboss-edit-media-preview">
		<div class="clearfix" id="buddyboss-edit-media-preview-inner">

		</div>
	</div><!-- #buddyboss-media-preview -->

	<div id="buddyboss-edit-media-bulk-uploader-wrapper" style="display:none">
		<div id="buddyboss-edit-media-bulk-uploader">
			<div id="buddyboss-edit-media-bulk-uploader-uploaded">

				<div class="images clearfix">

				</div>
			</div>
			<div id="buddyboss-edit-media-bulk-uploader-reception" class="image-drop-box">
				<h3 class="buddyboss-media-drop-instructions"><?php _e( 'Drop files anywhere to upload', 'buddyboss-media' ); ?></h3>
				<p class="buddyboss-media-drop-separator"><?php _e( 'or', 'buddyboss-media' ); ?></p>
				<a id="logo-file-browser-button" id="browse-file-button" title="Select image" class="browse-file-button" href="#"> <?php _e( 'Select Files', 'buddyboss-media' ); ?></a>
			</div>

		</div>
	</div><!-- buddyboss-edit-media-bulk-uploader-wrapper -->
	<?php
}

add_action( 'bb_after_print_edit_activity_template', 'buddyboss_media_print_add_media_wrapper', 10 );

/**
 * Remove media <a/> html from activity content
 * so it won't appear in edit activity content textarea
 * @param $content
 * @return mixed
 */
function bb_get_activity_content( $content ) {
	$content = preg_replace('#<a[^>]+class="buddyboss-media-photo-link"[^>]*>.*?</a>#is', '', $content );

	return $content;
}
add_filter( 'bea_get_activity_content', 'bb_get_activity_content' );
