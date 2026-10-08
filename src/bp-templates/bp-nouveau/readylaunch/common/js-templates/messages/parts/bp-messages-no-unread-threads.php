<?php
/**
 * Readylaunch - Messages no unread threads template.
 *
 * @since   BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<script type="text/html" id="tmpl-bp-messages-no-unread-threads">
	<div class="no-message-wrap">
		<span class="bb-icons-rl-chats-circle"></span>
		<div class="no-message-content">
			<h3><?php esc_html_e( 'No Messages Found', 'buddyboss-platform' ); ?></h3>
			<p><?php esc_html_e( 'You haven\'t unread any conversations.', 'buddyboss-platform' ); ?></p>
		</div>
	</div>
</script>
