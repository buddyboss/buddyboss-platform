<?php
/**
 * ReadyLaunch - Groups Video template.
 *
 * This template displays group videos with upload functionality,
 * video theatre, and management capabilities for group administrators.
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

<div class="bb-video-container bb-media-container group-video bb-rl-media-container">
	<?php
	bp_get_template_part( 'media/theatre' );
	bp_get_template_part( 'video/theatre' );
	bp_get_template_part( 'document/theatre' );

	switch ( bp_current_action() ) :

		// Home/Video.
		case 'videos':
			$current_group_id = bp_get_current_group_id();
			$loggedin_user_id = bp_loggedin_user_id();
			?>
			<div class="bb-rl-media-stream">
				<?php
				bp_get_template_part( 'video/video-header' );
				?>
				<div id="video-stream" class="video" data-bp-list="video" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
					<?php
					if ( $is_send_ajax_request ) {
						echo '<div id="bp-ajax-loader">';
						bp_nouveau_user_feedback( 'group-video-loading' );
						echo '</div>';
					} else {
						bp_get_template_part( 'video/video-loop' );
					}
					?>
				</div><!-- .media -->
			</div>
			<?php
			bp_nouveau_group_hook( 'after', 'video_content' );

			break;
		// Any other.
		default:
			bp_get_template_part( 'groups/single/plugins' );
			break;
	endswitch;
	?>
</div>
