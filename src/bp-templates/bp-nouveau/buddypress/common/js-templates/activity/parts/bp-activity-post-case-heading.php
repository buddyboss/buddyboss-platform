<?php
/**
 * The template for displaying activity post case heading
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/activity/parts/bp-activity-post-case-heading.php.
 *
 * @since [BBVERSION]
 * @version [BBVERSION]
 */

?>
<script type="text/html" id="tmpl-activity-post-case-heading">
	<# if ( data.display_avatar ) {  #>
		<span class="activity-post-user-name-container">
            <h5><a class="activity-post-user-name" href="{{data.user_domain}}"><span class="user-name">{{data.user_display_name}}</span></a></h5>
        </span>
	<# } #>
</script>
