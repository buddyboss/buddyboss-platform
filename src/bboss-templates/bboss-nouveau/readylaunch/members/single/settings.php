<?php
/**
 * ReadyLaunch - Member Settings template.
 *
 * This template handles displaying member account settings sections.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div class="bb-rl-account-settings-section">
	<?php
	switch ( bboss_current_action() ) :
		case 'notifications':
			if ( bboss_action_variables() && 'subscriptions' === bboss_action_variable( 0 ) ) {
				bboss_get_template_part( 'members/single/settings/subscriptions' );
			} elseif ( bboss_action_variables() ) {
				/**
				 * Fires to render a custom notifications settings sub-tab
				 * (e.g. a plugin-registered tab such as "Blog Subscriptions").
				 * The screen handler (`bboss_settings_screen_notification()`)
				 * 404s any sub-action without a registered handler, so this
				 * only fires for recognised sub-tabs. Handlers echo their
				 * content.
				 *
				 * @since BuddyBoss 3.2.0
				 *
				 * @param string $action_variable The sub-tab slug.
				 */
				do_action( 'bb_member_settings_notifications_custom_tab', bboss_action_variable( 0 ) );
			} else {
				bboss_get_template_part( 'members/single/settings/notifications' );
			}
			break;
		case 'capabilities':
			bboss_get_template_part( 'members/single/settings/capabilities' );
			break;
		case 'delete-account':
			bboss_get_template_part( 'members/single/settings/delete-account' );
			break;
		case 'security':
			bboss_get_template_part( 'members/single/settings/security' );
			break;
		case 'general':
			bboss_get_template_part( 'members/single/settings/general' );
			break;
		case 'profile':
			bboss_get_template_part( 'members/single/settings/profile' );
			break;
		case 'invites':
			bboss_get_template_part( 'members/single/settings/group-invites' );
			break;
		case 'export':
			bboss_get_template_part( 'members/single/settings/export-data' );
			break;
		case 'blocked-members':
			bboss_get_template_part( 'members/single/settings/moderation' );
			break;
		default:
			bboss_get_template_part( 'members/single/plugins' );
			break;
		endswitch;
	?>
</div>
