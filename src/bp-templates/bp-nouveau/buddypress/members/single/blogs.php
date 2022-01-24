<?php
/**
 * The template for users blogs
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/blogs.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */
?>

<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>

<?php bp_get_template_part( 'common/search-and-filters-bar' ); ?>

<?php
switch ( bp_current_action() ) :

	// Home/My Blogs
	case 'my-sites':
		bp_nouveau_member_hook( 'before', 'blogs_content' );
		?>

		<div class="blogs myblogs" data-bp-list="blogs">

			<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'member-blogs-loading' ); ?></div>

		</div><!-- .blogs.myblogs -->

		<?php
		bp_nouveau_member_hook( 'after', 'blogs_content' );
		break;

	// Any other
	default:
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;
