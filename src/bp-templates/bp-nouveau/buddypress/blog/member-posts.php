<?php
/**
 * BuddyBoss - Member profile Blogs tab content (legacy template pack).
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

/** This filter is documented in bp-templates/bp-nouveau/readylaunch/blog/member-posts.php */
$bb_blog_member_query = new WP_Query( apply_filters( 'bb_blog_member_posts_query_args', $bb_blog_query_args ) );
?>
<div class="bb-member-blog">
	<?php
	/** This action is documented in bp-templates/bp-nouveau/readylaunch/blog/member-posts.php */
	do_action( 'bb_blog_member_posts_before' );
	?>
	<?php if ( $bb_blog_member_query->have_posts() ) : ?>
		<ul class="bb-member-blog__list">
			<?php
			while ( $bb_blog_member_query->have_posts() ) :
				$bb_blog_member_query->the_post();
				?>
				<li class="bb-member-blog__item">
					<?php if ( has_post_thumbnail() ) : ?>
						<a class="bb-member-blog__thumb" href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'thumbnail' ); ?></a>
					<?php endif; ?>
					<div class="bb-member-blog__body">
						<h3 class="bb-member-blog__title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							<?php
							$bb_blog_status     = get_post_status();
							$bb_blog_status_obj = get_post_status_object( $bb_blog_status );
							if ( 'publish' !== $bb_blog_status ) :
								?>
								<span class="bb-member-blog__status"><?php echo esc_html( $bb_blog_status_obj ? $bb_blog_status_obj->label : ucfirst( $bb_blog_status ) ); ?></span>
							<?php endif; ?>
						</h3>
						<span class="bb-member-blog__date"><?php echo esc_html( get_the_date() ); ?></span>
					</div>
				</li>
				<?php
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
		?>
	<?php else : ?>
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
