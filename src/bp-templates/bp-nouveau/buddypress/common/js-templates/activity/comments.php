<?php
/**
 * Activity Comments JS Templates
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/activity/comments.php.
 *
 * @since   1.0.0
 * @version 1.0.0
 */

/**
 * Fires before the activity comments template.
 *
 * @since BuddyBoss 2.1.4
 */
do_action( 'bb_nouveau_activity_comments_before_js_template' );
?>
<script type="text/html" id="tmpl-activity-comments-loading-message">
	<div id="bp-activity-comments-ajax-loader"><?php bp_nouveau_user_feedback( 'activity-comments-loading' ); ?></div>
</script>

<?php
/**
 * Fires after the activity comments template.
 *
 * @since BuddyBoss 2.1.4
 */
do_action( 'bb_nouveau_activity_comments_after_js_template' );
