<?php
/**
 * The template for activity document audio preview
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @version 1.0.0
 */

$audio_url                    = '';
$doc_extension                = bp_get_document_extension();
$doc_preview_music_extensions = bp_get_document_preview_music_extensions();
$bp_document_music_preview    = apply_filters( 'bp_document_music_preview', true );

if ( in_array( $doc_extension, $doc_preview_music_extensions, true ) && $bp_document_music_preview ) {
	$audio_url = bp_document_get_preview_audio_url( bp_get_document_id(), bp_get_document_attachment_id(), $doc_extension );
}

if ( in_array( $doc_extension, $doc_preview_music_extensions, true ) ) {
	?>
	<div class="document-audio-wrap">
		<audio controls controlsList="nodownload">
			<source src="<?php echo esc_url( $audio_url ); ?>" type="audio/mpeg">
			<?php esc_html_e( 'Your browser does not support the audio element.', 'buddyboss' ); ?>
		</audio>
	</div>
	<?php
}
