<?php
/**
 * The template for users video
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @version 1.0.0
 */

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
			<div id="video-stream" class="video bb-rl-media-stream" data-bp-list="video" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
				<?php
				if ( bp_has_video( bp_ajax_querystring( 'video' ) ) ) {
					bp_get_template_part( 'video/add-video' );
					bp_nouveau_member_hook( 'before', 'video_content' );
					bp_get_template_part( 'video/actions' );
				}

				if ( $is_send_ajax_request ) {
					echo '<div id="bp-ajax-loader">';
					bp_nouveau_user_feedback( 'member-video-loading' );
					echo '</div>';
				} else {
					bp_get_template_part( 'video/video-loop' );
				}
				?>
			</div><!-- .video -->
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
