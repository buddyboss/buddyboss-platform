<?php
/**
 * BuddyBoss Upgrade Admin Screen.
 *
 * This file contains information about BuddyBoss Upgrade.
 *
 * @since   BuddyBoss [BBVERSION]
 *
 * @package BuddyBoss
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$bb_platform_pro_active = false;
$bb_theme_active        = false;
if ( function_exists( 'bbp_pro_is_license_valid' ) && bbp_pro_is_license_valid() ) {
	$bb_platform_pro_active = true;
}

if ( function_exists( 'buddyboss_theme' ) ) {
	$bb_theme_active = true;
}

?>
<div class="wrap">

	<div id="bb-integrations">
		<div class="bb-integrations-section">
			<h1>Integrate your BuddyBoss site with hundreds of apps and plugins for limitless possibilities.</h1>
			<div class="bb-integrations-section-listing"></div>
		</div>

		<script type="text/html" id="tmpl-bb-integrations">
			<div class="bb-integrations_filters_section">
				<div class="bb-integrations_search">
					<input type="search" name="search_integrations" placeholder="Search" />
				</div>
				<div class="bb-integrations_filters">
					<select name="categories_integrations">
						<option value="all">All</option>
						<% jQuery.each( categories, function( key, item ) { %>
							<option value="<%= item.id %>"><%= item.name %></option>
						<% }); %>
					</select>
					<ul class="integrations_collection-sub integrations-lists">
						<% jQuery.each( collections, function( key, item ) { %>
							<li >
								<input class="radio integrationscollection styled" type="radio" value="<%= item.id %>" name="integrations_collection" <%= key == 0  ? 'checked' : '' %> ><span><%= item.name %></span>
							</li>
						<% }); %>
					</ul>
				</div>
			</div>
			<div class="bb-integrations-listing">
				<% if ( data.length ) { %>
				<% jQuery.each( data, function( key, item ) { %>
				<% if ( 'title' === item.type ) { %>
				<div class="integration_cat_title"><div class="cat_title"><%= item.title.rendered %></div></div>
				<% } else { %>
				<div class="integrations_single_holder">
					<div class="holder_integrations_img">
						<% if (item && item._embedded && item._embedded['wp:featuredmedia'] && item._embedded['wp:featuredmedia'][0] ) { %>
							<img class="lazyload-disable" src="<%= item._embedded['wp:featuredmedia'][0].media_details.sizes.thumbnail.source_url %>">
						<% } %>
						<% if (item && item._embedded && item._embedded['wp:term'] && item._embedded['wp:term'][0] && item._embedded['wp:term'][0][0] && item._embedded['wp:term'][0][0].name ) { %>
							<div class="type_integrations_text type_compatible"><%= item._embedded['wp:term'][0][0].name %></div>
						<% } %>
					</div>
					<div class="holder_integrations_desc">
						<div class="logo_title"><%= item.title.rendered %></div>
						<div class="short_desc">
							<p><%= item.content.rendered %></p>
						</div>
					</div>
					<a href="<%= item.link %>" class="integration_readmore">Learn more <i class="bb-icon-l bb-icon-arrow-right"></i></a>
				</div>
				<% } %>
				<% }); %>
				<% } %>
			</div>
			<div class="bb-integrations-listing_loadmore">
				<button class="bb-integrations_loadmore">Load More</button>
			</div>

			<div class="bb-get-platform">
				<img class="guarantee-img" src="https://www.buddyboss.com/wp-content/uploads/2020/04/Advanced-Ads-Add-ons-e1588826603796.png" />
				<div class="bb-get-platform_details">
					<h3>Get Platform Pro for <br/>as low as $99</h3>
					<p>If you are unsatisfied for any reason for up to 14 days following your <br/>purchase, contact us for a full refund. No questions asked.</p>
					<div>
						<a href="#" class="bb-upgrade-btn">Upgrade Now</a>
					</div>
				</div>
			</div>
		</script>
	</div>

</div>
