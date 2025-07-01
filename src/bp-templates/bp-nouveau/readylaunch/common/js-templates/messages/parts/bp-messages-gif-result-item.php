<?php
/**
 * Readylaunch - Messages gif result item template.
 *
 * @since   BuddyBoss 2.9.00
 * @version 1.0.0
 */
?>

<script type="text/html" id="tmpl-messages-gif-result-item">
	<a class="found-media-item" href="{{{data.images.original.url}}}" data-id="{{data.id}}">
		<img src="{{{data.images.fixed_width.url}}}" />
	</a>
</script>
