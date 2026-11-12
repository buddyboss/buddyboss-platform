<?php
/**
 * ReadyLaunch - Groups Admin template.
 *
 * This template handles the group administration interface
 * with management forms and settings.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div class="bb-rl-manage-group-container bb-rl-group-dashboard-panel">
	<form action="<?php bboss_group_admin_form_action(); ?>" name="group-settings-form" id="group-settings-form" class="standard-form search-form-has-reset bb-rl-group-manage-form" method="post" enctype="multipart/form-data">
		<?php bboss_nouveau_group_manage_screen(); ?>
	</form><!-- #group-settings-form -->
</div>
