<?php
/**
 * BP Nouveau messages search no threads template
 *
 * This template can be overridden by copying it to yourtheme/buddypress/messages/parts/bp-messages-search-no-threads.php.
 *
 * @since   BuddyBoss 2.1.4
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<script type="text/html" id="tmpl-bp-messages-search-no-threads">
	<div class="no-message-wrap">
		<span class="bb-icon bb-icon-f bb-icon-comments-slash"></span>
		<div class="no-message-content">
			<h3><?php esc_html_e( 'No Messages Found', 'buddyboss-platform' ); ?></h3>
			<p><?php esc_html_e( 'We couldn\'t find any messages matching your search term.', 'buddyboss-platform' ); ?></p>
		</div>
	</div>
</script>
