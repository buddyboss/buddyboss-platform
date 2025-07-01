<?php
/**
 * No Search Results Feedback Template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<div class="bp-feedback info">
	<span class="bp-icon" aria-hidden="true"></span>
	<p><?php esc_html_e( 'No search results were found here.', 'buddyboss' ); ?></p>
</div>
