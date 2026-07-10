<?php
/**
 * ReadyLaunch - Member profile Blogs tab content.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Blogging
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
	 * blog post grid — used to render the "Add New" button.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_blog_member_posts_before' );
	?>
	<?php if ( $bb_blog_member_query->have_posts() ) : ?>
		<div class="bb-rl-blog-grid bb-rl-blog-grid--profile">
			<?php
			while ( $bb_blog_member_query->have_posts() ) :
				$bb_blog_member_query->the_post();
				bp_get_template_part( 'blog/loop-post' );
			endwhile;
			?>
		</div>
		<?php
		echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core pagination HTML.
			array(
				'total'   => (int) $bb_blog_member_query->max_num_pages,
				'current' => max( 1, (int) get_query_var( 'paged' ) ),
				'base'    => get_pagenum_link( 1 ) . '%_%',
				'format'  => '?paged=%#%',
			)
		);
		wp_reset_postdata();
		?>
	<?php else : ?>
		<div class="bb-rl-blog-empty">
			<p>
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
