<?php
/**
 * ReadyLaunch - Member Settings Capabilities template.
 *
 * This template handles the member capabilities settings.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_nouveau_member_hook( 'before', 'settings_template' ); ?>

<h2 class="screen-heading member-capabilities-screen">
	<?php esc_html_e( 'Member Capabilities', 'buddyboss' ); ?>
</h2>

<form action="<?php echo esc_url( bp_displayed_user_domain() . bp_get_settings_slug() . '/capabilities/' ); ?>" name="account-capabilities-form" id="account-capabilities-form" class="standard-form" method="post">

	<div class="bp-checkbox-wrap">
		<input type="checkbox" name="user-spammer" id="user-spammer" class="bs-styled-checkbox" value="1" <?php checked( bp_is_user_spammer( bp_displayed_user_id() ) ); ?> />
		<label for="user-spammer"><?php esc_html_e( 'This member is a spammer.', 'buddyboss' ); ?></label>
	</div>

	<?php bp_nouveau_submit_button( 'member-capabilities' ); ?>

</form>

<?php
bp_nouveau_member_hook( 'after', 'settings_template' );
