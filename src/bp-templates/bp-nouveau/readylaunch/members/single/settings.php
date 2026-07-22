<?php
/**
 * ReadyLaunch - Member Settings template.
 *
 * This template handles displaying member account settings sections.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div class="bb-rl-account-settings-section">
	<?php
	switch ( bp_current_action() ) :
		case 'notifications':
			if ( bp_action_variables() && 'subscriptions' === bp_action_variable( 0 ) ) {
				bp_get_template_part( 'members/single/settings/subscriptions' );
			} elseif ( bp_action_variables() ) {
				/**
				 * Fires to render a custom notifications settings sub-tab
				 * (e.g. a plugin-registered tab such as "Blog Subscriptions").
				 * The screen handler (`bp_settings_screen_notification()`)
				 * 404s any sub-action without a registered handler, so this
				 * only fires for recognised sub-tabs. Handlers echo their
				 * content.
				 *
				 * @since BuddyBoss [BBVERSION]
				 *
				 * @param string $action_variable The sub-tab slug.
				 */
				do_action( 'bb_member_settings_notifications_custom_tab', bp_action_variable( 0 ) );
			} else {
				bp_get_template_part( 'members/single/settings/notifications' );
			}
			break;
		case 'capabilities':
			bp_get_template_part( 'members/single/settings/capabilities' );
			break;
		case 'delete-account':
			bp_get_template_part( 'members/single/settings/delete-account' );
			break;
		case 'general':
			bp_get_template_part( 'members/single/settings/general' );
			break;
		case 'profile':
			bp_get_template_part( 'members/single/settings/profile' );
			break;
		case 'invites':
			bp_get_template_part( 'members/single/settings/group-invites' );
			break;
		case 'export':
			bp_get_template_part( 'members/single/settings/export-data' );
			break;
		case 'blocked-members':
			bp_get_template_part( 'members/single/settings/moderation' );
			break;
		default:
			bp_get_template_part( 'members/single/plugins' );
			break;
		endswitch;
	?>
</div>
