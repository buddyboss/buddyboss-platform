<?php
/**
 * The template for users media
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/media.php.
 *
 * @since   BuddyPress 1.0.0
 * @version 1.0.0
 */

$is_send_ajax_request = bb_is_send_ajax_request();
$bp_current_action    = bp_current_action();
$count                = false;
if ( bp_is_user() && bb_enable_content_counts() ) {
	?>
	<div class="bb-item-count">
		<?php
		if ( ! $is_send_ajax_request || ( 'albums' === $bp_current_action && ! bp_is_single_album() ) ) {
			if ( 'my-media' === $bp_current_action ) {
				$count = $count = bp_media_get_total_media_count();
			} elseif ( 'albums' === $bp_current_action && ! bp_is_single_album() ) {
				$count = bb_media_get_total_album_count();
			}

			if ( false !== $count ) {
				printf(
					wp_kses(
						/* translators: %d is the count */
						_n(
							'<span class="bb-count">%d</span> ' . ( 'albums' === $bp_current_action ? 'Album' : 'Photo' ),
							'<span class="bb-count">%d</span> ' . ( 'albums' === $bp_current_action ? 'Albums' : 'Photos' ),
							$count,
							'buddyboss'
						),
						array( 'span' => array( 'class' => true ) )
					),
					(int) $count
				);
			}

			unset( $count );
		}
		?>
	</div>
	<?php
}
?>
<div class="bb-media-container member-media">
	<?php
	bp_get_template_part( 'members/single/parts/item-subnav' );
	bp_get_template_part( 'media/theatre' );
	if ( bp_is_profile_video_support_enabled() ) {
		bp_get_template_part( 'video/theatre' );
		bp_get_template_part( 'video/add-video-thumbnail' );
	}
	bp_get_template_part( 'document/theatre' );

	switch ( $bp_current_action ) :

		// Home/Media.
		case 'my-media':
			bp_get_template_part( 'media/add-media' );
			bp_nouveau_member_hook( 'before', 'media_content' );
			bp_get_template_part( 'media/actions' );
			?>
			<div id="media-stream" class="media" data-bp-list="media" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
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
