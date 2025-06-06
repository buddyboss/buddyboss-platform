<?php
/**
 * Template for displaying the search results of the no results
 *
 * This template displays a message when no search results are found.
 * It provides user feedback with an appropriate icon and message.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div class="bp-search-results bp-feedback info">
	<span class="bp-icon" aria-hidden="true"></span>
	<p><?php esc_html_e( 'Sorry, there were no results found.', 'buddyboss' ); ?></p>
</div>
