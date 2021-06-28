<?php
/**
 * BuddyBoss - Groups Album
 *
 * @since BuddyBoss 1.0.0
 */
?>

<div class="bb-media-container group-albums">

	<?php
	bp_get_template_part( 'media/theatre' );

	if ( bp_is_profile_video_support_enabled() ) {
		bp_get_template_part( 'video/theatre' );
	}
	if ( bp_is_profile_document_support_enabled() ) {
		bp_get_template_part( 'document/theatre' );
	}
	if ( bp_is_profile_video_support_enabled() ) {
		bp_get_template_part( 'video/add-video-thumbnail' );
	}

	switch ( bp_current_action() ) :

		// Home/Media/Albums.
		case 'albums':
			if ( ! bp_is_single_album() ) {
				bp_get_template_part( 'media/albums' );
			} else {
				bp_get_template_part( 'media/single-album' );
			}
			break;

		// Any other.
		default:
			bp_get_template_part( 'groups/single/plugins' );
			break;
	endswitch;
	?>
</div>
