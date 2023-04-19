<?php
/**
 * The template for displaying link preview
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/members/bb-link-preview.php.
 *
 * @since [BBVERSION]
 */

?>
<script type="text/html" id="tmpl-bb-link-preview">
	<% if ( data.link_scrapping ) { %>
	<% if ( data.link_loading ) { %>
	<span class="bb-url-scrapper-loading bb-ajax-loader"><i class="bb-icon-l bb-icon-spinner animate-spin"></i><?php esc_html_e( 'Loading preview...', 'buddyboss' ) ?></span>
	<% } %>
	<% if ( data.link_success || data.link_error ) { %>
	<a title="<?php esc_html_e( 'Cancel Preview', 'buddyboss' ); ?>" href="#" id="bb-close-link-suggestion"><?php esc_html_e( 'Remove Preview', 'buddyboss' ); ?></i></a>
	<div class="bb-link-preview-container">

		<% if ( data.link_images && data.link_images.length && data.link_success && ! data.link_error && '' !== data.link_image_index ) { %>
		<div id="bb-url-scrapper-img-holder">
			<div class="bb-link-preview-image">
				<div class="bb-link-preview-image-cover">
					<img src="<%= data.link_images[data.link_image_index] %>"/>
				</div>
				<div class="bb-link-preview-icons">
					<%
					if ( data.link_images.length > 1 ) { %>
						<a data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Change image', 'buddyboss' ) ?>" href="#" class="icon-exchange toolbar-button bp-tooltip" id="icon-exchange"><i class="bb-icon-l bb-icon-exchange"></i></a>
					<% } %>
					<% if ( data.link_images.length ) { %>
						<a data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Remove image', 'buddyboss' ) ?>" href="#" class="icon-image-slash toolbar-button bp-tooltip" id="bb-link-preview-remove-image"><i class="bb-icon-l bb-icon-image-slash"></i></a>
					<% } %>
					<a data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Confirm', 'buddyboss' ) ?>" class="toolbar-button bp-tooltip" href="#" id="bb-link-preview-select-image">
						<i class="bb-icon-check bb-icon-l"></i>
					</a>
				</div>
			</div>
			<% if ( data.link_images.length > 1 ) { %>
			<div class="bb-url-thumb-nav">
				<button type="button" id="bb-url-prevPicButton"><span class="bb-icon-l bb-icon-angle-left"></span></button>
				<button type="button" id="bb-url-nextPicButton"><span class="bb-icon-l bb-icon-angle-right"></span></button>
				<div id="bb-url-scrapper-img-count">
					<?php esc_html_e( 'Image', 'buddyboss' ) ?> <%= data.link_image_index + 1 %>&nbsp;<?php esc_html_e( 'of', 'buddyboss' ) ?>&nbsp;<%= data.link_images.length %>
				</div>
			</div>
			<% } %>
		</div>
		<% } %>

		<% if ( data.link_success && ! data.link_error && data.link_url ) {%>
		<div class="bb-link-preview-info">
			<% var a = document.createElement('a');
				a.href = data.link_url;
				var hostname = a.hostname;
				var domainName = hostname.replace('www.', '' );
			%>

			<% if ( $.trim( data.link_title ) && data.link_description ) { %>
				<p class="bb-link-preview-link-name"><%= domainName %></p>
			<% } %>

			<% if ( data.link_success && ! data.link_error ) { %>
			<p class="bb-link-preview-title"><%= data.link_title %></p>
			<% } %>

			<% if ( data.link_success && ! data.link_error ) { %>
			<div class="bb-link-preview-excerpt"><p><%= data.link_description %></p></div>
			<% } %>
		</div>
	</div>
	<% } %>
	<% if ( data.link_error && ! data.link_success ) { %>
		<div id="bb-url-error" class="bb-url-error"><%= data.link_error_msg %></div>
	<% } %>
	<% } %>
	<% } %>
</script>
