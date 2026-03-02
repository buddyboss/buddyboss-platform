<?php
/**
 * ReadyLaunch - The template for activity document code preview.
 *
 * This template handles the preview display for code documents
 * with syntax highlighting and expand/collapse functionality.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$attachment_id = bp_get_document_attachment_id();
$download_url  = bp_document_download_link( $attachment_id, bp_get_document_id() );
$extension     = bp_get_document_extension();
/**
 * Filters the document text preview.
 *
 * @since BuddyBoss 2.9.00
 *
 * @param bool $retval Whether document text preview.
 */
$bp_document_text_preview = apply_filters( 'bp_document_text_preview', true );
$file_path                = get_attached_file( $attachment_id );
$sizes                    = is_file( $file_path ) ? $file_path : 0;
if ( $sizes && filesize( $sizes ) / 1e+6 < 2 && $bp_document_text_preview ) {
	if ( in_array( $extension, bp_get_document_preview_code_extensions(), true ) ) {
		$data      = bp_document_get_preview_text_from_attachment( $attachment_id );
		$file_data = $data['text'];
		$more_text = $data['more_text'];
		?>
		<div class="bb-rl-document-text-wrap">
			<div class="bb-rl-document-text" data-extension="<?php echo esc_attr( $extension ); ?>">
				<textarea class="bb-rl-document-text-file-data-hidden" style="display: none;"><?php echo wp_kses_post( $file_data ); ?></textarea>
			</div>
			<div class="bb-rl-document-expand">
				<a href="#" class="bb-rl-document-expand-anchor"><i class="bb-icons-rl-arrows-vertical document-icon-plus"></i>
					<span class="bb-rl-document-expand-text"><?php esc_html_e( 'Expand', 'buddyboss' ); ?></span>
					<span class="bb-rl-document-collapse-text"><?php esc_html_e( 'Collapse', 'buddyboss' ); ?></span>
				</a>
			</div>
		</div> <!-- .bb-rl-document-text-wrap -->
		<?php
		if ( true === $more_text ) {

			printf(
			/* translators: %s: download string */
				'<div class="bb_rl_more_text_view">%s</div>',
				sprintf(
				/* translators: %s: download url */
					wp_kses_post( 'This file was truncated for preview. Please <a href="%s">download</a> to view the full file.', 'buddyboss' ),
					esc_url( $download_url )
				)
			);
		}
	}
}
