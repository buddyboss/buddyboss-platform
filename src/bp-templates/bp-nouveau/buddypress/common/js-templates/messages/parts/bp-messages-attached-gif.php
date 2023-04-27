<?php
/**
 * BP Nouveau messages document template
 *
 * This template can be overridden by copying it to yourtheme/buddypress/messages/parts/bp-messages-attached-gif.php.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
?>
<script type="text/html" id="tmpl-messages-attached-gif">
	<# if ( ! _.isUndefined( data.gif_data.images ) ) { #>
	<div class="gif-image-container">
		<img src="{{data.gif_data.images.original.url}}" alt="">
	</div>
	<div class="gif-image-remove gif-image-overlay">
		<i class="bb-icon-l bb-icon-times"></i>
	</div>
	<# } #>
</script>
