<?php
/**
 * The template for users activity
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/activity.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

?>


<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>

<?php bp_nouveau_activity_member_post_form(); ?>

<?php bp_get_template_part( 'common/search-and-filters-bar' ); ?>

<?php bp_nouveau_member_hook( 'before', 'activity_content' ); ?>

<div id="activity-stream" class="activity single-user" data-bp-list="activity">

	<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'member-activity-loading' ); ?></div>

	<ul  class="<?php bp_nouveau_loop_classes(); ?>" >

	</ul>

</div><!-- .activity -->

<?php
bp_nouveau_member_hook( 'after', 'activity_content' );

bp_get_template_part( 'common/js-templates/activity/comments' );
