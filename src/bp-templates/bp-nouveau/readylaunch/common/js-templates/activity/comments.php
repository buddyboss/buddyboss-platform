<?php
/**
 * ReadyLaunch - Activity Comments JS Templates.
 *
 * @since   BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

/**
 * Fires before the activity comments template.
 *
 * @since BuddyBoss [BBVERSION]
 */
do_action( 'bb_nouveau_activity_comments_before_js_template' );
?>
	<script type="text/html" id="tmpl-activity-comments-loading-message">
		<div id="bb-rl-activity-comments-ajax-loader">
			<?php bp_nouveau_user_feedback( 'activity-comments-loading' ); ?>
		</div>
	</script>

<?php
/**
 * Fires after the activity comments template.
 *
 * @since BuddyBoss [BBVERSION]
 */
do_action( 'bb_nouveau_activity_comments_after_js_template' );
