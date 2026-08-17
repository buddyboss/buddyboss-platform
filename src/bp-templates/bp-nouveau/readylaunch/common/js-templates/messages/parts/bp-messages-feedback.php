<?php
/**
 * Readylaunch - Messages feedback template.
 *
 * @since   BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<script type="text/html" id="tmpl-bp-messages-feedback">
	<div class="bb-rl-notice bb-rl-notice--{{data.type}}">
		<span class="bb-icons-rl-fill" aria-hidden="true"></span>
		<p>{{{data.message}}}</p>
	</div>
</script>