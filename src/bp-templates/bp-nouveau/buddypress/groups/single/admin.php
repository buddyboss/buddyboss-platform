<?php
/**
 * BuddyBoss - Groups Admin
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */
?>

<div class="bp-manage-group-container">

	<?php bp_get_template_part( 'groups/single/parts/admin-subnav' ); ?>

	<form action="<?php bp_group_admin_form_action(); ?>" name="group-settings-form" id="group-settings-form" class="standard-form" method="post" enctype="multipart/form-data">

		<?php bp_nouveau_group_manage_screen(); ?>

	</form><!-- #group-settings-form -->

</div>