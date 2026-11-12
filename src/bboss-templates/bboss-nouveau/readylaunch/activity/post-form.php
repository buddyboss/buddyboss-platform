<?php
/**
 * The ReadyLaunch template for BuddyBoss - Activity Post Form.
 *
 * This template handles the activity post form display and initialization.
 * It includes capability checks, script enqueuing, and form placeholder setup.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/*
 * Template tag to prepare the activity post form checks capability and enqueue needed scripts.
 */
bboss_nouveau_before_activity_post_form();

$current_action = bboss_current_action();

if ( bboss_is_user_activity() && ! bboss_is_activity_tabs_active() && ! empty( $current_action ) ) {
	$current_action = explode( ',', $current_action );
}

$bp_hide_class = (
	(
		! bboss_user_can_create_activity() &&
		! bboss_is_group_activity()
	) ||
	(
		! empty( $current_action ) &&
		! is_array( $current_action ) &&
		'just-me' !== $current_action &&
		'activity' !== $current_action
	)
) ? ' bp-hide is-bp-hide' : '';

// Added media off class.
$media_enabled_class = '';
if ( ! bboss_is_active( 'media' ) ) {
	$media_enabled_class = ' media-off';
}
?>
	<h2 class="bb-rl-screen-reader-text"><?php esc_html_e( 'Post Update', 'buddyboss-platform' ); ?></h2>
	<div id="bb-rl-activity-form-placeholder" class="bb-rl-activity-form-placeholder-<?php echo esc_attr( $media_enabled_class . $bp_hide_class ); ?>"></div>
	<div id="bb-rl-activity-form" class="bb-rl-activity-update-form<?php echo esc_attr( $media_enabled_class . $bp_hide_class ); ?>"></div>
<?php
/*
 * Template tag to load the JavaScript templates of the Post form UI.
 */
bboss_nouveau_after_activity_post_form();
