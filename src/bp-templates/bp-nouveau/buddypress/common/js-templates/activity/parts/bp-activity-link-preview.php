<script type="text/html" id="tmpl-activity-link-preview">
	<# if ( data.link_scrapping ) { #>
	<# if ( data.link_loading ) { #>
	<span class="activity-url-scrapper-loading activity-ajax-loader"><?php esc_html_e( 'Loading preview...', 'buddyboss' ) ?></span>
	<# } #>
	<# if ( data.link_success || data.link_error ) { #>
	<div class="activity-link-preview-container">

		<# if ( data.link_success && ! data.link_error ) { #>
		<p class="activity-link-preview-title">{{{data.link_title}}}</p>
		<# } #>

		<# if ( data.link_images && data.link_images.length && data.link_success && ! data.link_error ) { #>
		<div id="activity-url-scrapper-img-holder">
			<div class="activity-link-preview-image">
				<img src="{{{data.link_images[data.link_image_index]}}}"/>
				<a title="Cancel Preview Image" href="#" id="activity-link-preview-close-image">
					<i class="bb-icons bb-icon-close"></i>
				</a>
			</div>
			<# if ( data.link_images.length > 1 ) { #>
			<div class="activity-url-thumb-nav">
				<button type="button" id="activity-url-prevPicButton"><span class="bb-icons bb-icon-angle-left"></span></button>
				<button type="button" id="activity-url-nextPicButton"><span class="bb-icons bb-icon-angle-right"></span></button>
				<div id="activity-url-scrapper-img-count">
					<?php esc_html_e( 'Image', 'buddyboss' ) ?> <# print(data.link_image_index + 1) #>&nbsp;<?php esc_html_e( 'of', 'buddyboss' ) ?>&nbsp;<# print(data.link_images.length) #>
				</div>
			</div>
			<# } #>
		</div>
		<# } #>

		<# if ( data.link_success && ! data.link_error ) { #>
		<div class="activity-link-preview-excerpt"><p>{{{data.link_description}}}</p></div>
		<# } #>

		<# if ( data.link_error && ! data.link_success ) { #>
		<div id="activity-url-error" class="activity-url-error">{{data.link_error_msg}}</div>
		<# } #>
		<a title="<?php _e( 'Cancel Preview', 'buddyboss' ); ?>" href="#" id="activity-close-link-suggestion"><i class="bb-icons bb-icon-close"></i></a>
	</div>
	<# } #>
	<# } #>
</script>
