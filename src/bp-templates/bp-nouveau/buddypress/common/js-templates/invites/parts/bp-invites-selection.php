<?php
/**
 * Invites selection Templates
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/invites/parts/bp-invites-selection.php.
 *
 * @since   1.0.0
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-bp-invites-selection">
	<a href="#uninvite-user-{{data.id}}" class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="{{data.uninviteTooltip}}" aria-label="{{data.uninviteTooltip}}">
		<img src="{{data.avatar}}" class="avatar" alt=""/>
	</a>
</script>
