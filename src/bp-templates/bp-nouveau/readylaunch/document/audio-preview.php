<?php
/**
 * ReadyLaunch - The template for activity document audio preview.
 *
 * @since   BuddyBoss 2.9.00
 * @package BuddyBoss\Core
 * @version 1.0.0
 */

$audio_url                    = '';
$doc_extension                = bp_get_document_extension();
$doc_preview_music_extensions = bp_get_document_preview_music_extensions();
$bp_document_music_preview    = apply_filters( 'bp_document_music_preview', true );
$allow_extension              = in_array( $doc_extension, $doc_preview_music_extensions, true );
if ( $allow_extension && true === $bp_document_music_preview ) {
	$audio_url = bp_document_get_preview_audio_url( bp_get_document_id(), bp_get_document_attachment_id(), $doc_extension );
}
if ( $allow_extension ) {
	?>
	<div class="bb-rl-document-audio-wrap">
		<audio controls controlsList="nodownload">
			<source src="<?php echo esc_url( $audio_url ); ?>" type="audio/mpeg">
			<?php esc_html_e( 'Your browser does not support the audio element.', 'buddyboss' ); ?>
		</audio>
	</div>
	<?php
}
