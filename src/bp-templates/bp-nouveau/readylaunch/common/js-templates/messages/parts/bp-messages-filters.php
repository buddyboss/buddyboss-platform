<?php
/**
 * Readylaunch - Messages filters template.
 *
 * @since   BuddyBoss 2.9.00
 * @version 1.0.0
 */
?>

<script type="text/html" id="tmpl-bp-messages-filters">
	<li class="user-messages-search" role="search" data-bp-search="{{data.box}}">
		<div class="bp-search messages-search">
			<?php bp_nouveau_message_search_form(); ?>
		</div>
	</li>
	<li class="user-messages-bulk-actions"></li>
</script>