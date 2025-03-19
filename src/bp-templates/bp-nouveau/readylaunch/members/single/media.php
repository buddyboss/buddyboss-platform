<?php
/**
 * The template for users media
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @version 1.0.0
 */

$is_send_ajax_request = bb_is_send_ajax_request();
?>
<div class="bb-rl-sub-ctrls flex items-center justify-between">
	<?php
	bp_get_template_part( 'members/single/parts/item-subnav' );
	?>
</div>
<div class="bb-media-container member-media bb-rl-media-container">
	<?php
	bp_get_template_part( 'media/theatre' );
	if ( bp_is_profile_video_support_enabled() ) {
		bp_get_template_part( 'video/theatre' );
		bp_get_template_part( 'video/add-video-thumbnail' );
	}
	bp_get_template_part( 'document/theatre' );

	switch ( bp_current_action() ) :

		// Home/Media.
		case 'my-media':
			?>
			<div id="media-stream" class="media bb-rl-media-stream" data-bp-list="media" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
				<div class="bb-media-actions-wrap bb-rl-media-actions-wrap">
					<?php
						bp_get_template_part( 'media/add-media' );
						bp_nouveau_member_hook( 'before', 'media_content' );
						bp_get_template_part( 'media/actions' );
					?>
				</div>
				<?php
				if ( $is_send_ajax_request ) {
					echo '<div id="bp-ajax-loader">';
					bp_nouveau_user_feedback( 'member-media-loading' );
					echo '</div>';
				} else {
					bp_get_template_part( 'media/media-loop' );
				}
				?>
			</div><!-- .media -->
			<?php
			bp_nouveau_member_hook( 'after', 'media_content' );
			break;

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
			bp_get_template_part( 'members/single/plugins' );
			break;
	endswitch;
	?>
</div>
