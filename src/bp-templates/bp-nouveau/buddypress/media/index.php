<?php
/**
 * BuddyBoss Media templates
 *
 * @since BuddyBoss 1.0.0
 * @version 1.0.0
 */
?>

	<?php bp_nouveau_before_media_directory_content(); ?>

	<?php bp_nouveau_template_notices(); ?>

	<div class="screen-content">

		<?php bp_nouveau_media_hook( 'before_directory', 'list' ); ?>

		<?php
		/**
		 * Fires before the display of the members list tabs.
		 *
		 * @since BuddyPress 1.8.0
		 */
		do_action( 'bp_before_directory_media_tabs' );
		?>

		<?php if ( ! bp_nouveau_is_object_nav_in_sidebar() ) : ?>

			<?php bp_get_template_part( 'common/nav/directory-nav' ); ?>

		<?php endif; ?>

		<?php bp_get_template_part( 'common/search-and-filters-bar' ); ?>

		<?php bp_get_template_part( 'media/theatre' ); ?>

		<div id="media-stream" class="media" data-bp-list="media">
			<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'directory-media-loading' ); ?></div>
		</div><!-- .media -->

		<?php bp_nouveau_after_media_directory_content(); ?>

	</div><!-- // .screen-content -->

