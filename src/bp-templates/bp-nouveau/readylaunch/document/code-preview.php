<?php
/**
 * ReadyLaunch - The template for activity document code preview
 *
 * @since   BuddyBoss [BBVERSION]
 * @var string $download_url Download Url.
 *
 * @package BuddyBoss\Core
 * @version 1.0.0
 */

$attachment_id = bp_get_document_attachment_id();
$download_url  = bp_document_download_link( $attachment_id, bp_get_document_id() );
$extension     = bp_get_document_extension();
/**
 * Filters the document text preview.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param bool $retval Whether document text preview.
 */
$bp_document_text_preview = apply_filters( 'bp_document_text_preview', true );
$sizes                    = is_file( get_attached_file( $attachment_id ) ) ? get_attached_file( $attachment_id ) : 0;

if ( $sizes && filesize( $sizes ) / 1e+6 < 2 && $bp_document_text_preview ) {
	if ( in_array( $extension, bp_get_document_preview_code_extensions(), true ) ) {
		$data      = bp_document_get_preview_text_from_attachment( $attachment_id );
		$file_data = $data['text'];
		$more_text = $data['more_text']
		?>
		<div class="document-text-wrap">
			<div class="document-text" data-extension="<?php echo esc_attr( $extension ); ?>">
				<textarea class="document-text-file-data-hidden" style="display: none;"><?php echo wp_kses_post( $file_data ); ?></textarea>
			</div>
			<div class="document-expand">
				<a href="#" class="document-expand-anchor"><i class="bb-icon-l bb-icon-expand document-icon-plus"></i>
					<span><?php esc_html_e( 'Expand', 'buddyboss' ); ?></span></a>
			</div>
		</div> <!-- .document-text-wrap -->
		<?php
		if ( true === $more_text ) {

			printf(
			/* translators: %s: download string */
				'<div class="more_text_view">%s</div>',
				sprintf(
				/* translators: %s: download url */
					wp_kses_post( 'This file was truncated for preview. Please <a href="%s">download</a> to view the full file.', 'buddyboss' ),
					esc_url( $download_url )
				)
			);
		}
	}
}
