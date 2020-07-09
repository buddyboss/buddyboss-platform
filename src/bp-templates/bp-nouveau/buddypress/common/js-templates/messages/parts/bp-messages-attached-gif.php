<script type="text/html" id="tmpl-messages-attached-gif">
	<# if ( ! _.isUndefined( data.gif_data.images ) ) { #>
	<div class="gif-image-container">
		<img src="{{data.gif_data.images.original.url}}" alt="">
	</div>
	<div class="gif-image-remove gif-image-overlay">
		<i class="bb-icon-close"></i>
	</div>
	<# } #>
</script>
