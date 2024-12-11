<?php
/**
 * BuddyBoss Notification System Header.
 *
 * Header notifies customers about major releases, significant changes, or special offers.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Combine both active and dismissed notifications with a marker.
$all_notifications = array();

$active_notifications = false;
if ( ! empty( $notifications['active'] ) ) {
	$active_notifications = true;
	foreach ( $notifications['active'] as $active_notification ) {
		$all_notifications[] = array_merge( $active_notification, array( 'status' => 'active' ) );
	}
}

$dismissed_notifications = false;
if ( ! empty( $notifications['dismissed'] ) ) {
	$dismissed_notifications = true;
	foreach ( $notifications['dismissed'] as $dismissed_notification ) {
		$all_notifications[] = array_merge( $dismissed_notification, array( 'status' => 'dismissed' ) );
	}
}

$total_notifications           = ! empty( $all_notifications ) ? count( $all_notifications ) : 0;
$total_active_notifications    = $active_notifications ? count( $notifications['active'] ) : 0;
$total_dismissed_notifications = $dismissed_notifications ? count( $notifications['dismissed'] ) : 0;
?>
<div class="bb-notice-header-wrapper">
	<div class="bb-admin-header">
		<div class="bb-admin-header__logo">
			<img alt="" class="gravatar" src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/admin/bb-logo.png' ); ?>" />
		</div>
		<div class="bb-admin-header__nav">
			<div class="bb-admin-nav">
				<?php do_action( 'bb_admin_header_actions' ); ?>
				<a href="<?php echo esc_url( bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
						),
						'admin.php'
					)
				) ); ?>" class="bb-admin-nav__button bb-admin-nav__help"><i class="bb-icon-l bb-icon-question"></i></a>
			</div>
		</div>
	</div>

</div>
