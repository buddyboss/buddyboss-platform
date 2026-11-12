<?php
/**
 * The template for users media
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/media.php.
 *
 * @since   BuddyPress 1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$is_send_ajax_request = bboss_is_send_ajax_request();
$bp_current_action    = bboss_current_action();
$count                = false;

bboss_get_template_part( 'members/single/parts/item-subnav' );

if ( bboss_is_user() && bboss_enable_content_counts() ) {
	?>
	<div class="bb-item-count">
		<?php
		if ( ! $is_send_ajax_request || ( 'albums' === $bp_current_action && ! bboss_is_single_album() ) ) {
			if ( 'my-media' === $bp_current_action ) {
				$count = bboss_media_get_total_media_count();
			} elseif ( 'albums' === $bp_current_action && ! bboss_is_single_album() ) {
				$count = bboss_media_get_total_album_count();
			}

			if ( false !== $count ) {
				if ( 'albums' === $bp_current_action ) {
					printf(
						wp_kses(
							/* translators: %d is the count */
							_n(
								'<span class="bb-count">%d</span> Album',
								'<span class="bb-count">%d</span> Albums',
								$count,
								'buddyboss'
							),
							array( 'span' => array( 'class' => true ) )
						),
						(int) $count
					);
				} else {
					printf(
						wp_kses(
							/* translators: %d is the count */
							_n(
								'<span class="bb-count">%d</span> Photo',
								'<span class="bb-count">%d</span> Photos',
								$count,
								'buddyboss'
							),
							array( 'span' => array( 'class' => true ) )
						),
						(int) $count
					);
				}
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
	bboss_get_template_part( 'media/theatre' );
	if ( bboss_is_active( 'video' ) && bboss_is_profile_video_support_enabled() ) {
		bboss_get_template_part( 'video/theatre' );
		bboss_get_template_part( 'video/add-video-thumbnail' );
	}
	bboss_get_template_part( 'document/theatre' );

	switch ( $bp_current_action ) :

		// Home/Media.
		case 'my-media':
			bboss_nouveau_member_hook( 'before', 'media_content' );
			?>
			<div class="bb-media-actions-header">
				<?php
				bboss_get_template_part( 'media/add-media' );
				bboss_get_template_part( 'media/actions' );
				?>
			</div>
			<div id="media-stream" class="media" data-bp-list="media" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
				<?php
				if ( $is_send_ajax_request ) {
					echo '<div id="bp-ajax-loader">';
					bboss_nouveau_user_feedback( 'member-media-loading' );
					echo '</div>';
				} else {
					bboss_get_template_part( 'media/media-loop' );
				}
				?>
			</div><!-- .media -->
			<?php
			bboss_nouveau_member_hook( 'after', 'media_content' );
			break;

		// Home/Media/Albums.
		case 'albums':
			if ( ! bboss_is_single_album() ) {
				bboss_get_template_part( 'media/albums' );
			} else {
				bboss_get_template_part( 'media/single-album' );
			}
			break;

		// Any other.
		default:
			bboss_get_template_part( 'members/single/plugins' );
			break;
	endswitch;
	?>
</div>
