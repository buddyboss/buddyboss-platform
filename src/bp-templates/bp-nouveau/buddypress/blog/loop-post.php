<?php
/**
 * BuddyBoss - Member profile blog card (one card per loop iteration).
 *
 * Shared by the My Blogs list (`members/single/blog.php`) and the Bookmarked
 * list. Context is passed via the `bb_blog_card_context` query var:
 * `member-posts` cards carry the status tag and the more-options menu,
 * `bookmarks` cards do not.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Blogging
 */

defined( 'ABSPATH' ) || exit;

$bb_blog_card_context = get_query_var( 'bb_blog_card_context' );
$bb_blog_card_context = in_array( $bb_blog_card_context, array( 'member-posts', 'bookmarks' ), true ) ? $bb_blog_card_context : 'member-posts';
$bb_blog_is_owner     = bp_is_my_profile() || current_user_can( 'edit_others_posts' );

$bb_blog_post_id     = get_the_ID();
$bb_blog_status      = get_post_status();
$bb_blog_categories  = get_the_category();
$bb_blog_author_id   = (int) get_the_author_meta( 'ID' );
$bb_blog_author_url  = function_exists( 'bp_core_get_user_domain' ) ? bp_core_get_user_domain( $bb_blog_author_id ) : get_author_posts_url( $bb_blog_author_id );
$bb_blog_author_name = function_exists( 'bp_core_get_user_displayname' ) ? bp_core_get_user_displayname( $bb_blog_author_id ) : get_the_author();

$bb_blog_bookmarking = function_exists( 'bb_blog_pro_is_bookmarking_enabled' ) && bb_blog_pro_is_bookmarking_enabled() && is_user_logged_in();
$bb_blog_bookmarked  = $bb_blog_bookmarking && function_exists( 'bb_blog_pro_is_bookmarked' ) && bb_blog_pro_is_bookmarked( $bb_blog_post_id );
$bb_blog_show_status = 'member-posts' === $bb_blog_card_context && $bb_blog_is_owner;
$bb_blog_show_menu   = 'member-posts' === $bb_blog_card_context;

$bb_blog_status_labels = array(
	'publish' => __( 'Published', 'buddyboss' ),
	'pending' => __( 'In Review', 'buddyboss' ),
	'draft'   => __( 'Draft', 'buddyboss' ),
	'future'  => __( 'Scheduled', 'buddyboss' ),
);

if ( '' === trim( (string) $bb_blog_author_name ) ) {
	$bb_blog_author_name = __( 'Unknown Member', 'buddyboss' );
}
?>
<article class="bb-member-blog__item" id="post-<?php the_ID(); ?>">
	<div class="bb-member-blog__media">
		<a class="bb-member-blog__thumb" href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
			<?php
			if ( has_post_thumbnail() ) :
				the_post_thumbnail( 'medium_large' );
			else :
				?>
				<span class="bb-member-blog__thumb-placeholder"><i class="bb-icon-l bb-icon-image" aria-hidden="true"></i></span>
			<?php endif; ?>
		</a>
		<?php if ( $bb_blog_show_status && isset( $bb_blog_status_labels[ $bb_blog_status ] ) ) : ?>
			<span class="bb-member-blog__status bb-member-blog__status--<?php echo esc_attr( $bb_blog_status ); ?>"><?php echo esc_html( $bb_blog_status_labels[ $bb_blog_status ] ); ?></span>
		<?php endif; ?>
		<?php if ( $bb_blog_bookmarking && 'publish' === $bb_blog_status ) : ?>
			<button
				type="button"
				class="bb-blog-bookmark bb-member-blog__bookmark<?php echo $bb_blog_bookmarked ? ' is-bookmarked' : ''; ?>"
				data-post-id="<?php echo esc_attr( $bb_blog_post_id ); ?>"
				aria-pressed="<?php echo $bb_blog_bookmarked ? 'true' : 'false'; ?>"
				aria-label="<?php echo $bb_blog_bookmarked ? esc_attr__( 'Remove bookmark', 'buddyboss' ) : esc_attr__( 'Bookmark this post', 'buddyboss' ); ?>"
			>
				<i class="bb-icon-l bb-icon-bookmark" aria-hidden="true"></i>
			</button>
		<?php endif; ?>
	</div>
	<div class="bb-member-blog__body">
		<div class="bb-member-blog__main">
			<div class="bb-member-blog__heading">
				<h3 class="bb-member-blog__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
				<?php if ( $bb_blog_show_menu ) : ?>
					<div class="bb-member-blog__menu bb_more_options">
						<a href="#" class="bb_more_options_action" aria-label="<?php esc_attr_e( 'More options', 'buddyboss' ); ?>">
							<i class="bb-icon-f bb-icon-ellipsis-h" aria-hidden="true"></i>
						</a>
						<ul class="bb_more_options_list">
							<?php
							/** This filter is documented in bp-templates/bp-nouveau/readylaunch/blog/loop-post.php */
							if ( apply_filters( 'bb_blog_card_show_view_post_menu_item', true, get_the_ID() ) ) :
								?>
								<li><a href="<?php the_permalink(); ?>"><i class="bb-icons-rl bb-icons-rl-eye" aria-hidden="true"></i><?php esc_html_e( 'View Post', 'buddyboss' ); ?></a></li>
								<?php
							endif;
							/** This action is documented in bp-templates/bp-nouveau/readylaunch/blog/loop-post.php */
							do_action( 'bb_blog_card_menu_items', get_the_ID() );
							?>
						</ul>
					</div>
				<?php endif; ?>
			</div>
			<div class="bb-member-blog__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></div>
		</div>
		<div class="bb-member-blog__footer">
			<div class="bb-member-blog__author">
				<a class="bb-member-blog__avatar" href="<?php echo esc_url( $bb_blog_author_url ); ?>">
					<?php
					if ( function_exists( 'bp_core_fetch_avatar' ) ) {
						echo bp_core_fetch_avatar( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- avatar HTML built by BuddyPress.
							array(
								'item_id' => $bb_blog_author_id,
								'type'    => 'thumb',
								'html'    => true,
							)
						);
					} else {
						echo get_avatar( $bb_blog_author_id, 76 );
					}
					?>
				</a>
				<div class="bb-member-blog__author-info">
					<a class="bb-member-blog__author-name" href="<?php echo esc_url( $bb_blog_author_url ); ?>"><?php echo esc_html( $bb_blog_author_name ); ?></a>
					<div class="bb-member-blog__meta">
						<span class="bb-member-blog__date"><?php echo esc_html( get_the_date() ); ?></span>
						<?php if ( ! empty( $bb_blog_categories ) && ! is_wp_error( $bb_blog_categories ) ) : ?>
							<span class="bb-member-blog__meta-sep" aria-hidden="true"></span>
							<a class="bb-member-blog__category" href="<?php echo esc_url( get_category_link( $bb_blog_categories[0] ) ); ?>"><?php echo esc_html( $bb_blog_categories[0]->name ); ?></a>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<a class="bb-member-blog__comments" href="<?php echo esc_url( get_comments_link() ); ?>">
				<i class="bb-icon-l bb-icon-comment-square" aria-hidden="true"></i>
				<span><?php echo esc_html( number_format_i18n( (int) get_comments_number() ) ); ?></span>
			</a>
		</div>
	</div>
</article>
