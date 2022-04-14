<?php
/**
 * BP Nouveau messages gif result item template
 *
 * This template can be overridden by copying it to yourtheme/buddypress/messages/parts/bp-messages-gif-result-item.php.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
?>

<script type="text/html" id="tmpl-messages-gif-result-item">
	<a class="found-media-item" href="{{{data.images.original.url}}}" data-id="{{data.id}}">
		<img src="{{{data.images.fixed_width.url}}}" />
	</a>
</script>
