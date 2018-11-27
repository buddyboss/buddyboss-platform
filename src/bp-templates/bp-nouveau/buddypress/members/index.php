<?php
/**
 * BuddyPress Members Directory
 *
 * @version 3.0.0
 */

?>

<?php
	/**
	 * Fires at the begining of the templates BP injected content.
	 *
	 * @since BuddyPress 2.3.0
	 */
	do_action( 'bp_before_directory_members_page' );
?>

<?php
	/**
	 * Fires before the display of the members.
	 *
	 * @since BuddyPress 1.1.0
	 */
	do_action( 'bp_before_directory_members' );
?>

<?php
	/**
	 * Fires before the display of the members list tabs.
	 *
	 * @since BuddyPress 1.8.0
	 */
	do_action( 'bp_before_directory_members_tabs' );
?>

	<?php if ( ! bp_nouveau_is_object_nav_in_sidebar() ) : ?>

		<?php bp_get_template_part( 'common/nav/directory-nav' ); ?>

	<?php endif; ?>

	<?php bp_get_template_part( 'common/search-and-filters-bar' ); ?>

	<div class="screen-content members-directory-content">
		<?php
			/**
			 * Fires before the display of the members content.
			 *
			 * @since BuddyPress 1.1.0
			 */
			do_action( 'bp_before_directory_members_content' );
		?>

		<div id="members-dir-list" class="members dir-list" data-bp-list="members">
			<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'directory-members-loading' ); ?></div>
		</div><!-- #members-dir-list -->

		<?php bp_nouveau_after_members_directory_content(); ?>
	</div><!-- // .screen-content -->