<?php
/**
 * The template for document actions
 *
 * This template can be overridden by copying it to yourtheme/buddypress/document/actions.php.
 *
 * @since   BuddyBoss 1.4.0
 * @package BuddyBoss\Core
 * @version 1.4.0
 */

if ( bp_is_my_profile() || ( bp_is_active( 'groups' ) && bp_is_group() && ( bp_is_group_media() && groups_can_user_manage_media( bp_loggedin_user_id(), bp_get_current_group_id() ) ) || ( bp_is_group_albums() && groups_can_user_manage_albums( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ) ) : ?>

	<header class="bb-member-media-header bb-photos-actions">
		<div class="bb-media-meta bb-documents-meta">
			<a data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Delete', 'buddyboss' ); ?>" class="bb-delete bp-tooltip" id="bb-delete-media" href="#"><i class="dashicons dashicons-trash"></i></a>
			<a data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Select All', 'buddyboss' ); ?>" class="bb-select bp-tooltip" id="bb-select-deselect-all-media" href="#"><i class="dashicons dashicons-yes"></i></a>
		</div>
	</header>

<?php endif; ?>
