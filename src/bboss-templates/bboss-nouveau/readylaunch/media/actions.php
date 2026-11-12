<?php
/**
 * ReadyLaunch - Media Actions template.
 *
 * This template handles media action buttons for bulk operations.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$bp_loggedin_user_id     = bboss_loggedin_user_id();
$bp_get_current_group_id = function_exists( 'bboss_get_current_group_id' ) ? bboss_get_current_group_id() : 0;

if (
	(
		bboss_is_my_profile() ||
		bboss_current_user_can( 'bp_moderate' )
	) ||
	(
		bboss_is_group() &&
		(
			bboss_is_group_media() &&
			(
				groups_can_user_manage_media( $bp_loggedin_user_id, $bp_get_current_group_id ) ||
				bboss_groups_is_user_mod( $bp_loggedin_user_id, $bp_get_current_group_id ) ||
				bboss_groups_is_user_admin( $bp_loggedin_user_id, $bp_get_current_group_id )
			)
		) ||
		(
			bboss_is_group_albums() &&
			(
				groups_can_user_manage_albums( $bp_loggedin_user_id, $bp_get_current_group_id ) ||
				bboss_groups_is_user_mod( $bp_loggedin_user_id, $bp_get_current_group_id ) ||
				bboss_groups_is_user_admin( $bp_loggedin_user_id, $bp_get_current_group_id )
			)
		)
	)
) : ?>
	<header class="bb-member-media-header bb-photos-actions" style="display:none;">
		<div class="bb-media-meta bb-photos-meta">
			<a data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Select All', 'buddyboss-platform' ); ?>" class="bb-select bp-tooltip" id="bb-select-deselect-all-media" href="#"><i class="bb-icons-rl-check-circle"></i></a>
			<a data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Delete', 'buddyboss-platform' ); ?>" class="bb-delete bp-tooltip" id="bb-delete-media" href="#" disabled="disabled"><i class="bb-icons-rl-trash"></i></a>
		</div>
	</header>

<?php endif; ?>
