<?php
/**
 * BuddyBoss - Activity Document Doc Preview
 *
 * @since BuddyBoss 1.5.7
 * @package BuddyBoss\Core
 */

$bp_document_image_preview = apply_filters( 'bp_document_image_preview', true );
$attachment_url            = bp_document_get_preview_image_url( bp_get_document_id(), bp_get_document_extension(), bp_get_document_preview_attachment_id() );
if ( $attachment_url && $bp_document_image_preview ) {
	?>
	<div class="document-preview-wrap">
		<img src="<?php echo esc_url( $attachment_url ); ?>" alt="" />
	</div><!-- .document-preview-wrap -->
	<?php
}