<?php
/**
 * The template for displaying activity post form title field.
 *
 * This template can be overridden by copying it to
 * yourtheme/buddypress/common/js-templates/activity/parts/bb-activity-post-form-title.php.
 *
 * @since   BuddyBoss 2.13.0
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-bb-activity-post-form-title">
	<# var required = data.required ? 'required' : ''; #>
	<input type="text"
	       class="whats-new-title"
	       name="whats-new-title"
	       id="whats-new-title"
	       maxlength="{{{data.maxlength}}}"
	       placeholder="{{{data.placeholder}}}"
	       {{required}} />
</script>
