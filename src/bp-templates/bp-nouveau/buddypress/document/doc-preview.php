<?php
/**
 * The template for activity document doc preview
 *
 * This template can be overridden by copying it to yourtheme/buddypress/document/doc-preview.php.
 *
 * @since   BuddyBoss 1.7.0
 * @package BuddyBoss\Core
 * @version 1.7.0
 */

$bp_document_image_preview = apply_filters( 'bp_document_image_preview', true );
$attachment_url            = bp_get_document_attachment_image_activity_thumbnail();
if (
	$attachment_url &&
	$bp_document_image_preview &&
	! in_array( bp_get_document_extension(), bp_get_document_preview_code_extensions(), true ) && // exclude file extension.
	! in_array( bp_get_document_extension(), bp_get_document_preview_music_extensions(), true ) // exclude audio extension.
 ) {
	?>
	<div class="document-preview-wrap">
		<img src="<?php echo esc_url( bp_get_document_attachment_image_activity_thumbnail() ); ?>" alt="" />
	</div><!-- .document-preview-wrap -->
	<?php
}
