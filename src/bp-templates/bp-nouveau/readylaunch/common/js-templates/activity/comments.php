<?php
/**
 * ReadyLaunch - Activity Comments JS Templates.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since   BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fires before the activity comments template.
 *
 * @since BuddyBoss 2.9.00
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
 * @since BuddyBoss 2.9.00
 */
do_action( 'bb_nouveau_activity_comments_after_js_template' );
