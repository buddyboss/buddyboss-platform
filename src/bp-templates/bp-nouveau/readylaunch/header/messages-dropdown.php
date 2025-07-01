<?php
/**
 * ReadyLaunch - Header Messages Dropdown template.
 *
 * This template handles the messages dropdown display in the header for ReadyLaunch.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$menu_link            = trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() );
$unread_message_count = messages_get_unread_count();
?>
<div id="header-messages-dropdown-elem" class="dropdown-passive dropdown-right notification-wrap messages-wrap bb-message-dropdown-notification menu-item-has-children">
	<a href="javascript:void(0);" ref="notification_bell" class="notification-link" aria-label="<?php esc_html_e( 'Messages', 'buddyboss' ); ?>">
		<span data-balloon-pos="down" data-balloon="<?php esc_html_e( 'Messages', 'buddyboss' ); ?>" class="bb-member-unread-count-span-<?php echo esc_attr( bp_loggedin_user_id() ); ?>">
			<i class="bb-icons-rl-chat-teardrop-text"></i>
			<?php if ( $unread_message_count > 0 ) : ?>
				<span class="count"><?php echo esc_html( $unread_message_count ); ?></span>
			<?php endif; ?>
		</span>
	</a>
	<section class="notification-dropdown">
		<header class="notification-header flex items-center justify-between">
			<h2 class="title"><?php esc_html_e( 'Messages', 'buddyboss' ); ?></h2>
			<div class="notification-header-actions">
				<a href="<?php echo esc_url( $menu_link ); ?>" class="bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small bdelete-all delete-all">
					<?php esc_html_e( 'View All', 'buddyboss' ); ?>
				</a>
			</div>
		</header>

		<div class="header-ajax-container">
			<ul class="notification-list"></ul>
		</div>
	</section>
</div>
