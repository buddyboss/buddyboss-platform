<?php
/**
 * ReadyLaunch - Groups Album template.
 *
 * This template displays group albums with media theatre functionality
 * and single album views for group photo organization.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<div class="bb-media-container group-albums bb-rl-media-container">

	<?php
	bp_get_template_part( 'media/theatre' );

	if ( bp_is_group_video_support_enabled() ) {
		bp_get_template_part( 'video/theatre' );
	}

	if ( bp_is_group_document_support_enabled() ) {
		bp_get_template_part( 'document/theatre' );
	}
	if ( bp_is_group_video_support_enabled() ) {
		bp_get_template_part( 'video/add-video-thumbnail' );
	}

	switch ( bp_current_action() ) :

		// Home/Media/Albums.
		case 'albums':
			?>
			<div class="bb-rl-albums bb-rl-media-stream">
				<?php
				if ( ! bp_is_single_album() ) {
					bp_get_template_part( 'media/albums' );
				} else {
					bp_get_template_part( 'media/single-album' );
				}
				?>
			</div>
			<?php
			break;

		// Any other.
		default:
			bp_get_template_part( 'groups/single/plugins' );
			break;
	endswitch;
	?>
</div>
