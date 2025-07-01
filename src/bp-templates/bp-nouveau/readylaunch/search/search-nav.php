<?php
/**
 * ReadyLaunch - Search Navigation template.
 *
 * Template for displaying the search navigation.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<nav class="search_filters bb-rl-network-search-subnav item-list-tabs main-navs dir-navs bp-navs no-ajax" role="navigation">
	<ul class="component-navigation search-nav">
		<?php bp_search_filters(); ?>
	</ul>
</nav>
