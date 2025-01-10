<?php
/**
 * The Message template in the header for ReadyLaunch.
 *
 * @since   BuddyBoss [BBVERSION]
 *
 * @package ReadyLaunch
 */

$menu_link            = trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() );
$unread_message_count = messages_get_unread_count();
?>
<div id="header-messages-dropdown-elem" class="dropdown-passive dropdown-right notification-wrap messages-wrap bb-message-dropdown-notification menu-item-has-children">
	<a href="javascript:void(0);" ref="notification_bell" class="notification-link" <?php echo bb_elementor_pro_disable_page_transition(); ?> aria-label="<?php esc_html_e( 'Messages', 'buddyboss' ); ?>">
<!--		<span data-balloon-pos="down" data-balloon="--><?php //esc_html_e( 'Messages', 'buddyboss' ); ?><!--" class="bb-member-unread-count-span---><?php //echo esc_attr( bp_loggedin_user_id() ); ?><!--">-->
<!--			<i class="bb-icon-l bb-icon-inbox"></i>-->
<!--			--><?php //if ( $unread_message_count > 0 ) : ?>
<!--				<span class="count">--><?php //echo esc_html( $unread_message_count ); ?><!--</span>-->
<!--			--><?php //endif; ?>
<!--		</span>-->
		Messages
	</a>
	<section class="notification-dropdown">
		<header class="notification-header">
			<h2 class="title"><?php esc_html_e( 'Messages', 'buddyboss' ); ?></h2>
		</header>

		<div class="header-ajax-container">
			<ul class="notification-list"></ul>

			<div class="notification-footer">
				<a href="<?php echo esc_url( $menu_link ); ?>" class="delete-all">
					<?php esc_html_e( 'View Inbox', 'buddyboss' ); ?>
					<i class="bb-icon-l bb-icon-angle-right"></i>
				</a>
			</div>
		</div>
	</section>
</div>
