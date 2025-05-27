<?php
/**
 * ReadyLaunch - The template for activity document doc preview.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core
 * @version 1.0.0
 */

$bp_document_image_preview = apply_filters( 'bp_document_image_preview', true );
$attachment_url            = bp_get_document_attachment_image_activity_thumbnail();
$bp_get_document_extension = bp_get_document_extension();
$excluded_extensions       = array_merge( bp_get_document_preview_code_extensions(), bp_get_document_preview_music_extensions() );
if (
	$attachment_url &&
	true === $bp_document_image_preview &&
	! in_array( $bp_get_document_extension, (array) $excluded_extensions, true ) // exclude file and audio extension.
) { ?>
	<div class="bb-rl-document-preview-wrap">
		<img src="<?php echo esc_url( $attachment_url ); ?>" alt="" />
	</div><!-- .bb-rl-document-preview-wrap -->
	<?php
}
