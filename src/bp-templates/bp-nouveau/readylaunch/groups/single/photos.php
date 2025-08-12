<?php
/**
 * ReadyLaunch - Groups Media template.
 *
 * This template displays group photos with upload functionality,
 * media theatre, and management capabilities for group administrators.
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

<div class="bb-media-container group-media bb-rl-media-container">
	<?php
	bp_get_template_part( 'media/theatre' );
	if ( bp_is_profile_video_support_enabled() ) {
		bp_get_template_part( 'video/theatre' );
	}

	switch ( bp_current_action() ) :

		// Home/Media.
		case 'photos':
			?>
			<div class="bb-rl-media-stream">
				<?php
				bp_get_template_part( 'media/media-header' );
				?>
				<div id="media-stream" class="media" data-bp-list="media" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
					<?php
					if ( $is_send_ajax_request ) {
						echo '<div id="bp-ajax-loader">';
						bp_nouveau_user_feedback( 'group-media-loading' );
						echo '</div>';
					} else {
						bp_get_template_part( 'media/media-loop' );
					}
					?>
				</div><!-- .media -->
			</div>
			<?php
			bp_nouveau_group_hook( 'after', 'media_content' );

			break;
		// Any other.
		default:
			bp_get_template_part( 'groups/single/plugins' );
			break;
	endswitch;
	?>
</div>
