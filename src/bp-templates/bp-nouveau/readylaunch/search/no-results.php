<?php
/**
 * ReadyLaunch - Search No Results template.
 *
 * Template for displaying when no search results are found.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div class="bp-search-results bp-feedback info">
	<span class="bp-icon" aria-hidden="true"></span>
	<p><?php esc_html_e( 'Sorry, there were no results found.', 'buddyboss' ); ?></p>
</div>
