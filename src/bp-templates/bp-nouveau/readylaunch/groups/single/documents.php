<?php
/**
 * ReadyLaunch - Groups Document template.
 *
 * This template renders group documents with search functionality,
 * folder management, and document upload capabilities.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$is_send_ajax_request = bb_is_send_ajax_request();
?>
<div class="bb-media-container group-media">

	<?php
	bp_get_template_part( 'media/theatre' );

	if ( bp_is_profile_video_support_enabled() ) {
		bp_get_template_part( 'video/theatre' );
		bp_get_template_part( 'video/add-video-thumbnail' );
	}

	bp_get_template_part( 'document/theatre' );

	if ( bp_is_single_folder() ) {
		bp_get_template_part( 'document/single-folder' );
	} else {

		switch ( bp_current_action() ) :

			// Home/Documents.
			case 'documents':
				?>
				<div class="bb-rl-media-stream">
					<?php
					bp_get_template_part( 'document/document-header' );
					?>
					<div id="media-stream" class="media bb-rl-document" data-bp-list="document" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
						<?php
						bp_nouveau_group_hook( 'before', 'document_content' );
						bp_get_template_part( 'document/actions' );
						?>

						<?php
						if ( $is_send_ajax_request ) {
							echo '<div id="bp-ajax-loader">';
							bp_nouveau_user_feedback( 'group-document-loading' );
							echo '</div>';
						} else {
							bp_get_template_part( 'document/document-loop' );
						}
						?>
					</div><!-- .media -->
				</div>
				<?php
				bp_nouveau_group_hook( 'after', 'document_content' );

				break;

			// Any other.
			default:
				bp_get_template_part( 'groups/single/plugins' );
				break;
		endswitch;
	}
	?>
</div>
