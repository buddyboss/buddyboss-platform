<script type="text/html" id="tmpl-activity-link-preview">
	<# if ( data.link_scrapping ) { #>
	<# if ( data.link_loading ) { #>
	<span class="activity-url-scrapper-loading activity-ajax-loader"><?php esc_html_e( 'Loading preview...', 'buddyboss' ) ?></span>
	<# } #>
	<# if ( data.link_success || data.link_error ) { #>
	<div id="activity-url-scrapper" class="activity-link-preview-container">
		<# if ( data.link_images.length && data.link_success && ! data.link_error ) { #>
		<div id="activity-url-scrapper-img-holder">
			<div id="activity-url-scrapper-img" class="activity-link-preview-image">
				<img src="{{{data.link_images[data.link_image_index]}}}"/>
				<a title="Cancel Preview Image" href="#" id="activity-link-preview-close-image">
					<i class="dashicons dashicons-no-alt"></i>
				</a>
			</div>
			<# if ( data.link_images.length > 1 ) { #>
			<div class="activity-url-thumb-nav">
				<button type="button" id="activity-url-prevPicButton"><span class="dashicons dashicons-arrow-left-alt2"></span></button>
				<button type="button" id="activity-url-nextPicButton"><span class="dashicons dashicons-arrow-right-alt2"></span></button>
				<div id="activity-url-scrapper-img-count">
					<# print(data.link_image_index + 1) #>&nbsp;<?php esc_html_e( 'of', 'buddyboss' ) ?>&nbsp;<# print(data.link_images.length) #>
				</div>
			</div>
			<# } #>
		</div>
		<# } #>
		<div id="activity-url-scrapper-text-holder" class="activity-link-preview-content">
			<# if ( data.link_success && ! data.link_error ) { #>
			<div id="activity-url-scrapper-title" class="activity-link-preview-title">{{data.link_title}}</div>
			<div id="activity-url-scrapper-description" class="activity-link-preview-excerpt">{{{data.link_description}}}</div>
			<# } #>
			<# if ( data.link_error && ! data.link_success ) { #>
			<div id="activity-url-error" class="activity-url-error">{{data.link_error_msg}}</div>
			<# } #>
			<# if ( data.link_description.indexOf('iframe') > -1 ) { #>
			<a style="display: none;" title="Cancel Preview" href="#" id="activity-close-link-suggestion"><i class="dashicons dashicons-no-alt"></i></a>
			<# } else { #>
			<a title="Cancel Preview" href="#" id="activity-close-link-suggestion"><i class="dashicons dashicons-no-alt"></i></a>
			<# } #>

		</div>
	</div>
	<# } #>
	<# } #>
</script>
