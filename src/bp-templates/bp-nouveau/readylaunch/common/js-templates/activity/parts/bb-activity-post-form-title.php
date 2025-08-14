<?php
/**
 * The template for displaying activity post form title field.
 *
 * @since   BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-bb-activity-post-form-title">
	<# var required = data.required ? 'required' : ''; #>
	<input type="text"
	       class="whats-new-title"
	       name="whats-new-title"
	       maxlength="{{data.maxlength}}"
	       placeholder="{{data.placeholder}}"
	       {{required}} />
</script>
