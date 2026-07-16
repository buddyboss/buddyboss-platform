<?php
/**
 * ReadyLaunch - Member profile Blogs tab content.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Blogging
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

/**
 * Filter the member Blogs tab query args.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $bb_blog_query_args WP_Query args.
 */
$bb_blog_member_query = new WP_Query( apply_filters( 'bb_blog_member_posts_query_args', $bb_blog_query_args ) );
?>
<div class="bb-rl-screen-content bb-rl-member-blog">
	<?php
	/**
	 * Fires at the start of the member profile Blogs tab content, before the
	 * blog post grid — renders the toolbar (sub-tabs, sort, Create button).
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_blog_member_posts_before' );
	?>
	<?php if ( $bb_blog_member_query->have_posts() ) : ?>
		<div class="bb-rl-blog-grid bb-rl-blog-grid--profile">
			<?php
			// Profile cards carry the post status tag on the cover image.
			add_filter( 'bb_rl_blog_card_show_status', '__return_true' );
			while ( $bb_blog_member_query->have_posts() ) :
				$bb_blog_member_query->the_post();
				bp_get_template_part( 'blog/loop-post' );
			endwhile;
			remove_filter( 'bb_rl_blog_card_show_status', '__return_true' );
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
			// Same wrapper as core `the_posts_pagination()` so the shared
			// `.navigation.pagination` styles apply.
			printf(
				'<nav class="navigation pagination" aria-label="%1$s"><div class="nav-links">%2$s</div></nav>',
				esc_attr__( 'Posts pagination', 'buddyboss' ),
				$bb_blog_pagination_links // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core pagination HTML.
			);
		}
		wp_reset_postdata();
		?>
	<?php else : ?>
		<div class="bb-rl-blog-empty">
			<p>
				<i class="bb-icons-rl-info"></i>
				<?php
				if ( $bb_blog_is_owner ) {
					esc_html_e( "You haven't written any blog posts yet.", 'buddyboss' );
				} else {
					/* translators: %s: member display name. */
					printf( esc_html__( '%s has not written any blog posts yet.', 'buddyboss' ), esc_html( bp_get_displayed_user_fullname() ) );
				}
				?>
			</p>
		</div>
	<?php endif; ?>
</div>
