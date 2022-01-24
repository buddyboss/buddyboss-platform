<?php
/**
 * The template for members settings ( Capabilities )
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/settings/capabilities.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

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
