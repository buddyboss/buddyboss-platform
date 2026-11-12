<?php
/**
 * The template for users blogs
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/blogs.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bboss_get_template_part( 'members/single/parts/item-subnav' );
bboss_get_template_part( 'common/search-and-filters-bar' );

$is_send_ajax_request = bboss_is_send_ajax_request();

switch ( bboss_current_action() ) :

	// Home/My Blogs
	case 'my-sites':
		bboss_nouveau_member_hook( 'before', 'blogs_content' );
		?>

		<div class="blogs myblogs" data-bp-list="blogs">
			<?php
			if ( $is_send_ajax_request ) {
				echo '<div id="bp-ajax-loader">';
				bboss_nouveau_user_feedback( 'member-blogs-loading' );
				echo '</div>';
			} else {
				bboss_get_template_part( 'blogs/blogs-loop' );
			}
			?>
		</div><!-- .blogs.myblogs -->

		<?php
		bboss_nouveau_member_hook( 'after', 'blogs_content' );
		break;

	// Any other
	default:
		bboss_get_template_part( 'members/single/plugins' );
		break;
endswitch;
