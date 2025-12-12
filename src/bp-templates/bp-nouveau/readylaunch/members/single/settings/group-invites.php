<?php
/**
 * ReadyLaunch - Member Settings Group Invites template.
 *
 * This template handles the group invitation settings for members.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$group_invites_setting = bp_nouveau_groups_get_group_invites_setting();
if ( 1 === $group_invites_setting ) {
	bp_nouveau_user_feedback( 'member-group-invites-friends-only' );
} else {
	bp_nouveau_user_feedback( 'member-group-invites-all' );
}
?>

<form action="<?php echo esc_url( bp_displayed_user_domain() . bp_get_settings_slug() . '/invites/' ); ?>" name="account-group-invites-form" id="account-group-invites-form" class="standard-form" method="post">
	<div class="bp-checkbox-wrap">
		<input type="checkbox" name="account-group-invites-preferences" id="account-group-invites-preferences" class="bs-styled-checkbox" value="1" <?php checked( 1, $group_invites_setting ); ?> />
		<label for="account-group-invites-preferences"><?php esc_html_e( 'Restrict Group invites to members who are connected.', 'buddyboss' ); ?></label>
	</div>
	<?php bp_nouveau_submit_button( 'member-group-invites' ); ?>
</form>
