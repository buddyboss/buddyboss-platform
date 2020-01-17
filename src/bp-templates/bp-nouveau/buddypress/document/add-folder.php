<?php
/**
 * BuddyBoss - Add Media
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php if ( bp_is_my_profile() || ( bp_is_group() && groups_can_user_manage_albums( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ) : ?>

	<div class="bb-media-actions-wrap album-actions-wrap">
		<div class="bb-media-actions">
			<a href="#" id="bb-create-folder" class="bb-create-folder button small outline"><i class="bb-icon-plus"></i><?php _e( 'Create Folder', 'buddyboss' ); ?></a>
		</div>
	</div>

	<?php bp_get_template_part( 'document/create-folder' ); ?>

<?php endif; ?>
