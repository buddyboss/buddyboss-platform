<?php
/**
 * ReadyLaunch - Document actions template.
 *
 * This template handles the action buttons for document management
 * including delete and select all functionality.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( bp_is_my_profile() || ( bp_is_active( 'groups' ) && bp_is_group() && ( ( bp_is_group_media() && groups_can_user_manage_media( bp_loggedin_user_id(), bp_get_current_group_id() ) ) || ( bp_is_group_albums() && groups_can_user_manage_albums( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ) ) ) :
	?>

	<header class="bb-member-media-header bb-photos-actions">
		<div class="bb-media-meta bb-documents-meta">
			<a data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Select All', 'buddyboss' ); ?>" class="bb-select bp-tooltip" id="bb-select-deselect-all-media" href="#"><i class="bb-icons-rl-check-circle"></i></a>
			<a data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Delete', 'buddyboss' ); ?>" class="bb-delete bp-tooltip" id="bb-delete-media" href="#" disabled="disabled"><i class="dashicons dashicons-trash"></i></a>
		</div>
	</header>

<?php endif; ?>
