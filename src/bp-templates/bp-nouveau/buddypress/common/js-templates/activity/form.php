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
			<div id="activity-url-scrapper-url">{{data.link_url}}</div>
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

<script type="text/html" id="tmpl-activity-post-elements-buttons">
	<ul>
		<?php if ( bp_is_activity_link_preview_active() ):  ?>
			<li>
				<a href="#" id="activity-link-preview-button" class="dashicons dashicons-admin-links"></a>
			</li>
		<?php endif; ?>
	</ul>
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
