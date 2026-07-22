<?php
/**
 * The template for the member profile "Blogs" tab (Member Blogging feature).
 *
 * Loaded for the member profile `blog` (singular) nav item — the Member
 * Blogging feature (native WP posts authored by the member), distinct from the
 * multisite Sites component template `members/single/blogs.php`. Queries the
 * member's posts and renders each one through the `blog/loop-post.php` part.
 *
 * This template can be overridden by copying it to
 * yourtheme/buddypress/members/single/blog.php.
 *
 * @since   BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$bb_blog_is_owner = bp_is_my_profile() || current_user_can( 'edit_others_posts' );

$bb_blog_query_args = array(
	'post_type'      => 'post',
	'author'         => bp_displayed_user_id(),
	'post_status'    => $bb_blog_is_owner ? array( 'publish', 'draft', 'pending', 'future' ) : array( 'publish' ),
	'posts_per_page' => 12,
	'paged'          => max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) ),
);

/** This filter is documented in bp-templates/bp-nouveau/readylaunch/members/single/blog.php */
$bb_blog_member_query = new WP_Query( apply_filters( 'bb_blog_member_posts_query_args', $bb_blog_query_args ) );
?>
<div class="bb-member-blog">
	<?php
		/** This action is documented in bp-templates/bp-nouveau/readylaunch/members/single/blog.php */
		do_action( 'bb_blog_member_posts_before' );
	?>
	<?php if ( $bb_blog_member_query->have_posts() ) : ?>
		<ul class="bb-member-blog__list">
			<?php
			while ( $bb_blog_member_query->have_posts() ) :
				$bb_blog_member_query->the_post();
				bp_get_template_part( 'blog/loop-post' );
			endwhile;
			wp_reset_postdata();
			?>
		</ul>
		<?php
		echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core pagination HTML.
			array(
				'total'   => (int) $bb_blog_member_query->max_num_pages,
				'current' => max( 1, (int) get_query_var( 'paged' ) ),
				'base'    => get_pagenum_link( 1 ) . '%_%',
				'format'  => '?paged=%#%',
			)
		);

	else :
		?>
		<p class="bb-member-blog__empty">
			<?php
			if ( $bb_blog_is_owner ) {
				esc_html_e( "You haven't written any blog posts yet.", 'buddyboss' );
			} else {
				/* translators: %s: member display name. */
				printf( esc_html__( '%s has not written any blog posts yet.', 'buddyboss' ), esc_html( bp_get_displayed_user_fullname() ) );
			}
			?>
		</p>
	<?php endif; ?>
</div>

