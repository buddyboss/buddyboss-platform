<?php
/**
 * BuddyBoss - Video Actions
 *
 * @since BuddyBoss 1.0.0
 */

if ( ( bp_is_my_profile() || bp_current_user_can( 'bp_moderate' ) ) || ( bp_is_group() && ( bp_is_group_video() && groups_can_user_manage_video( bp_loggedin_user_id(), bp_get_current_group_id() ) ) || ( bp_is_group_albums() && groups_can_user_manage_albums( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ) ) : ?>

	<header class="bb-member-media-header bb-videos-actions">
		<div class="bb-media-meta bb-videos-meta">
			<a data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e( 'Delete', 'buddyboss' ); ?>" class="bb-delete bp-tooltip" id="bb-delete-video" href="#"><i class="bb-icon-trash-small"></i></a>
			<a data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e( 'Select All', 'buddyboss' ); ?>" class="bb-select bp-tooltip" id="bb-select-deselect-all-video" href="#"><i class="bb-icon-check"></i></a>
		</div>
	</header>

<?php endif; ?>
