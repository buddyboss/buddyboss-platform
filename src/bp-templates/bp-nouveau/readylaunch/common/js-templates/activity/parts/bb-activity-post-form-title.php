<?php
/**
 * The template for displaying activity post form title field.
 *
 * @since   BuddyBoss 2.13.0
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-bb-activity-post-form-title">
	<# var required = data.required ? 'required' : ''; #>
	<input type="text"
	       class="bb-rl-whats-new-title"
	       name="bb-rl-whats-new-title"
	       id="bb-rl-whats-new-title"
	       maxlength="{{{data.maxlength}}}"
	       placeholder="{{{data.placeholder}}}"
	       {{required}} />
</script>
