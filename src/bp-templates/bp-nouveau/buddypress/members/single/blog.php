<?php
/**
 * The template for the member profile "Blogs" tab (Member Blogging feature).
 *
 * Loaded for the member profile `blog` (singular) nav item — the Member
 * Blogging feature (native WP posts authored by the member), distinct from the
 * multisite Sites component template `members/single/blogs.php`. Renders the
 * toolbar (via the `bb_blog_member_posts_before` action) and the blog card
 * list, with each card rendered through the `blog/loop-post.php` part.
 *
 * This template can be overridden by copying it to
 * yourtheme/buddypress/members/single/blog.php.
 *
 * @since   BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$bb_blog_is_owner = bp_is_my_profile() || current_user_can( 'edit_others_posts' );

$bb_blog_sort = isset( $_GET['bb-sort'] ) ? sanitize_key( wp_unslash( $_GET['bb-sort'] ) ) : 'newest'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only sort.
if ( ! in_array( $bb_blog_sort, array( 'newest', 'oldest' ), true ) ) {
	$bb_blog_sort = 'newest';
}

$bb_blog_query_args = array(
	'post_type'      => 'post',
	'author'         => bp_displayed_user_id(),
	'post_status'    => $bb_blog_is_owner ? array( 'publish', 'draft', 'pending', 'future' ) : array( 'publish' ),
	'posts_per_page' => 12,
	'paged'          => max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) ),
	'order'          => 'oldest' === $bb_blog_sort ? 'ASC' : 'DESC',
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
		<div class="bb-member-blog__list">
			<?php
			set_query_var( 'bb_blog_card_context', 'member-posts' );
			while ( $bb_blog_member_query->have_posts() ) :
				$bb_blog_member_query->the_post();
				bp_get_template_part( 'blog/loop-post' );
			endwhile;
			set_query_var( 'bb_blog_card_context', '' );
			wp_reset_postdata();
			?>
		</div>
		<?php
		$bb_blog_pagination_links = paginate_links(
			array(
				'total'     => (int) $bb_blog_member_query->max_num_pages,
				'current'   => max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) ),
				'base'      => get_pagenum_link( 1 ) . '%_%',
				'format'    => '?paged=%#%',
				'mid_size'  => 2,
				'prev_text' => esc_html__( 'Previous', 'buddyboss' ),
				'next_text' => esc_html__( 'Next', 'buddyboss' ),
			)
		);

		if ( $bb_blog_pagination_links ) {
			printf(
				'<nav class="navigation pagination" aria-label="%1$s"><div class="nav-links">%2$s</div></nav>',
				esc_attr__( 'Posts pagination', 'buddyboss' ),
				$bb_blog_pagination_links // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core pagination HTML.
			);
		}
		?>
	<?php else : ?>
		<p class="bb-member-blog__empty">
			<i class="bb-icon-info"></i>
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
