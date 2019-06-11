<?php
/**
 * BuddyBoss - Members Settings ( Notifications )
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */

bp_nouveau_member_hook( 'before', 'settings_template' ); ?>

<h2 class="screen-heading email-settings-screen">
	<?php _e( 'Email Preferences', 'buddyboss' ); ?>
</h2>

<p class="bp-help-text email-notifications-info">
	<?php _e( 'Choose your email notification preferences.', 'buddyboss' ); ?>
</p>

<form action="<?php echo esc_url( bp_displayed_user_domain() . bp_get_settings_slug() . '/notifications' ); ?>" method="post" class="standard-form" id="settings-form">

	<?php bp_nouveau_member_email_notice_settings(); ?>

	<?php bp_nouveau_submit_button( 'member-notifications-settings' ); ?>

</form>

<?php
bp_nouveau_member_hook( 'after', 'settings_template' );
