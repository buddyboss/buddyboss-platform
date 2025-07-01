<?php
/**
 * ReadyLaunch - Member Video template.
 *
 * This template handles displaying member videos with albums and loading placeholders.
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
<div class="bb-video-container bb-media-container member-video bb-rl-media-container">
	<?php
	bp_get_template_part( 'members/single/parts/item-subnav' );
	bp_get_template_part( 'video/theatre' );
	bp_get_template_part( 'media/theatre' );
	bp_get_template_part( 'document/theatre' );

	switch ( bp_current_action() ) :

		// Home/Video.
		case 'my-video':
			?>
			<div class="bb-rl-media-stream">
				<?php
				bp_get_template_part( 'video/video-header' );
				?>
				<div id="video-stream" class="video" data-bp-list="video" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
					<?php
					if ( $is_send_ajax_request ) {
						echo '<div id="bp-ajax-loader">';
						bp_nouveau_user_feedback( 'member-video-loading' );
						echo '</div>';
					} else {
						bp_get_template_part( 'video/video-loop' );
					}
					?>
				</div><!-- .video -->
			</div>
			<?php
			bp_nouveau_member_hook( 'after', 'video_content' );

			break;

		// Home/Video/Albums.
		case 'albums':
			if ( ! bp_is_single_video_album() ) {
				bp_get_template_part( 'video/albums' );
			} else {
				bp_get_template_part( 'video/single-album' );
			}
			break;

		// Any other.
		default:
			bp_get_template_part( 'members/single/plugins' );
			break;
	endswitch;
	?>
</div>
