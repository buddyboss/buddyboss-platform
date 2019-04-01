<?php
/**
 * Activity Post form JS Templates
 *
 * @version 3.1.0
 */
?>

<script type="text/html" id="tmpl-activity-post-form-feedback">
	<span class="bp-icon" aria-hidden="true"></span><p>{{{data.message}}}</p>
</script>

<script type="text/html" id="tmpl-activity-media">
    <div class="dropzone closed" id="activity-post-media-uploader"></div>
</script>

<script type="text/html" id="tmpl-activity-link-preview">
	<# if ( data.link_scrapping ) { #>
	<input type="url" id="activity-link-preview-url" value="{{data.link_url}}" />
	<# } #>
	<# if ( data.link_scrapping ) { #>
	<# if ( data.link_loading ) { #>
	<span class="activity-url-scrapper-loading activity-ajax-loader"><?php esc_html_e( 'Processing...', 'buddyboss' ) ?></span>
	<# } #>
	<# if ( data.link_success || data.link_error ) { #>
	<div id="activity-url-scrapper">
		<# if ( data.link_images.length && data.link_success && ! data.link_error ) { #>
		<div id="activity-url-scrapper-img-holder">
			<div id="activity-url-scrapper-img">
				<img src="{{{data.link_images[data.link_image_index]}}}"/>
				<a title="Cancel Preview Image" href="#" id="activity-link-preview-close-image">
					<i class="dashicons dashicons-no-alt"></i>
				</a>
			</div>
			<div class="activity-url-thumb-nav">
				<input type="button" id="activity-url-prevPicButton" value="<"/>
				<input type="button" id="activity-url-nextPicButton" value=">">
				<div id="activity-url-scrapper-img-count">
					<# print(data.link_image_index + 1) #>&nbsp;<?php esc_html_e( 'of', 'buddyboss' ) ?>&nbsp;<# print(data.link_images.length) #>
				</div>
			</div>
		</div>
		<# } #>
		<div id="activity-url-scrapper-text-holder">
			<# if ( data.link_success && ! data.link_error ) { #>
			<div id="activity-url-scrapper-title">{{data.link_title}}</div>
			<div id="activity-url-scrapper-description">{{data.link_description}}</div>
			<# } #>
			<# if ( data.link_error && ! data.link_success ) { #>
			<div id="activity-url-error">{{data.link_error_msg}}</div>
			<# } #>
			<a title="Cancel Preview" href="#" id="activity-close-link-suggestion">
				<i class="dashicons dashicons-no-alt"></i>
			</a>
		</div>
	</div>
	<# } #>
	<# } #>
</script>

<script type="text/html" id="tmpl-activity-attached-gif">
	<div class="gif-image-container">
		<# if ( ! _.isEmpty( data.gif_data ) ) { #>
			<img src="{{data.gif_data.images.original.url}}" alt="">
		<# } #>
	</div>
	<div class="gif-image-remove gif-image-overlay">
		<span class="dashicons dashicons-no"></span>
	</div>
</script>

<script type="text/html" id="tmpl-gif-result-item">
	<a class="found-media-item" href="{{{data.images.original.url}}}" data-id="{{data.id}}">
		<img src="{{{data.images.fixed_width.url}}}" />
	</a>
</script>

<script type="text/html" id="tmpl-gif-media-search-dropdown">
	<div class="gif-search-content">
		<div class="gif-search-query">
			<input type="text" class="search-query-input" />
			<span class="search-icon"></span>
		</div>
		<div class="gif-search-results" id="gif-search-results">
			<ul class="gif-search-results-list" >
			</ul>
		</div>
	</div>
</script>

<script type="text/html" id="tmpl-whats-new-toolbar">
	<?php if ( bp_is_active( 'media' ) ): ?>
        <div class="post-elements-buttons-item post-media">
			<a href="#" id="activity-media-button" class="toolbar-button bp-tooltip" data-bp-tooltip="<?php _e('Attach a photo', 'buddyboss'); ?>">
				<span class="dashicons dashicons-admin-media"></span>
			</a>
		</div>
	<?php endif; ?>
	<?php if ( bp_is_activity_link_preview_active() ): ?>
        <div class="post-elements-buttons-item post-link">
			<a href="#" id="activity-link-preview-button" class="toolbar-button bp-tooltip" data-bp-tooltip="<?php _e('Post a link', 'buddyboss'); ?>">
				<span class="dashicons dashicons-admin-links"></span>
			</a>
		</div>
	<?php endif; ?>
	<?php if ( bp_is_activity_gif_active() ): ?>
        <div class="post-elements-buttons-item post-gif">
			<div class="gif-media-search">
				<a href="#" id="activity-gif-button" class="toolbar-button bp-tooltip" data-bp-tooltip="<?php _e('Post a GIF', 'buddyboss'); ?>"><span class="dashicons dashicons-smiley"></span></a>
				<div class="gif-media-search-dropdown"></div>
			</div>
		</div>
	<?php endif; ?>
	<div class="post-elements-buttons-item post-emoji bp-tooltip" data-bp-tooltip="<?php _e('Insert an emoji', 'buddyboss'); ?>"></div>
</script>

<script type="text/html" id="tmpl-activity-post-form-avatar">
	<# if ( data.display_avatar ) { #>
		<a href="{{data.user_domain}}">
			<img src="{{data.avatar_url}}" class="avatar user-{{data.user_id}}-avatar avatar-{{data.avatar_width}} photo" width="{{data.avatar_width}}" height="{{data.avatar_width}}" alt="{{data.avatar_alt}}" />
		</a>
	<# } #>
</script>

<script type="text/html" id="tmpl-activity-post-form-options">
	<?php bp_nouveau_activity_hook( '', 'post_form_options' ); ?>
</script>

<script type="text/html" id="tmpl-activity-post-form-buttons">
	<button type="button" class="button dashicons {{data.icon}}" data-button="{{data.id}}"><span class="bp-screen-reader-text">{{data.caption}}</span></button>
</script>

<script type="text/html" id="tmpl-activity-target-item">
	<# if ( data.selected ) { #>
		<input type="hidden" value="{{data.id}}">
	<# } #>

	<# if ( data.avatar_url ) { #>
		<img src="{{data.avatar_url}}" class="avatar {{data.object_type}}-{{data.id}}-avatar photo" alt="" />
	<# } #>

	<span class="bp-item-name">{{data.name}}</span>

	<# if ( data.selected ) { #>
		<button type="button" class="bp-remove-item dashicons dashicons-no" data-item_id="{{data.id}}">
			<span class="bp-screen-reader-text"><?php esc_html_e( 'Remove item', 'buddyboss' ); ?></span>
		</button>
	<# } #>
</script>
