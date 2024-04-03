<?php
/**
 * The template for BuddyBoss - Blogs Directory
 *
 * This template can be overridden by copying it to yourtheme/buddypress/blogs/index.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

$is_send_ajax_request = bb_is_send_ajax_request();

bp_nouveau_before_blogs_directory_content();

if ( ! bp_nouveau_is_object_nav_in_sidebar() ) :
	bp_get_template_part( 'common/nav/directory-nav' );
endif;
?>

<div class="screen-content">
	<?php bp_get_template_part( 'common/search-and-filters-bar' ); ?>
	<div id="blogs-dir-list" class="blogs dir-list" data-bp-list="blogs" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
		<?php
		if ( $is_send_ajax_request ) {
			echo '<div id="bp-ajax-loader">';
			bp_nouveau_user_feedback( 'directory-blogs-loading' );
			echo '</div>';
		} else {
			bp_get_template_part( 'blogs/blogs-loop' );
		}
		?>
	</div><!-- #blogs-dir-list -->
	<?php bp_nouveau_after_blogs_directory_content(); ?>
</div><!-- // .screen-content -->
