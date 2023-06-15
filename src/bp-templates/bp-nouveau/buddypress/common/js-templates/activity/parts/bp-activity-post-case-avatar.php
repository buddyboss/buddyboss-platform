<?php
/**
 * The template for displaying activity header
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/activity/parts/bp-activity-post-case-avatar.php.
 *
 * @since   BuddyBoss 1.8.6
 * @version 1.8.6
 */

?>
<script type="text/html" id="tmpl-activity-post-case-avatar">
	<# if ( data.display_avatar ) {  #>
	<div class="activity-post-avatar-container">
		<a class="activity-post-avatar" href="{{data.user_domain}}"><img src="{{{data.avatar_url}}}" class="avatar user-{{data.user_id}}-avatar avatar-{{data.avatar_width}} photo" width="{{data.avatar_width}}" height="{{data.avatar_width}}" alt="{{data.avatar_alt}}" /></a>
	</div>
	<# } #>
</script>
