<?php
/**
 * ReadyLaunch - The template for displaying activity post case heading.
 *
 * @since   BuddyBoss 2.9.00
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-activity-post-case-heading">
	<# if ( data.display_avatar ) {  #>
		<span class="bb-rl-activity-post-user-name-container">
			<h5><a class="bb-rl-activity-post-user-name" href="{{data.user_domain}}" aria-label="{{data.user_display_name}}"><span class="user-name">{{data.user_display_name}}</span></a></h5>
		</span>
	<# } #>
</script>
