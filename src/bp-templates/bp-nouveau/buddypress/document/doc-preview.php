<?php
/**
 * BuddyBoss - Activity Document Doc Preview
 *
 * @since BuddyBoss 1.5.7
 * @package BuddyBoss\Core
 */

if ( in_array( bp_get_document_extension(), bp_get_document_preview_doc_extensions(), true ) ) {
	?>
	<div class="document-preview-wrap">
		<img src="<?php bp_document_attachment_image_activity_thumbnail(); ?>" alt="" />
	</div><!-- .document-preview-wrap -->
	<?php
}
