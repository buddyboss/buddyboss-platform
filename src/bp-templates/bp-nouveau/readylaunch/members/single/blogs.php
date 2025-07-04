<?php
/**
 * ReadyLaunch - Member Blogs template.
 *
 * This template handles displaying member blogs with loading placeholders.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_get_template_part( 'members/single/parts/item-subnav' );
bp_get_template_part( 'common/search-and-filters-bar' );

$is_send_ajax_request = bb_is_send_ajax_request();

switch ( bp_current_action() ) :

	// Home/My Blogs
	case 'my-sites':
		bp_nouveau_member_hook( 'before', 'blogs_content' );
		?>

		<div class="blogs myblogs" data-bp-list="blogs">
			<?php
			if ( $is_send_ajax_request ) {
				echo '<div id="bp-ajax-loader">';
				bp_nouveau_user_feedback( 'member-blogs-loading' );
				echo '</div>';
			} else {
				bp_get_template_part( 'blogs/blogs-loop' );
			}
			?>
		</div><!-- .blogs.myblogs -->

		<?php
		bp_nouveau_member_hook( 'after', 'blogs_content' );
		break;

	// Any other
	default:
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;
