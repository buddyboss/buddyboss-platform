<?php
/**
 * ReadyLaunch - Member Media template.
 *
 * This template handles displaying member media with albums and loading placeholders.
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
<div class="bb-rl-sub-ctrls flex items-center justify-between">
	<?php
	bboss_get_template_part( 'members/single/parts/item-subnav' );
	?>
</div>
<div class="bb-media-container member-media bb-rl-media-container">
	<?php
	bboss_get_template_part( 'media/theatre' );
	if ( bboss_is_active( 'video' ) && bboss_is_profile_video_support_enabled() ) {
		bboss_get_template_part( 'video/theatre' );
		bboss_get_template_part( 'video/add-video-thumbnail' );
	}
	bboss_get_template_part( 'document/theatre' );

	switch ( bboss_current_action() ) :

		// Home/Media.
		case 'my-media':
			/**
			 * Fires before the display of the media list.
			 *
			 * @since BuddyBoss 2.15.2
			 */
			bboss_nouveau_member_hook( 'before', 'rl_stream_media_content' );
			?>
			<div class="bb-rl-media-stream">
				<?php
				bboss_get_template_part( 'media/media-header' );
				?>
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
			</div>
			<?php
			bboss_nouveau_member_hook( 'after', 'media_content' );

			/**
			 * Fires after the display of the media list.
			 *
			 * @since BuddyBoss 2.15.2
			 */
			bboss_nouveau_member_hook( 'after', 'rl_stream_media_content' );
			break;

		// Home/Media/Albums.
		case 'albums':
			?>
			<div class="bb-rl-albums bb-rl-media-stream">
				<?php
				if ( ! bboss_is_single_album() ) {
					bboss_get_template_part( 'media/albums' );
				} else {
					bboss_get_template_part( 'media/single-album' );
				}
				?>
			</div>
			<?php
			break;

		// Any other.
		default:
			bboss_get_template_part( 'members/single/plugins' );
			break;
	endswitch;
	?>
</div>
