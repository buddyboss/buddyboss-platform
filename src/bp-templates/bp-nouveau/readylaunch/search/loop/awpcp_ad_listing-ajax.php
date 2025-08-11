<?php
/**
 * ReadyLaunch - Search Loop AWPCP Ad Listing AJAX template.
 *
 * The template for AJAX search results for AWPCP ad listings.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<div class="bp-search-ajax-item bp-search-ajax-item_post">
	<a href="$url_showad">
		<div class="item">
			<div class="item-title">$ad_title</div>
			<div class="item-desc">$addetailssummary</div>
		</div>
	</a>
</div>
