<?php
/**
 * ReadyLaunch - The template for displaying activity target item.
 *
 * @since   BuddyBoss 2.9.00
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-activity-target-item">
	<label for="bb-rl-item-opt-{{data.id}}">
		<span class="bb-rl-radio-style bb-rl-privacy-radio <# if ( data.selected ) { #>selected<# } #>">
			<input type="radio" id="bb-rl-item-opt-{{data.id}}" class="bb-rl-activity-object__radio" name="group-privacy" data-title="{{data.name}}" data-id="{{data.id}}" <# if ( data.allow_schedule ) { #> data-allow-schedule-post="{{data.allow_schedule}}" <# } #> <# if ( data.allow_polls ) { #> data-allow-polls="{{data.allow_polls}}" <# } #> value="opt-value-{{data.id}}" <# if ( data.selected ) { #> checked <# } #>><span></span>
		</span>
		<# if ( data.selected ) { #>
		<input type="hidden" value="{{data.id}}">
		<# } #>

		<# if ( data.avatar_url ) { #>
		<img src="{{data.avatar_url}}" class="avatar {{data.object_type}}-{{data.id}}-avatar photo" alt="" />
		<# } #>

		<span class="bp-item-name">{{data.name}}</span>
	</label>
</script>
