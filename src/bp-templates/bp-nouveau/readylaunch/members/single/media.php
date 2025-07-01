<?php
/**
 * ReadyLaunch - Member Media template.
 *
 * This template handles displaying member media with albums and loading placeholders.
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
			<div class="bb-rl-media-stream">
				<?php
				bp_get_template_part( 'media/media-header' );
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
			</div>
			<?php
			bp_nouveau_member_hook( 'after', 'media_content' );
			break;

		// Home/Media/Albums.
		case 'albums':
			?>
			<div class="bb-rl-albums bb-rl-media-stream">
				<?php
				if ( ! bp_is_single_album() ) {
					bp_get_template_part( 'media/albums' );
				} else {
					bp_get_template_part( 'media/single-album' );
				}
				?>
			</div>
			<?php
			break;

		// Any other.
		default:
			bp_get_template_part( 'members/single/plugins' );
			break;
	endswitch;
	?>
</div>
