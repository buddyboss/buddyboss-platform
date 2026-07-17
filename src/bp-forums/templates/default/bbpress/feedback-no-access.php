<?php

/**
 * No Access Feedback Part
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div id="forum-private" class="bbp-forum-content">
	<h1 class="entry-title"><?php esc_html_e( 'Private', 'buddyboss-platform' ); ?></h1>
	<div class="entry-content">
		<div class="bp-feedback info">
			<span class="bp-icon" aria-hidden="true"></span>
			<p><?php esc_html_e( 'You do not have permission to view this forum.', 'buddyboss-platform' ); ?></p>
		</div>
	</div>
</div><!-- #forum-private -->
