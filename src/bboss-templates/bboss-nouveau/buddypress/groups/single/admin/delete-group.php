<?php
/**
 * BP Nouveau Group's delete group template.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/admin/delete-group.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<h2 class="bp-screen-title warn">
	<?php esc_html_e( 'Delete this group', 'buddyboss' ); ?>
</h2>

<?php bboss_nouveau_user_feedback( 'group-delete-warning' ); ?>

<div class="bp-checkbox-wrap">
	<input type="checkbox" name="delete-group-understand" id="delete-group-understand" class="bb-input-switch bs-styled-checkbox" value="1" onclick="if(this.checked) { document.getElementById( 'delete-group-button' ).disabled = ''; } else { document.getElementById( 'delete-group-button' ).disabled = 'disabled'; }" />
	<label for="delete-group-understand" class="bp-label-text warn"><?php esc_html_e( 'I understand the consequences of deleting this group.', 'buddyboss' ); ?></label>
</div>

<?php if ( bboss_is_active('forums') && bboss_bbp_get_group_forum_ids( bboss_groups_get_current_group()->id ) ): ?>
	<div class="bp-checkbox-wrap">
		<input type="checkbox" name="delete-group-forum-understand" id="delete-group-forum-understand" value="1" class="bb-input-switch bs-styled-checkbox" />
		<label for="delete-group-forum-understand" class="bp-label-text warn"><?php esc_html_e( 'I also want to delete the discussion forum.', 'buddyboss' ); ?></label>
	</div>
<?php endif; ?>
