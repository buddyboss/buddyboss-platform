<?php
/**
 * BuddyBoss - Video Actions, This template is used to render the video actions.
 *
 * This template handles the video action buttons for deleting and selecting videos.
 * It displays actions based on user permissions and current context (profile or group).
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$bp_loggedin_user_id     = bp_loggedin_user_id();
$bp_get_current_group_id = function_exists( 'bp_get_current_group_id' ) ? bp_get_current_group_id() : 0;

if (
	(
		bp_is_my_profile() ||
		bp_current_user_can( 'bp_moderate' )
	) ||
	(
		bp_is_group() &&
		(
			(
				bp_is_group_video() &&
				(
					groups_can_user_manage_video( $bp_loggedin_user_id, $bp_get_current_group_id ) ||
					groups_is_user_mod( $bp_loggedin_user_id, $bp_get_current_group_id ) ||
					groups_is_user_admin( $bp_loggedin_user_id, $bp_get_current_group_id )
				)
			) ||
			(
				bp_is_group_albums() &&
				(
					groups_can_user_manage_albums( $bp_loggedin_user_id, $bp_get_current_group_id ) ||
					groups_is_user_mod( $bp_loggedin_user_id, $bp_get_current_group_id ) ||
					groups_is_user_admin( $bp_loggedin_user_id, $bp_get_current_group_id )
				)
			)
		)
	)
) :
	?>

	<header class="bb-member-media-header bb-videos-actions" style="display:none;">
		<div class="bb-media-meta bb-videos-meta">
			<a data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Delete', 'buddyboss' ); ?>" class="bb-delete bp-tooltip" id="bb-delete-video" href="#"><i class="bb-icon-l bb-icon-trash"></i></a>
			<a data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Select All', 'buddyboss' ); ?>" class="bb-select bp-tooltip" id="bb-select-deselect-all-video" href="#"><i class="bb-icon-l bb-icon-check"></i></a>
		</div>
	</header>

<?php endif; ?>
