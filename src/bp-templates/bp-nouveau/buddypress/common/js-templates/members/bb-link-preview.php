<?php
/**
 * The template for displaying link preview
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/members/bb-link-preview.php.
 *
 * @since 2.3.60
 */

?>
<script type="text/html" id="tmpl-bb-link-preview">
<% if ( link_scrapping ) { %>
	<% if ( link_loading ) { %>
		<span class="bb-url-scrapper-loading bb-ajax-loader"><i class="bb-icon-l bb-icon-spinner animate-spin"></i><?php esc_html_e( 'Loading preview...', 'buddyboss' ) ?></span>
	<% } %>
	<% if ( link_success || link_error ) { %>
		<a title="<?php esc_html_e( 'Cancel Preview', 'buddyboss' ); ?>" href="#" id="bb-close-link-suggestion"><?php esc_html_e( 'Remove Preview', 'buddyboss' ); ?></a>
		<div class="bb-link-preview-container">

			<% if ( link_images && link_images.length && link_success && ! link_error && '' !== link_image_index ) { %>
				<div id="bb-url-scrapper-img-holder">
					<div class="bb-link-preview-image">
						<div class="bb-link-preview-image-cover">
							<img src="<%= link_images[link_image_index] %>"/>
						</div>
						<div class="bb-link-preview-icons">
							<%
							if ( link_images.length > 1 ) { %>
								<a data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Change image', 'buddyboss' ) ?>" href="#" class="icon-exchange toolbar-button bp-tooltip" id="icon-exchange"><i class="bb-icon-l bb-icon-exchange"></i></a>
							<% } %>
							<% if ( link_images.length ) { %>
								<a data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Remove image', 'buddyboss' ) ?>" href="#" class="icon-image-slash toolbar-button bp-tooltip" id="bb-link-preview-remove-image"><i class="bb-icon-l bb-icon-image-slash"></i></a>
							<% } %>
							<a data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Confirm', 'buddyboss' ) ?>" class="toolbar-button bp-tooltip" href="#" id="bb-link-preview-select-image">
								<i class="bb-icon-check bb-icon-l"></i>
							</a>
						</div>
					</div>
					<% if ( link_images.length > 1 ) { %>
						<div class="bb-url-thumb-nav">
							<button type="button" id="bb-url-prevPicButton"><span class="bb-icon-l bb-icon-angle-left"></span></button>
							<button type="button" id="bb-url-nextPicButton"><span class="bb-icon-l bb-icon-angle-right"></span></button>
							<div id="bb-url-scrapper-img-count">
								<?php esc_html_e( 'Image', 'buddyboss' ) ?> <%= link_image_index + 1 %>&nbsp;<?php esc_html_e( 'of', 'buddyboss' ) ?>&nbsp;<%= link_images.length %>
							</div>
						</div>
					<% } %>
				</div>
			<% } %>

			<% if ( link_success && ! link_error && link_url ) { %>
				<div class="bb-link-preview-info">
					<% var a = document.createElement('a');
						a.href = link_url;
						var hostname = a.hostname;
						var domainName = hostname.replace('www.', '' );
					%>

					<% if ( 'undefined' !== typeof link_title && link_title.trim() && link_description ) { %>
						<p class="bb-link-preview-link-name"><%= domainName %></p>
					<% } %>

					<% if ( link_success && ! link_error ) { %>
						<p class="bb-link-preview-title"><%= link_title %></p>
					<% } %>

					<% if ( link_success && ! link_error ) { %>
						<div class="bb-link-preview-excerpt"><p><%= link_description %></p></div>
					<% } %>
				</div>
			<% } %>
			<% if ( link_error && ! link_success ) { %>
				<div id="bb-url-error" class="bb-url-error"><%= link_error_msg %></div>
			<% } %>
		</div>
	<% } %>
<% } %>
</script>
