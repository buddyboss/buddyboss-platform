<?php
/**
 * The template for members settings ( Delete Account )
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/settings/delete-account.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

bp_nouveau_member_hook( 'before', 'settings_template' ); ?>

<h2 class="screen-heading delete-account-screen warn">
	<?php esc_html_e( 'Delete Account', 'buddyboss' ); ?>
</h2>

<?php bp_nouveau_user_feedback( 'member-delete-account' ); ?>

<form action="<?php echo esc_url( bp_displayed_user_domain() . bp_get_settings_slug() . '/delete-account' ); ?>" name="account-delete-form" id="#account-delete-form" class="standard-form" method="post">

	<div class="bp-checkbox-wrap">
		<input id="delete-account-understand" class="disabled bs-styled-checkbox" type="checkbox" name="delete-account-understand" value="1" data-bp-disable-input="#delete-account-button" />
		<label class="warn" for="delete-account-understand"><?php esc_html_e( 'I understand the consequences.', 'buddyboss' ); ?></label>
	</div>

	<?php bp_nouveau_submit_button( 'member-delete-account' ); ?>

</form>

<?php
bp_nouveau_member_hook( 'after', 'settings_template' );
