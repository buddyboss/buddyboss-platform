<?php
/**
 * Readylaunch - Messages no threads template.
 *
 * @since   BuddyBoss 2.9.00
 * @version 1.0.0
 */
?>

<script type="text/html" id="tmpl-bp-messages-no-threads">
	<div class="no-message-wrap">
		<span class="bb-icons-rl-chats-circle"></span>
		<div class="no-message-content">
			<h3><?php esc_html_e( 'No Messages', 'buddyboss' ); ?></h3>
			<p><?php esc_html_e( 'When you have new messages, they will appear here.', 'buddyboss' ); ?></p>

			<div id="no-messages-archived-link" class="no-messages-links bp-hide">
				<a href="<?php echo esc_url( bb_get_messages_archived_url() ); ?>"><?php esc_html_e( 'View Archived Messages', 'buddyboss' ); ?></a>
			</div>

			<div id="no-messages-unarchived-link" class="no-messages-links bp-hide">
				<a href="<?php echo esc_url( trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() ) ); ?>"><?php esc_html_e( 'View Unarchived Messages', 'buddyboss' ); ?></a>
			</div>
		</div>
	</div>
</script>
