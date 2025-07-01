<?php
/**
 * The ReadyLaunch template for BuddyBoss - Activity Post Form.
 *
 * This template handles the activity post form display and initialization.
 * It includes capability checks, script enqueuing, and form placeholder setup.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/*
 * Template tag to prepare the activity post form checks capability and enqueue needed scripts.
 */
bp_nouveau_before_activity_post_form();

$current_action = bp_current_action();

if ( bp_is_user_activity() && ! bp_is_activity_tabs_active() && ! empty( $current_action ) ) {
	$current_action = explode( ',', $current_action );
}

$bp_hide_class = (
	(
		! bb_user_can_create_activity() &&
		! bp_is_group_activity()
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
if ( ! bp_is_active( 'media' ) ) {
	$media_enabled_class = ' media-off';
}
?>
	<h2 class="bb-rl-screen-reader-text"><?php esc_html_e( 'Post Update', 'buddyboss' ); ?></h2>
	<div id="bb-rl-activity-form-placeholder" class="bb-rl-activity-form-placeholder-<?php echo esc_attr( $media_enabled_class . $bp_hide_class ); ?>"></div>
	<div id="bb-rl-activity-form" class="bb-rl-activity-update-form<?php echo esc_attr( $media_enabled_class . $bp_hide_class ); ?>"></div>
<?php
/*
 * Template tag to load the JavaScript templates of the Post form UI.
 */
bp_nouveau_after_activity_post_form();
