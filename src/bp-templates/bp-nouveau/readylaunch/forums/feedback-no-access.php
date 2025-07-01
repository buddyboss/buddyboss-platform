<?php
/**
 * No Access Feedback Template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<div id="forum-private" class="bbp-forum-content">
	<h1 class="entry-title"><?php esc_html_e( 'Private', 'buddyboss' ); ?></h1>
	<div class="entry-content">
		<div class="bp-feedback info">
			<span class="bp-icon" aria-hidden="true"></span>
			<p><?php esc_html_e( 'You do not have permission to view this forum.', 'buddyboss' ); ?></p>
		</div>
	</div>
</div><!-- #forum-private -->
