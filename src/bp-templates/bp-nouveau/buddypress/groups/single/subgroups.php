<?php
/**
 * BuddyBoss - Groups Subgroups
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/subgroups.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

?>

<?php bp_nouveau_before_groups_directory_content(); ?>

<?php if ( ! bp_nouveau_is_object_nav_in_sidebar() ) : ?>

	<?php bp_get_template_part( 'common/nav/directory-nav' ); ?>

<?php endif; ?>

<div class="screen-content">

	<?php bp_get_template_part( 'common/search-and-filters-bar' ); ?>

	<div id="groups-dir-list" class="groups dir-list" data-bp-list="group_subgroups">
		<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'directory-groups-loading' ); ?></div>
	</div><!-- #groups-dir-list -->

	<?php bp_nouveau_after_groups_directory_content(); ?>

</div><!-- // .screen-content -->
