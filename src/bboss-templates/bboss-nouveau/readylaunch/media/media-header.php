<?php
/**
 * ReadyLaunch - Media Header template.
 *
 * This template handles displaying the media header for group and my profile.
 *
 * @since      BuddyBoss 2.9.00
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @package    BuddyBoss\Template
 * @version    1.0.0
 */

defined( 'ABSPATH' ) || exit;

$is_group      = function_exists( 'bboss_is_group' ) && bboss_is_group();
$is_my_profile = function_exists( 'bboss_is_my_profile' ) && bboss_is_my_profile();

if (
	(
		$is_group ||
		$is_my_profile
	) &&
	bboss_has_media( bboss_ajax_querystring( 'media' ) )
) {
	?>
	<div class="bb-media-actions-wrap bb-rl-media-actions-wrap">
		<?php
		if ( $is_group ) {
			$current_group_id    = bboss_get_current_group_id();
			$bp_loggedin_user_id = bboss_loggedin_user_id();
			if (
				bboss_is_group_media() &&
				(
					groups_can_user_manage_media( $bp_loggedin_user_id, $current_group_id ) ||
					bboss_groups_is_user_mod( $bp_loggedin_user_id, $current_group_id ) ||
					bboss_groups_is_user_admin( $bp_loggedin_user_id, $current_group_id )
				)
			) {
				bboss_get_template_part( 'media/add-media' );
				bboss_nouveau_group_hook( 'before', 'media_content' );
			} else {
				?>
				<h2 class="bb-title"><?php esc_html_e( 'Photos', 'buddyboss-platform' ); ?></h2>
				<?php
			}
		} elseif ( $is_my_profile ) {
			bboss_get_template_part( 'media/add-media' );
			bboss_nouveau_member_hook( 'before', 'media_content' );
		}
		bboss_get_template_part( 'media/actions' );
		?>
	</div>
	<?php
}
