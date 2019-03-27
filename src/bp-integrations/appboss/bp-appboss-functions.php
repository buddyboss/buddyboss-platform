<?php
/**
 * AppBoss integration helpers
 *
 * @package BuddyBoss\AppBoss
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Output View Logs link for Integrations
 *
 * @param  {string}  $integration Name of integration to be used in url-param
 * @return  {html}
 */
function appBossRenderAnchor($integration) {

	printf(
		'<a href="%s" class="button-secondary">%s</a>',
		admin_url( "admin.php?page=bp-memberships-log&integration=$integration" ),
		__('View Logs', 'buddyboss')
	);
}

/**
 * Display information when LearnDash is not installed/activated
 *
 * @return {html}
 */
function appBossNoLearnDashText($integration) {
	echo sprintf(
		__("<h3>LearnDash is Required.</h3> <p>BuddyBoss Platform has integration settings for LearnDash-%s. If using LearnDash we add the ability to sync LearnDash groups with social groups, to generate course reports within social groups, and more. If using our BuddyBoss Theme we also include styling for LearnDash.</p>", 'buddyboss'), $integration);

}