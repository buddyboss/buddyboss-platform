<?php
/**
 * ReadyLaunch - Activity Link Preview JS Templates.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since   BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<script type="text/html" id="tmpl-activity-link-preview">
	<# if ( data.link_scrapping ) { #>

		<# if ( data.link_loading ) { #>
			<span class="activity-url-scrapper-loading activity-ajax-loader"><i class="bb-rl-loader"></i>
				<?php esc_html_e( 'Loading preview...', 'buddyboss' ); ?>
			</span>
		<# } #>

		<# if ( data.link_success || data.link_error ) { #>
			<a title="<?php esc_html_e( 'Cancel Preview', 'buddyboss' ); ?>" href="#" id="activity-close-link-suggestion"><?php esc_html_e( 'Remove preview', 'buddyboss' ); ?></i></a>
			<div class="activity-link-preview-container">

				<# if ( data.link_images && data.link_images.length && data.link_success && ! data.link_error && '' !== data.link_image_index ) { #>
					<div id="activity-url-scrapper-img-holder">
						<div class="activity-link-preview-image">
							<div class="activity-link-preview-image-cover">
								<img src="{{{data.link_images[data.link_image_index]}}}"/>
							</div>
							<div class="activity-link-preview-icons">
								<# if ( data.link_images.length > 1 ) { #>
									<a data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Change image', 'buddyboss' ); ?>" href="#" class="icon-exchange bb-rl-toolbar-button bp-tooltip" id="icon-exchange"><i class="bb-icons-rl-arrows-left-right"></i></a>
								<# } #>
								<# if ( data.link_images.length ) { #>
									<a data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Remove image', 'buddyboss' ); ?>" href="#" class="icon-image-slash bb-rl-toolbar-button bp-tooltip" id="activity-link-preview-remove-image"><i class="bb-icons-rl-camera-slash"></i></a>
								<# } #>
								<a data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Confirm', 'buddyboss' ); ?>" class="bb-rl-toolbar-button bp-tooltip" href="#" id="activity-link-preview-select-image">
									<i class="bb-icons-rl-check"></i>
								</a>
							</div>
						</div>
						<# if ( data.link_images.length > 1 ) { #>
							<div class="activity-url-thumb-nav">
								<button type="button" id="activity-url-prevPicButton"><span class="bb-icons-rl-caret-left"></span></button>
								<button type="button" id="activity-url-nextPicButton"><span class="bb-icons-rl-caret-right"></span></button>
								<div id="activity-url-scrapper-img-count">
									<?php esc_html_e( 'Image', 'buddyboss' ); ?> <# print(data.link_image_index + 1) #>&nbsp;<?php esc_html_e( 'of', 'buddyboss' ); ?>&nbsp;<# print(data.link_images.length) #>
								</div>
							</div>
						<# } #>
					</div>
				<# } #>

				<# if ( data.link_success && ! data.link_error && data.link_url ) {#>
					<div class="activity-link-preview-info">
						<# var a = document.createElement('a');
							a.href = data.link_url;
							var hostname = a.hostname;
							var domainName = hostname.replace('www.', '' );
						#>

						<# if ( 'undefined' !== typeof data.link_title && data.link_title.trim() && data.link_description ) { #>
							<p class="activity-link-preview-link-name">{{domainName}}</p>
						<# } #>

						<# if ( data.link_success && ! data.link_error ) { #>
							<p class="activity-link-preview-title">{{{data.link_title}}}</p>
						<# } #>

						<# if ( data.link_success && ! data.link_error ) { #>
							<div class="activity-link-preview-excerpt"><p>{{{data.link_description}}}</p></div>
						<# } #>
					</div>
				<# } #>
			</div>

			<# if ( data.link_error && ! data.link_success ) { #>
				<div id="activity-url-error" class="activity-url-error">{{data.link_error_msg}}</div>
			<# } #>

		<# } #>

	<# } #>
</script>
