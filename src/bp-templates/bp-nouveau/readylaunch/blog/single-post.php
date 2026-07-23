<?php
/**
 * ReadyLaunch - Blog single post template.
 *
 * The share row and author bio are appended by the `the_content` filter in
 * bb-blogging-functions.php. Related posts are suppressed there and rendered
 * here instead, as blog cards after the comments — matching the design order.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Blogging
 */

defined( 'ABSPATH' ) || exit;

$bb_rl_blog_categories  = get_the_category();
$bb_rl_blog_author_id   = (int) get_the_author_meta( 'ID' );
$bb_rl_blog_author_url  = function_exists( 'bp_core_get_user_domain' ) ? bp_core_get_user_domain( $bb_rl_blog_author_id ) : get_author_posts_url( $bb_rl_blog_author_id );
$bb_rl_blog_author_name = function_exists( 'bp_core_get_user_displayname' ) ? bp_core_get_user_displayname( $bb_rl_blog_author_id ) : get_the_author();
$bb_rl_blog_tags        = get_the_tags();

if ( '' === trim( (string) $bb_rl_blog_author_name ) ) {
	$bb_rl_blog_author_name = __( 'Unknown Member', 'buddyboss' );
}

// Related posts render after the comments below — keep them out of the
// content-appended footer sections.
add_filter( 'bb_blog_suppress_related_for_theme', '__return_true' );
?>
<div class="bb-rl-secondary-header bb-rl-blog-single-header">
	<a class="bb-rl-blog-single-header__back" href="<?php echo esc_url( get_post_type_archive_link( 'post' ) ); ?>">
		<i class="bb-icons-rl bb-icons-rl-arrow-left" aria-hidden="true"></i>
		<span class="screen-reader-text"><?php esc_html_e( 'Back to blog', 'buddyboss' ); ?></span>
	</a>
	<span class="bb-rl-blog-single-header__crumb"><?php the_title(); ?></span>
</div>
<article class="bb-rl-blog-single" id="post-<?php the_ID(); ?>">
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="bb-rl-blog-single__image"><?php the_post_thumbnail( 'large' ); ?></div>
	<?php endif; ?>
	<h1 class="bb-rl-blog-single__title"><?php the_title(); ?></h1>
	<div class="bb-rl-blog-single__meta">
		<a href="<?php echo esc_url( $bb_rl_blog_author_url ); ?>" class="item-avatar bb-rl-author-avatar" data-bb-hp-profile="<?php echo esc_attr( $bb_rl_blog_author_id ); ?>">
			<?php
			if ( function_exists( 'bp_core_fetch_avatar' ) ) {
				echo bp_core_fetch_avatar( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- avatar HTML built by BuddyPress.
					array(
						'item_id' => $bb_rl_blog_author_id,
						'html'    => true,
					)
				);
			}
			?>
		</a>
		<span class="bb-rl-author-name">
			<a href="<?php echo esc_url( $bb_rl_blog_author_url ); ?>" data-bb-hp-profile="<?php echo esc_attr( $bb_rl_blog_author_id ); ?>"><?php echo esc_html( $bb_rl_blog_author_name ); ?></a>
		</span>
		<span class="bb-rl-blog-single__date"><?php echo esc_html( get_the_date() ); ?></span>
		<?php
		/**
		 * Fires at the trailing edge of the single post header meta row, for
		 * action controls. (Pro's bookmark and subscribe buttons render lower,
		 * in the info row on `bb_rl_blog_single_meta_actions`.)
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int $post_id Post ID.
		 */
		do_action( 'bb_blog_single_meta_actions', get_the_ID() );
		?>
	</div>
	<div class="bb-rl-blog-single__info">
		<div class="bb-rl-blog-single__info_row">
			<?php if ( ! empty( $bb_rl_blog_categories ) && ! is_wp_error( $bb_rl_blog_categories ) ) : ?>
				<div class="bb-rl-blog-single__categories">
					<span class="bb-rl-blog-single__label"><i class="bb-icons-rl bb-icons-rl-folder" aria-hidden="true"></i> <?php esc_html_e( 'Categories:', 'buddyboss' ); ?></span>
					<?php foreach ( $bb_rl_blog_categories as $bb_rl_blog_category ) : ?>
						<a class="bb-rl-blog-single__category" href="<?php echo esc_url( get_category_link( $bb_rl_blog_category ) ); ?>"><?php echo esc_html( $bb_rl_blog_category->name ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $bb_rl_blog_tags ) && ! is_wp_error( $bb_rl_blog_tags ) ) : ?>
				<div class="bb-rl-blog-single__tags">
					<span class="bb-rl-blog-single__label"><i class="bb-icons-rl bb-icons-rl-tag" aria-hidden="true"></i> <?php esc_html_e( 'Tags:', 'buddyboss' ); ?></span>
					<?php foreach ( $bb_rl_blog_tags as $bb_rl_blog_tag ) : ?>
						<a class="bb-rl-blog-single__tag" href="<?php echo esc_url( get_tag_link( $bb_rl_blog_tag ) ); ?>"><?php echo esc_html( $bb_rl_blog_tag->name ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<div class="bb-rl-blog-single__info_row">
			<?php
			/**
			 * Fires inside the single post info row, below the categories and
			 * tags, for action controls such as Pro's bookmark and subscribe
			 * buttons.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param int $post_id Post ID.
			 */
			do_action( 'bb_rl_blog_single_meta_actions', get_the_ID() );
			?>
		</div>
	</div>

	<div class="bb-rl-blog-single__content entry-content">
		<?php the_content(); ?>
	</div>
	<?php
	if ( comments_open() || get_comments_number() ) {
		comments_template();
	}

	$bb_rl_blog_related_query = function_exists( 'bb_blog_is_related_posts_enabled' ) && bb_blog_is_related_posts_enabled()
		? bb_blog_get_related_posts( get_the_ID(), 9 )
		: null;

	if ( $bb_rl_blog_related_query && $bb_rl_blog_related_query->have_posts() ) :
		?>
		<section class="bb-rl-blog-related-cards">
			<div class="bb-rl-blog-related-cards__header">
				<h2 class="bb-rl-blog-related-cards__title"><?php esc_html_e( 'Related Blogs', 'buddyboss' ); ?></h2>
				<div class="bb-rl-blog-related-cards__nav">
					<button type="button" class="bb-rl-blog-related-cards__nav-button" data-bb-rl-related-nav="prev" aria-label="<?php esc_attr_e( 'Previous related blogs', 'buddyboss' ); ?>" disabled>
						<i class="bb-icons-rl bb-icons-rl-caret-left" aria-hidden="true"></i>
					</button>
					<button type="button" class="bb-rl-blog-related-cards__nav-button" data-bb-rl-related-nav="next" aria-label="<?php esc_attr_e( 'Next related blogs', 'buddyboss' ); ?>" disabled>
						<i class="bb-icons-rl bb-icons-rl-caret-right" aria-hidden="true"></i>
					</button>
				</div>
			</div>
			<div class="bb-rl-blog-related-cards__track">
				<?php
				while ( $bb_rl_blog_related_query->have_posts() ) :
					$bb_rl_blog_related_query->the_post();
					bp_get_template_part( 'blog/loop-post' );
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</section>
	<?php endif; ?>
</article>
