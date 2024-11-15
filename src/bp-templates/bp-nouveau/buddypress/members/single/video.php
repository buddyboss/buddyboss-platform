<?php
/**
 * The template for users video
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/video.php.
 *
 * @since   BuddyBoss 1.7.0
 * @version 1.7.0
 */

$is_send_ajax_request = bb_is_send_ajax_request();

if ( bp_is_user() ) {
	switch ( bp_current_action() ) :
		case 'my-video':
		 	$count = bp_video_get_total_video_count( bp_displayed_user_id() );
			?>
			<div class="bb-item-count">
				<?php
				/* translators: %d is the video count */
				printf(
					wp_kses( _n( '<span class="bb-count">%d</span> Video', '<span "bb-count">%d</span> Videos', $count, 'buddyboss' ), array( 'span' => array() ) ),
					$count
				);
				?>
			</div>
			<?php
			break;
		case 'albums':
			break;
	endswitch;

}
?>
<div class="bb-video-container bb-media-container member-video">
	<?php
	bp_get_template_part( 'members/single/parts/item-subnav' );
	bp_get_template_part( 'video/theatre' );
	bp_get_template_part( 'media/theatre' );
	bp_get_template_part( 'document/theatre' );

	switch ( bp_current_action() ) :

		// Home/Video.
		case 'my-video':
			bp_get_template_part( 'video/add-video' );
			bp_nouveau_member_hook( 'before', 'video_content' );
			bp_get_template_part( 'video/actions' );
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
