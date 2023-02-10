<?php
/**
 * BP Nouveau member no subscription template
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/members/settings/bb-member-no-subscription.php.
 *
 * @since   BuddyBoss 2.2.6
 * @version 1.0.0
 */
?>

<script type="text/html" id="tmpl-bb-member-no-subscription">
	<p>
		<#
		var no_results = BP_Nouveau.subscriptions.no_result;
		no_results = no_results.replace( "%s", data.pluralLabel );
		print( no_results );
		#>
	</p>
</script>
