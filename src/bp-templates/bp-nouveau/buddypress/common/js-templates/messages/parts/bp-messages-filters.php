<?php
/**
 * BP Nouveau messages filters template
 *
 * This template can be overridden by copying it to yourtheme/buddypress/messages/parts/bp-messages-filters.php.
 *
 * @since   1.0.0
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