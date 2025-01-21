<?php
/**
 * ReadyLaunch - The template for displaying activity post case heading.
 *
 * @since   BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-activity-post-case-heading">
	<# if ( data.display_avatar ) {  #>
		<span class="activity-post-user-name-container">
			<h5><a class="activity-post-user-name" href="{{data.user_domain}}"><span class="user-name">{{data.user_display_name}}</span></a></h5>
		</span>
	<# } #>
</script>
