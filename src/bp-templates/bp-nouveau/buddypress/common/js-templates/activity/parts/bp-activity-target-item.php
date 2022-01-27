<?php
/**
 * The template for displaying activity target item
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/activity/parts/bp-activity-target-item.php.
 *
 * @since   1.0.0
 * @version 1.8.6
 */

?>
<script type="text/html" id="tmpl-activity-target-item">
	<label for="bp-item-opt-{{data.id}}">
		<# if ( data.selected ) { #>
		<input type="hidden" value="{{data.id}}">
		<# } #>

		<# if ( data.avatar_url ) { #>
		<img src="{{data.avatar_url}}" class="avatar {{data.object_type}}-{{data.id}}-avatar photo" alt="" />
		<# } #>

		<span class="bp-item-name">{{data.name}}</span>

		<span class="bb-radio-style privacy-radio <# if ( data.selected ) { #>selected<# } #>">
			<input type="radio" id="bp-item-opt-{{data.id}}" class="bp-activity-object__radio" name="group-privacy" data-title="{{data.name}}" data-id="{{data.id}}" value="opt-value-{{data.id}}" <# if ( data.selected ) { #> checked <# } #>><span></span>
		</span>
	</label>
</script>
