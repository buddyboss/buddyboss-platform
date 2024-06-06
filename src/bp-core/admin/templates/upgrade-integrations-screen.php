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
						<option value="ad_manager">Ad Manager</option>
						<option value="affiliate_management">Affiliate Management</option>
						<option value="anti_spam">Anti-spam</option>
						<option value="automation">Automation</option>
						<option value="bbPress">bbPress</option>
						<option value="buddyBoss_app">BuddyBoss App</option>
						<option value="buddyPress">BuddyPress</option>
						<option value="classifieds">Classifieds</option>
						<option value="comments_management">Comments Management</option>
						<option value="crm">CRM</option>
						<option value="custom_login">Custom Login</option>
						<option value="custom_redirect">Custom Redirect</option>
						<option value="dynamic_content">Dynamic Content</option>
						<option value="ecommerce">eCommerce</option>
						<option value="emails">Emails</option>
						<option value="events">Events</option>
						<option value="forms">Forms</option>
						<option value="gamification">Gamification</option>
						<option value="job_listings">Job Listings</option>
						<option value="Listings">Listings</option>
						<option value="live_streaming">Live Streaming</option>
						<option value="lms">LMS</option>
						<option value="marketing">Marketing</option>
						<option value="media_gallery">Media Gallery</option>
						<option value="membership_plugins">Membership Plugins</option>
						<option value="page_builder">Page Builder</option>
						<option value="polls">Polls</option>
						<option value="popup_builder">Popup Builder</option>
						<option value="profile_fields">Profile Fields</option>
						<option value="project_management">Project Management</option>
						<option value="question_answers">Question &amp; Answers</option>
						<option value="ratings">Ratings</option>
						<option value="security">Security</option>
						<option value="seo">SEO</option>
						<option value="social">Social</option>
						<option value="support_ticketing">Support Ticketing</option>
						<option value="translation">Translation</option>
					</select>
					<ul class="integrations_collection-sub integrations-lists">
						<li class="checked act">
							<input class="radio integrationscollection all styled" type="radio" value="all" name="integrations_collection" checked="" act=""><span>All</span>
						</li>
						<li class="order_1">
							<input class="radio integrationscollection official styled" type="radio" value="official" name="integrations_collection"><span>Official</span>
						</li>
						<li class="order_2">
							<input class="radio integrationscollection third-party styled" type="radio" value="third-party" name="integrations_collection"><span>Third-party</span>
						</li>
						<li class="order_3">
							<input class="radio integrationscollection compatible styled" type="radio" value="compatible" name="integrations_collection"><span>Compatible</span>
						</li>
					</ul>
				</div>
			</div>
			<div class="bb-integrations-listing">
				<% if ( data.length ) { %>
				<% jQuery.each( data, function( key, item ) { %>
				<% if ( 'title' === item.type ) { %>
				<div class="integration_cat_title"><div class="cat_title"><%= item.text %></div></div>
				<% } else { %>
				<div class="integrations_single_holder">
					<div class="holder_integrations_img">
						<img class="lazyload-disable" src="<%= item.logo_url %>">
						<div class="type_integrations_text type_compatible"><%= item.int_type %></div>
					</div>
					<div class="holder_integrations_desc">
						<div class="logo_title"><%= item.title %></div>
						<div class="short_desc">
							<p><%= item.desc %></p>
						</div>
					</div>
					<a href="<%= item.title.toLowerCase() %>" class="integration_readmore">Learn more <i class="bb-icon-l bb-icon-arrow-right"></i></a>
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
