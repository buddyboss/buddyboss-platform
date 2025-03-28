<?php
/**
 * BuddyBoss - Groups Video
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/videos.php.
 *
 * @since   BuddyBoss 1.7.0
 * @version 1.7.0
 */

$is_send_ajax_request = bb_is_send_ajax_request();
?>

<div class="bb-video-container bb-media-container group-video">
	<?php
	bp_get_template_part( 'media/theatre' );
	bp_get_template_part( 'video/theatre' );
	bp_get_template_part( 'document/theatre' );

	switch ( bp_current_action() ) :

		// Home/Video.
		case 'videos':
			$current_group_id = bp_get_current_group_id();
			$loggedin_user_id = bp_loggedin_user_id();

			if (
				bp_is_group_video() &&
				(
					groups_can_user_manage_video( $loggedin_user_id, $current_group_id ) ||
					groups_is_user_mod( $loggedin_user_id, $current_group_id ) ||
					groups_is_user_admin( $loggedin_user_id, $current_group_id )
				)
			) {
				bp_get_template_part( 'video/add-video' );
			} else {
				?>
				<h2 class="bb-title"><?php esc_html_e( 'Videos', 'buddyboss' ); ?></h2>
				<?php
			}

			bp_nouveau_group_hook( 'before', 'video_content' );
			bp_get_template_part( 'video/actions' );
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
