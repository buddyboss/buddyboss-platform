<?php
/**
 * No Access Reply Feedback Template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<div id="reply-private" class="bbp-reply-content">
	<div class="entry-content">
		<div class="bp-feedback info">
			<span class="bp-icon" aria-hidden="true"></span>
			<p><?php esc_html_e( 'You do not have permission to view this reply.', 'buddyboss' ); ?></p>
		</div>
	</div>
</div><!-- #forum-private -->
