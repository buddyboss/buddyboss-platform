<?php
/**
 * ReadyLaunch - Member Settings Delete Account template.
 *
 * This template handles the account deletion settings for members.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bboss_nouveau_member_hook( 'before', 'settings_template' ); ?>

<h2 class="screen-heading delete-account-screen warn">
	<?php esc_html_e( 'Delete Account', 'buddyboss' ); ?>
</h2>

<?php bboss_nouveau_user_feedback( 'member-delete-account' ); ?>

<form action="<?php echo esc_url( bboss_displayed_user_domain() . bboss_get_settings_slug() . '/delete-account' ); ?>" name="account-delete-form" id="account-delete-form" class="standard-form" method="post">

	<div class="bp-checkbox-wrap">
		<input id="delete-account-understand" class="disabled bs-styled-checkbox" type="checkbox" name="delete-account-understand" value="1" data-bp-disable-input="#delete-account-button" />
		<label class="warn" for="delete-account-understand"><?php esc_html_e( 'I understand the consequences.', 'buddyboss' ); ?></label>
	</div>

	<?php bboss_nouveau_submit_button( 'member-delete-account' ); ?>

</form>

<?php
bboss_nouveau_member_hook( 'after', 'settings_template' );
