<?php
/**
 * BuddyBoss - Activity Document Doc Preview
 *
 * @since BuddyBoss 1.5.7
 * @package BuddyBoss\Core
 */

$bp_document_image_preview = apply_filters( 'bp_document_image_preview', true );
$attachment_url            = bp_get_document_attachment_image_activity_thumbnail();
if ( $attachment_url && $bp_document_image_preview && ! in_array( bp_get_document_extension(), bp_get_document_preview_code_extensions(), true ) ) {
	?>
	<div class="document-preview-wrap">
		<img src="<?php echo esc_url( bp_get_document_attachment_image_activity_thumbnail() ); ?>" alt="" />
	</div><!-- .document-preview-wrap -->
	<?php
}