<?php
/**
 * The template for users activity
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/activity.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

bp_get_template_part( 'members/single/parts/item-subnav' );
bp_nouveau_activity_member_post_form();
bp_get_template_part( 'common/search-and-filters-bar' );
bp_nouveau_member_hook( 'before', 'activity_content' );
?>


<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>

<?php bp_nouveau_activity_member_post_form(); ?>

<?php bp_get_template_part( 'common/search-and-filters-bar' ); ?>

<?php bp_nouveau_member_hook( 'before', 'activity_content' ); ?>

<div id="activity-stream" class="activity single-user" data-bp-list="activity">

	<div id="bp-ajax-loader">
		<div class="bb-activity-placeholder">
			<div class="bb-activity-placeholder_head">
				<div class="bb-activity-placeholder_avatar bb-bg-animation bb-loading-bg"></div>
				<div class="bb-activity-placeholder_details">
					<div class="bb-activity-placeholder_title bb-bg-animation bb-loading-bg"></div>
					<div class="bb-activity-placeholder_description bb-bg-animation bb-loading-bg"></div>
				</div>
			</div>
			<div class="bb-activity-placeholder_content">
				<div class="bb-activity-placeholder_title bb-bg-animation bb-loading-bg"></div>
				<div class="bb-activity-placeholder_title bb-bg-animation bb-loading-bg"></div>
			</div>
			<div class="bb-activity-placeholder_actions">
				<div class="bb-activity-placeholder_description bb-bg-animation bb-loading-bg"></div>
				<div class="bb-activity-placeholder_description bb-bg-animation bb-loading-bg"></div>
				<div class="bb-activity-placeholder_description bb-bg-animation bb-loading-bg"></div>
			</div>
		</div>
		<div class="bb-activity-placeholder">
			<div class="bb-activity-placeholder_head">
				<div class="bb-activity-placeholder_avatar bb-bg-animation bb-loading-bg"></div>
				<div class="bb-activity-placeholder_details">
					<div class="bb-activity-placeholder_title bb-bg-animation bb-loading-bg"></div>
					<div class="bb-activity-placeholder_description bb-bg-animation bb-loading-bg"></div>
				</div>
			</div>
			<div class="bb-activity-placeholder_content">
				<div class="bb-activity-placeholder_title bb-bg-animation bb-loading-bg"></div>
				<div class="bb-activity-placeholder_title bb-bg-animation bb-loading-bg"></div>
			</div>
			<div class="bb-activity-placeholder_actions">
				<div class="bb-activity-placeholder_description bb-bg-animation bb-loading-bg"></div>
				<div class="bb-activity-placeholder_description bb-bg-animation bb-loading-bg"></div>
				<div class="bb-activity-placeholder_description bb-bg-animation bb-loading-bg"></div>
			</div>
		</div>
	</div>

	<ul  class="<?php bp_nouveau_loop_classes(); ?>" >

	</ul>

</div><!-- .activity -->

<?php
bp_nouveau_member_hook( 'after', 'activity_content' );
bp_get_template_part( 'common/js-templates/activity/comments' );
