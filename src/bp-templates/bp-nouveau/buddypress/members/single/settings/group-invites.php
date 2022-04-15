<?php
/**
 * The template for members settings ( Group Invites )
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/settings/group-invites.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */
?>

<h2 class="screen-heading group-invites-screen">
	<?php _e( 'Group Invites', 'buddyboss' ); ?>
</h2>

<?php
if ( 1 === bp_nouveau_groups_get_group_invites_setting() ) {
	 bp_nouveau_user_feedback( 'member-group-invites-friends-only' );
} else {
	 bp_nouveau_user_feedback( 'member-group-invites-all' );
}
?>


<form action="<?php echo esc_url( bp_displayed_user_domain() . bp_get_settings_slug() . '/invites/' ); ?>" name="account-group-invites-form" id="account-group-invites-form" class="standard-form" method="post">

	<div class="bp-checkbox-wrap">
		<input type="checkbox" name="account-group-invites-preferences" id="account-group-invites-preferences" class="bs-styled-checkbox" value="1" <?php checked( 1, bp_nouveau_groups_get_group_invites_setting() ); ?> />
		<label for="account-group-invites-preferences"><?php esc_html_e( 'Restrict Group invites to members who are connected.', 'buddyboss' ); ?></label>
	</div>

	<?php bp_nouveau_submit_button( 'member-group-invites' ); ?>

</form>
