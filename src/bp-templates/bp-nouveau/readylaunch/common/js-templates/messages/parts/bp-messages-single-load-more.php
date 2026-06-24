<?php
/**
 * Readylaunch - Messages single load more template.
 *
 * @since   BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<script type="text/html" id="tmpl-bp-messages-single-load-more">
	<button type="button" class="button" style="display: none;"><i class="dashicons dashicons-update animate-spin"></i><?php esc_html_e( 'Load previous messages', 'buddyboss-platform' ); ?></button>
</script>
