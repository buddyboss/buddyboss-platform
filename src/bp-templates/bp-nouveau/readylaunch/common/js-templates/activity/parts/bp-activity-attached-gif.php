<?php
/**
 * ReadyLaunch - The template for displaying activity attached GIF.
 *
 * @since   BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-activity-attached-gif">
	<# if ( ! _.isUndefined( data.gif_data.images ) ) { #>
	<div class="gif-image-container">
		<img src="{{data.gif_data.images.original.url}}" alt="">
	</div>
	<div class="gif-image-remove gif-image-overlay">
		<i class="bb-icon-l bb-icon-times"></i>
	</div>
	<# } #>
</script>
