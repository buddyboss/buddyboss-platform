<?php
/**
 * ReadyLaunch - The template for displaying activity header.
 *
 * @since   BuddyBoss 2.9.00
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-activity-post-case-avatar">
	<# if ( data.display_avatar ) {  #>
	<div class="bb-rl-activity-post-avatar-container">
		<a class="bb-rl-activity-post-avatar" href="{{data.user_domain}}"><img src="{{{data.avatar_url}}}" class="avatar user-{{data.user_id}}-avatar avatar-{{data.avatar_width}} photo" width="{{data.avatar_width}}" height="{{data.avatar_width}}" alt="{{data.avatar_alt}}" /></a>
	</div>
	<# } #>
</script>
