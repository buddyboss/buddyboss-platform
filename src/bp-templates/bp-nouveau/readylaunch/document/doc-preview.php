<?php
/**
 * The template for activity document doc preview
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @version 1.0.0
 */

$bp_document_image_preview = apply_filters( 'bp_document_image_preview', true );
$attachment_url            = bp_get_document_attachment_image_activity_thumbnail();
$bp_get_document_extension = bp_get_document_extension();
if (
	$attachment_url &&
	$bp_document_image_preview &&
	! in_array( $bp_get_document_extension, bp_get_document_preview_code_extensions(), true ) && // exclude file extension.
	! in_array( $bp_get_document_extension, bp_get_document_preview_music_extensions(), true ) // exclude audio extension.
) {
	?>
	<div class="document-preview-wrap">
		<img src="<?php echo esc_url( $attachment_url ); ?>" alt="" />
	</div><!-- .document-preview-wrap -->
	<?php
}
