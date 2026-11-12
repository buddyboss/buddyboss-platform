<?php
/**
 * ReadyLaunch - Groups Media template.
 *
 * This template displays group photos with upload functionality,
 * media theatre, and management capabilities for group administrators.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$is_send_ajax_request = bboss_is_send_ajax_request();
?>

<div class="bb-media-container group-media bb-rl-media-container">
	<?php
	bboss_get_template_part( 'media/theatre' );
	if ( bboss_is_active( 'video' ) && bboss_is_profile_video_support_enabled() ) {
		bboss_get_template_part( 'video/theatre' );
	}

	switch ( bboss_current_action() ) :

		// Home/Media.
		case 'photos':
			/**
			 * Fires before the display of the media list.
			 *
			 * @since BuddyBoss 2.15.2
			 */
			bboss_nouveau_group_hook( 'before', 'rl_stream_media_content' );
			?>
			<div class="bb-rl-media-stream">
				<?php
				bboss_get_template_part( 'media/media-header' );
				?>
				<div id="media-stream" class="media" data-bp-list="media" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
					<?php
					if ( $is_send_ajax_request ) {
						echo '<div id="bp-ajax-loader">';
						bboss_nouveau_user_feedback( 'group-media-loading' );
						echo '</div>';
					} else {
						bboss_get_template_part( 'media/media-loop' );
					}
					?>
				</div><!-- .media -->
			</div>
			<?php
			bboss_nouveau_group_hook( 'after', 'media_content' );

			/**
			 * Fires after the display of the media list.
			 *
			 * @since BuddyBoss 2.15.2
			 */
			bboss_nouveau_group_hook( 'after', 'rl_stream_media_content' );
			break;
		// Any other.
		default:
			bboss_get_template_part( 'groups/single/plugins' );
			break;
	endswitch;
	?>
</div>
