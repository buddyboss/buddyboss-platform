<?php
/**
 * The template for BuddyBoss - Blogs Directory
 *
 * This template can be overridden by copying it to yourtheme/buddypress/blogs/index.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$is_send_ajax_request = bboss_is_send_ajax_request();

bboss_nouveau_before_blogs_directory_content();

if ( ! bboss_nouveau_is_object_nav_in_sidebar() ) :
	bboss_get_template_part( 'common/nav/directory-nav' );
endif;
?>

<div class="screen-content">
	<?php bboss_get_template_part( 'common/search-and-filters-bar' ); ?>
	<div id="blogs-dir-list" class="blogs dir-list" data-bp-list="blogs" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
		<?php
		if ( $is_send_ajax_request ) {
			echo '<div id="bp-ajax-loader">';
			bboss_nouveau_user_feedback( 'directory-blogs-loading' );
			echo '</div>';
		} else {
			bboss_get_template_part( 'blogs/blogs-loop' );
		}
		?>
	</div><!-- #blogs-dir-list -->
	<?php bboss_nouveau_after_blogs_directory_content(); ?>
</div><!-- // .screen-content -->
