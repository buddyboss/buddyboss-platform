<?php

/**
 * No Access Feedback Part
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div id="topic-private" class="bbp-topic-content">
	<div class="entry-content">
		<div class="bp-feedback info">
			<span class="bp-icon" aria-hidden="true"></span>
			<p><?php esc_html_e( 'You do not have permission to view this discussion.', 'buddyboss-community-social-network-groups-forums-messaging' ); ?></p>
		</div>
	</div>
</div><!-- #forum-private -->
