<?php
/**
 * The template for members settings ( Subscription )
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/settings/subscriptions.php.
 *
 * @since BuddyBoss [BBVERSION]
 */

bp_nouveau_member_hook( 'before', 'settings_template' );

$data = bb_core_notification_preferences_data();
?>
	<h2 class="screen-heading email-settings-screen"><?php echo wp_kses_post( $data['screen_title'] ); ?></h2>

	<?php bp_get_template_part( 'members/single/parts/notification-subnav' ); ?>

<?php
bp_nouveau_member_hook( 'after', 'settings_template' );
