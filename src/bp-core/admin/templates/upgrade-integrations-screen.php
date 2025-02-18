<?php
/**
 * BuddyBoss Upgrade Admin Screen.
 *
 * This file contains information about BuddyBoss Upgrade.
 *
 * @since   BuddyBoss 2.6.30
 *
 * @package BuddyBoss
 */

// phpcs:disable PHPCompatibility

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
			<h1 class="bb-advance-heading"><?php esc_html_e( 'Integrate your BuddyBoss site with hundreds of apps and plugins for limitless possibilities.', 'buddyboss' ); ?></h1>
			<div class="bb-integrations-section-listing"></div>
		</div>

		<script type="text/html" id="tmpl-bb-integrations">
			<div class="bb-integrations_filters_section">
				<div class="bb-integrations_search">
					<input type="search" name="search_integrations" placeholder="Search" value="<%= searchQuery ? searchQuery : '' %>" />
					<span class="bb-icon-l bb-icon-search <%= searchQuery !== '' ? 'clear-search' : '' %>"></span>
				</div>
				<div class="bb-integrations_filters">
					<% if ( categoriesArr ) { %>
						<select name="categories_integrations">
							<option value="all"><?php esc_html_e( 'All', 'buddyboss' ); ?></option>
							<% for (var i=0; i < categoriesArr.length; i++ ) { %>
								<option value="<%= categoriesArr[i][0] %>" <%= categoriesArr[i][0] == categoryId ? 'selected' : '' %> ><%= categoriesArr[i][1] %></option>

							<% } %>
						</select>
					<% } %>
					<% if ( collections && collections.length ) { %>
						<ul class="integrations_collection-sub integrations-lists">
							<% jQuery.each( collections, function( key, item ) { %>
								<li >
									<input class="radio integrationscollection styled" type="radio" value="<%= item.id %>" name="integrations_collection" <%= key == 0 || item.id == collectionId ? 'checked' : '' %> ><span><%= item.name %></span>
								</li>
							<% } ); %>
						</ul>
					<% } %>
				</div>
			</div>
			<div class="bb-integrations-listing">
				<% if ( data == null ) { %>
					<% for ( var i = 0; i < 5; i++ ) { %>
						<div class="integrations_single_holder loading">
							<div class="integrations_single_holder_avatar bb-bg-animation bb-loading-bg"></div>
							<div class="integrations_single_holder_block bb-bg-animation bb-loading-bg"></div>
							<div class="integrations_single_holder_block bb-bg-animation bb-loading-bg"></div>
						</div>
					<% } %>
				<% } else if ( data && data.length ) { %>
					<% currentCategory = null; %>
					<% jQuery.each( data, function( key, item ) { %>
						<% if ( categoryHeadings ) { %>
							<% if ( currentCategory != item.integrations_category[0] ) { %>
								<div class="integration_cat_title"><div class="cat_title"><%= categoriesObj[item.integrations_category[0]] %></div></div>
								<% currentCategory = item.integrations_category[0] %>
							<% } %>
						<% } %>
						<div class="integrations_single_holder">
							<div class="holder_integrations_img">
								<% if ( item && item.logo_image_url ) { %>
									<img alt="" class="lazyload-disable" src="<%= item.logo_image_url %>">
								<% } %>
								<% if ( item && item.collection_name ) { %>
									<div class="type_integrations_text type_compatible"><%= item.collection_name %></div>
								<% } %>
							</div>
							<div class="holder_integrations_desc">
								<div class="logo_title"><%= item.title.rendered %></div>
								<div class="short_desc">
									<p><%= item.short_description %></p>
								</div>
							</div>
							<a href="<%= item.link_url ? item.link_url : item.link %>" class="integration_readmore" target="_blank"><?php esc_html_e( 'Learn more', 'buddyboss' ); ?> <i class="bb-icon-l bb-icon-arrow-right"></i></a>
						</div>
					<% }); %>
				<% } else { %>
					<div class="bb-integrations-no-results">
						<i class="bb-icon-f bb-icon-exclamation-triangle"></i>
						<h2><?php esc_html_e( 'No Results Found', 'buddyboss' ); ?></h2>
						<p><?php esc_html_e( 'Sorry, there was no result found', 'buddyboss' ); ?></p>
					</div>
				<% } %>
			</div>
			<% if ( totalpages && totalpages > page ) { %>
				<div class="bb-integrations-listing_loadmore">
					<button class="bb-integrations_loadmore"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></button>
				</div>
			<% } %>

			<div class="bb-get-platform">
				<img alt="" class="guarantee-img" src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/upgrade/bb-guarantee.png' ); ?>" />
				<div class="bb-get-platform_details">
					<h3><?php echo wp_kses_post( __( 'Get Platform Pro for <br/>as low as $99', 'buddyboss' ) ); ?></h3>
					<p><?php echo wp_kses_post( __( 'If you are unsatisfied for any reason for up to 14 days following your <br/>purchase, contact us for a full refund. No questions asked.', 'buddyboss' ) ); ?></p>
					<div>
						<a href="https://www.buddyboss.com/bbwebupgrade" target="_blank" class="advance-nav-action advance-nav-action--upgrade">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M23.25 7.49999C23.2504 7.08886 23.1542 6.68339 22.9691 6.31625C22.7841 5.94911 22.5154 5.63057 22.1847 5.38629C21.854 5.14201 21.4705 4.97882 21.0652 4.90988C20.6599 4.84093 20.2441 4.86815 19.8512 4.98934C19.4583 5.11054 19.0994 5.32232 18.8034 5.60762C18.5074 5.89293 18.2825 6.24378 18.1469 6.6319C18.0113 7.02003 17.9688 7.43459 18.0227 7.84216C18.0767 8.24974 18.2256 8.63895 18.4575 8.97843L15.946 12.0722L13.6875 6.88124C14.0994 6.53457 14.3948 6.06964 14.5335 5.54946C14.6723 5.02928 14.6477 4.47902 14.4632 3.97325C14.2787 3.46749 13.9432 3.03069 13.502 2.72206C13.0609 2.41343 12.5356 2.24789 11.9972 2.24789C11.4588 2.24789 10.9335 2.41343 10.4924 2.72206C10.0512 3.03069 9.71569 3.46749 9.53118 3.97325C9.34667 4.47902 9.32213 5.02928 9.46089 5.54946C9.59965 6.06964 9.89499 6.53457 10.3069 6.88124L8.05408 12.0694L5.54251 8.97562C5.86515 8.50288 6.02443 7.93765 5.99612 7.36601C5.9678 6.79437 5.75343 6.24765 5.38566 5.80912C5.01788 5.37058 4.51686 5.06426 3.95889 4.9368C3.40092 4.80935 2.81659 4.86775 2.29488 5.1031C1.77317 5.33846 1.34268 5.73788 1.06897 6.24053C0.795258 6.74317 0.693327 7.32151 0.778699 7.88744C0.864072 8.45338 1.13207 8.97591 1.54188 9.37544C1.95169 9.77498 2.48084 10.0296 3.04876 10.1006L4.40626 18.2466C4.46462 18.5968 4.64532 18.9149 4.9162 19.1444C5.18708 19.374 5.5306 19.4999 5.88564 19.5H18.1144C18.4694 19.4999 18.8129 19.374 19.0838 19.1444C19.3547 18.9149 19.5354 18.5968 19.5938 18.2466L20.9503 10.1044C21.5852 10.0251 22.1692 9.71667 22.5927 9.23709C23.0162 8.75751 23.2499 8.13978 23.25 7.49999ZM12 3.74999C12.2225 3.74999 12.44 3.81597 12.625 3.93959C12.81 4.06321 12.9542 4.23891 13.0394 4.44448C13.1245 4.65004 13.1468 4.87624 13.1034 5.09447C13.06 5.3127 12.9528 5.51316 12.7955 5.67049C12.6382 5.82782 12.4377 5.93497 12.2195 5.97838C12.0013 6.02179 11.7751 5.99951 11.5695 5.91436C11.3639 5.82921 11.1882 5.68502 11.0646 5.50001C10.941 5.31501 10.875 5.0975 10.875 4.87499C10.875 4.57663 10.9935 4.29048 11.2045 4.0795C11.4155 3.86852 11.7016 3.74999 12 3.74999ZM2.25001 7.49999C2.25001 7.27749 2.316 7.05998 2.43961 6.87498C2.56323 6.68997 2.73893 6.54578 2.9445 6.46063C3.15006 6.37548 3.37626 6.3532 3.59449 6.39661C3.81272 6.44002 4.01318 6.54716 4.17051 6.7045C4.32784 6.86183 4.43499 7.06229 4.4784 7.28052C4.52181 7.49875 4.49953 7.72495 4.41438 7.93051C4.32923 8.13608 4.18504 8.31178 4.00003 8.4354C3.81503 8.55901 3.59752 8.62499 3.37501 8.62499C3.07665 8.62499 2.7905 8.50647 2.57952 8.29549C2.36854 8.08451 2.25001 7.79836 2.25001 7.49999ZM18.1144 18H5.88564L4.58064 10.1737L7.66783 13.9687C7.73775 14.0561 7.82632 14.1267 7.92706 14.1753C8.02779 14.224 8.13814 14.2495 8.25002 14.25C8.28388 14.2501 8.31771 14.248 8.35127 14.2434C8.47904 14.2261 8.60017 14.176 8.70297 14.0982C8.80577 14.0204 8.88677 13.9173 8.93814 13.7991L11.685 7.48031C11.8942 7.50654 12.1058 7.50654 12.315 7.48031L15.0619 13.7991C15.1133 13.9173 15.1943 14.0204 15.2971 14.0982C15.3999 14.176 15.521 14.2261 15.6488 14.2434C15.6823 14.248 15.7162 14.2501 15.75 14.25C15.8619 14.2495 15.9722 14.224 16.073 14.1753C16.1737 14.1267 16.2623 14.0561 16.3322 13.9687L19.4194 10.17L18.1144 18ZM20.625 8.62499C20.4025 8.62499 20.185 8.55901 20 8.4354C19.815 8.31178 19.6708 8.13608 19.5857 7.93051C19.5005 7.72495 19.4782 7.49875 19.5216 7.28052C19.565 7.06229 19.6722 6.86183 19.8295 6.7045C19.9869 6.54716 20.1873 6.44002 20.4055 6.39661C20.6238 6.3532 20.85 6.37548 21.0555 6.46063C21.2611 6.54578 21.4368 6.68997 21.5604 6.87498C21.684 7.05998 21.75 7.27749 21.75 7.49999C21.75 7.79836 21.6315 8.08451 21.4205 8.29549C21.2095 8.50647 20.9234 8.62499 20.625 8.62499Z" fill="white"/>
							</svg>
							<?php esc_html_e( 'Upgrade Now', 'buddyboss' ); ?>
						</a>
					</div>
				</div>
			</div>
		</script>
	</div>

</div>
<?php
// phpcs:enable PHPCompatibility
?>
