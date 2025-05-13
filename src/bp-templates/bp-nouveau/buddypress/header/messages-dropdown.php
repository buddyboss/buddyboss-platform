<?php
/**
 * The Message template in the header for ReadyLaunch.
 *
 * @since   BuddyBoss [BBVERSION]
 *
 * @package ReadyLaunch
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$menu_link            = trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() );
$unread_message_count = messages_get_unread_count();
?>
<div id="bb-rl-header-messages-dropdown-elem" class="dropdown-passive dropdown-right bb-rl-notification-wrap messages-wrap bb-message-dropdown-notification menu-item-has-children">
	<a href="javascript:void(0);" ref="notification_bell" class="bb-rl-notification-link" aria-label="<?php esc_html_e( 'Messages', 'buddyboss' ); ?>">
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
