<?php
/**
 * BuddyBoss - Activity Document Audio Preview
 *
 * @since BuddyBoss 1.5.7
 * @package BuddyBoss\Core
 */

if ( in_array( bp_get_document_extension(), bp_get_document_preview_music_extensions(), true ) ) {
	?>
	<div class="document-audio-wrap">
		<audio controls controlsList="nodownload">
			<source src="<?php bp_document_attachment_url(); ?>" type="audio/mpeg">
			<?php esc_html_e( 'Your browser does not support the audio element.', 'buddyboss' ); ?>
		</audio>
	</div>
	<?php
}
