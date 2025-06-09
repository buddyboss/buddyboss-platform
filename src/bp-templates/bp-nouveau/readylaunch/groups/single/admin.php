<?php
/**
 * BuddyBoss - Groups Admin
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

?>
<div class="bb-rl-manage-group-container bb-rl-group-dashboard-panel">
	<form action="<?php bp_group_admin_form_action(); ?>" name="group-settings-form" id="group-settings-form" class="standard-form search-form-has-reset bb-rl-group-manage-form bb-rl-styled-select bb-rl-styled-select--default" method="post" enctype="multipart/form-data">
		<?php bp_nouveau_group_manage_screen(); ?>
	</form><!-- #group-settings-form -->
</div>
