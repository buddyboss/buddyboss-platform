<?php
/**
 * ReadyLaunch - Video Header template.
 *
 * This template handles displaying the video header for group and my profile.
 *
 * @since      BuddyBoss 2.9.00
 * @subpackage BP_Nouveau\ReadyLaunch
 * @package    BuddyBoss\Template
 * @version    1.0.0
 */

defined( 'ABSPATH' ) || exit;

$is_group      = function_exists( 'bp_is_group' ) && bp_is_group();
$is_my_profile = function_exists( 'bp_is_my_profile' ) && bp_is_my_profile();

if (
	(
		$is_group ||
		$is_my_profile
	) &&
	bp_has_video( bp_ajax_querystring( 'video' ) )
) {
	?>
	<div class="bb-video-actions-wrap bb-rl-media-actions-wrap">
		<?php
		if ( $is_group ) {
			$current_group_id    = bp_get_current_group_id();
			$bp_loggedin_user_id = bp_loggedin_user_id();
			if (
				bp_is_group_video() &&
				(
					groups_can_user_manage_video( $bp_loggedin_user_id, $current_group_id ) ||
					groups_is_user_mod( $bp_loggedin_user_id, $current_group_id ) ||
					groups_is_user_admin( $bp_loggedin_user_id, $current_group_id )
				)
			) {
				bp_get_template_part( 'video/add-video' );
				bp_nouveau_group_hook( 'before', 'video_content' );
			} else {
				?>
				<h2 class="bb-title"><?php esc_html_e( 'Videos', 'buddyboss' ); ?></h2>
				<?php
			}
		} elseif ( $is_my_profile ) {
			bp_get_template_part( 'video/add-video' );
			bp_nouveau_member_hook( 'before', 'video_content' );
		}

		bp_get_template_part( 'video/actions' );
		?>
	</div>
	<?php
}
