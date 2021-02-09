<?php
/**
 * BuddyBoss - Activity Document Code Preview
 *
 * @since BuddyBoss 1.5.7
 * @package BuddyBoss\Core
 */

if ( in_array( bp_get_document_extension(), bp_get_document_preview_code_extensions(), true ) ) {
	$file  = get_attached_file( bp_get_document_attachment_id() );
	$sizes = is_file( $file ) ? $file : 0;

	if ( $sizes && filesize( $sizes ) / 1e+6 < 2 ) {
		$data      = bp_document_get_preview_text_from_attachment( bp_get_document_attachment_id() );
		$file_data = $data['text'];
		$more_text = $data['more_text']
		?>
		<div class="document-text-wrap">
			<div class="document-text" data-extension="<?php echo esc_attr( bp_get_document_extension() ); ?>">
				<textarea class="document-text-file-data-hidden" style="display: none;"><?php echo wp_kses_post( $file_data ); ?></textarea>
			</div>
			<div class="document-expand">
				<a href="#" class="document-expand-anchor"><i class="bb-icon-plus document-icon-plus"></i> <?php esc_html_e( 'Click to expand', 'buddyboss' ); ?>
				</a>
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
