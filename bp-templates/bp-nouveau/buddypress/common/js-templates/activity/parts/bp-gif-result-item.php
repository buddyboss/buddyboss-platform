<?php
/**
 * The template for displaying gif result item
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/activity/parts/bp-gif-result-item.php.
 *
 * @since   1.0.0
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-gif-result-item">
	<a class="found-media-item" href="{{{data.images.original.url}}}" data-id="{{data.id}}">
		<img src="{{{data.images.fixed_width.url}}}" />
	</a>
</script>
