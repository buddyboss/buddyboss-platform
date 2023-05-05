<?php
/**
 * The template for members settings ( Notifications )
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/settings/notifications.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Added this condition for theme backward compatibility.
if ( bp_action_variables() && 'subscriptions' === bp_action_variable( 0 ) ) {
	bp_get_template_part( 'members/single/settings/subscriptions' );
	return;
}

bp_nouveau_member_hook( 'before', 'settings_template' );

$data  = bb_core_notification_preferences_data();
$class = '';
if ( bb_web_notification_enabled() && bb_app_notification_enabled() ) {
	$class = 'bb-notification-column-3';
} elseif ( bb_web_notification_enabled() || bb_app_notification_enabled() ) {
	$class = 'bb-notification-column-2';
}
?>

<h2 class="screen-heading email-settings-screen"><?php echo wp_kses_post( $data['screen_title'] ); ?></h2>

<?php bp_get_template_part( 'members/single/parts/notification-subnav' ); ?>

<p class="bp-help-text email-notifications-info">
	<?php echo wp_kses_post( $data['screen_description'] ); ?>
</p>

<form action="<?php echo esc_url( bp_displayed_user_domain() . bp_get_settings_slug() . '/notifications' ); ?>" method="post" class="standard-form <?php echo esc_attr( $class ); ?>" id="settings-form">

	<?php if ( false === bb_enabled_legacy_email_preference() && ( bb_web_notification_enabled() || bb_app_notification_enabled() ) ) { ?>
		<div class="notification_info">

			<div class="notification_type email_notification">
				<span class="notification_type_icon">
					<i class="bb-icon bb-icon-mail"></i>
				</span>

				<div class="notification_type_info">
					<h3><?php esc_attr_e( 'Email', 'buddyboss' ); ?></h3>
					<p><?php esc_attr_e( 'A notification sent to your inbox', 'buddyboss' ); ?></p>
				</div>
			</div><!-- .notification_type -->

			<?php if ( bb_web_notification_enabled() ) { ?>
			<div class="notification_type web_notification">
				<span class="notification_type_icon">
					<i class="bb-icon bb-icon-monitor"></i>
				</span>

				<div class="notification_type_info">
					<h3><?php esc_attr_e( 'Web', 'buddyboss' ); ?></h3>
					<p><?php esc_attr_e( 'A notification in the corner of your screen', 'buddyboss' ); ?></p>
				</div>
			</div><!-- .notification_type -->
			<?php } ?>

			<?php if ( bb_app_notification_enabled() ) { ?>
			<div class="notification_type app_notification">
				<span class="notification_type_icon">
					<i class="bb-icon bb-icon-smartphone"></i>
				</span>

				<div class="notification_type_info">
					<h3><?php esc_attr_e( 'App', 'buddyboss' ); ?></h3>
					<p><?php esc_attr_e( 'A notification pushed to your mobile device', 'buddyboss' ); ?></p>
				</div>
			</div><!-- .notification_type -->
			<?php } ?>

			<p class="notification_learn_more"><a href="#"><?php esc_html_e( 'Learn more', 'buddyboss' ); ?><span class="bb-icon-chevron-down"></span></a></p>

		</div><!-- .notification_info -->
	<?php } ?>

	<?php bp_nouveau_member_email_notice_settings(); ?>

	<?php bp_nouveau_submit_button( 'member-notifications-settings' ); ?>

</form>

<?php
bp_nouveau_member_hook( 'after', 'settings_template' );
