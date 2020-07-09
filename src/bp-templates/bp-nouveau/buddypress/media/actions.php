<?php
/**
 * BuddyBoss - Media Actions
 *
 * @since BuddyBoss 1.0.0
 */

if ( ( bp_is_my_profile() || bp_current_user_can( 'bp_moderate' ) ) || ( bp_is_group() && ( bp_is_group_media() && groups_can_user_manage_media( bp_loggedin_user_id(), bp_get_current_group_id() ) ) || ( bp_is_group_albums() && groups_can_user_manage_albums( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ) ) : ?>

	<header class="bb-member-photos-header bb-photos-actions">
		<div class="bb-photos-meta">
			<a data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e( 'Delete', 'buddyboss' ); ?>" class="bb-delete bp-tooltip" id="bb-delete-media" href="#"><i class="dashicons dashicons-trash"></i></a>
			<a data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e( 'Select All', 'buddyboss' ); ?>" class="bb-select bp-tooltip" id="bb-select-deselect-all-media" href="#"><i class="dashicons dashicons-yes"></i></a>
		</div>
	</header>

<?php endif; ?>
