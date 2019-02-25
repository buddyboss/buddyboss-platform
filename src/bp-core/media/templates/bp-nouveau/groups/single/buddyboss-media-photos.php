<?php
/**
 * BuddyPress Media - group album photos page template
 *
 * @package WordPress
 * @subpackage BuddyBoss Media
 */
?>

<?php bp_nouveau_groups_activity_post_form(); ?>

<?php bp_nouveau_group_hook( 'before', 'activity_content' ); ?>

<div id="activity-stream" class="activity single-group" data-bp-list="activity">

	<li id="bp-activity-ajax-loader"><?php bp_nouveau_user_feedback( 'group-activity-loading' ); ?></li>

</div><!-- .activity -->

<?php
bp_nouveau_group_hook( 'after', 'activity_content' );