<?php
/**
 * ReadyLaunch - Member Settings Notifications template.
 *
 * This template handles the notification settings for members.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Added this condition for theme backward compatibility.
if ( bboss_action_variables() && 'subscriptions' === bboss_action_variable( 0 ) ) {
	bboss_get_template_part( 'members/single/settings/subscriptions' );
	return;
}

bboss_nouveau_member_hook( 'before', 'settings_template' );

$is_web_enabled = bboss_web_notification_enabled();
$is_app_enabled = bboss_app_notification_enabled();

$class = '';
if ( $is_web_enabled && $is_app_enabled ) {
	$class = 'bb-notification-column-3';
} elseif ( $is_web_enabled || $is_app_enabled ) {
	$class = 'bb-notification-column-2';
}
?>

<?php bboss_get_template_part( 'members/single/parts/notification-subnav' ); ?>

<form action="<?php echo esc_url( bboss_displayed_user_domain() . bboss_get_settings_slug() . '/notifications' ); ?>" method="post" class="standard-form <?php echo esc_attr( $class ); ?>" id="settings-form">
	<?php
	if ( false === bboss_enabled_legacy_email_preference() && ( $is_web_enabled || $is_app_enabled ) ) {
		?>
		<div class="notification_info">

			<div class="notification_type email_notification">
				<div class="notification_type_info">
					<h3><i class="bb-icons-rl-envelope-simple"></i><?php esc_attr_e( 'Email', 'buddyboss' ); ?></h3>
					<p><?php esc_attr_e( 'A notification sent to your inbox', 'buddyboss' ); ?></p>
				</div>
			</div><!-- .notification_type -->

			<?php if ( $is_web_enabled ) { ?>
			<div class="notification_type web_notification">
				<div class="notification_type_info">
					<h3><i class="bb-icons-rl-desktop"></i><?php esc_attr_e( 'Web', 'buddyboss' ); ?></h3>
					<p><?php esc_attr_e( 'A notification in the corner of your screen', 'buddyboss' ); ?></p>
				</div>
			</div><!-- .notification_type -->
			<?php } ?>

			<?php if ( $is_app_enabled ) { ?>
			<div class="notification_type app_notification">
				<div class="notification_type_info">
					<h3><i class="bb-icons-rl-device-mobile"></i><?php esc_attr_e( 'App', 'buddyboss' ); ?></h3>
					<p><?php esc_attr_e( 'A notification pushed to your mobile device', 'buddyboss' ); ?></p>
				</div>
			</div><!-- .notification_type -->
			<?php } ?>

			<p class="notification_learn_more"><a href="#"><?php esc_html_e( 'Learn more', 'buddyboss' ); ?><span class="bb-icon-chevron-down"></span></a></p>

		</div><!-- .notification_info -->
		<?php
	}

	bboss_nouveau_member_email_notice_settings();
	bboss_nouveau_submit_button( 'member-notifications-settings' );
	?>
</form>
<?php
bboss_nouveau_member_hook( 'after', 'settings_template' );
