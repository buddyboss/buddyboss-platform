<?php
/**
 * ReadyLaunch - Blog post card template (one card per loop iteration).
 *
 * Shared markup for the grid and list layouts — the two arrangements are
 * CSS-only (`.bb-rl-blog-grid--list` modifier on the container).
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Blogging
 */

defined( 'ABSPATH' ) || exit;

$bb_rl_blog_categories  = get_the_category();
$bb_rl_blog_author_id   = (int) get_the_author_meta( 'ID' );
$bb_rl_blog_author_url  = function_exists( 'bp_core_get_user_domain' ) ? bp_core_get_user_domain( $bb_rl_blog_author_id ) : get_author_posts_url( $bb_rl_blog_author_id );
$bb_rl_blog_author_name = function_exists( 'bp_core_get_user_displayname' ) ? bp_core_get_user_displayname( $bb_rl_blog_author_id ) : get_the_author();

if ( '' === trim( (string) $bb_rl_blog_author_name ) ) {
	$bb_rl_blog_author_name = __( 'Unknown Member', 'buddyboss' );
}
$bb_rl_blog_status        = get_post_status();
$bb_rl_blog_status_obj    = get_post_status_object( $bb_rl_blog_status );
$bb_rl_blog_comment_count = (int) get_comments_number();
?>
<article class="bb-rl-blog-card" id="post-<?php the_ID(); ?>">
	<div class="bb-rl-blog-card__image">
		<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
			<?php
			if ( has_post_thumbnail() ) :
				the_post_thumbnail( 'medium_large', array( 'class' => 'img-responsive' ) );
			else :
				?>
				<img src="<?php echo esc_url( buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/images/group_cover_image.jpeg' ); ?>" alt="<?php esc_attr_e( 'Blog post placeholder image', 'buddyboss' ); ?>">
			<?php endif; ?>
		</a>
		<?php
		/**
		 * Fires inside the blog card image wrapper, for overlay controls
		 * (e.g. Pro's bookmark toggle).
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int $post_id Post ID.
		 */
		do_action( 'bb_blog_card_actions', get_the_ID() );
		?>
	</div>
	<div class="bb-rl-blog-card__content">
		<h2 class="bb-rl-blog-card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			<?php if ( 'publish' !== $bb_rl_blog_status ) : ?>
				<span class="bb-rl-blog-card__status bb-rl-blog-card__status--<?php echo esc_attr( $bb_rl_blog_status ); ?>"><?php echo esc_html( $bb_rl_blog_status_obj ? $bb_rl_blog_status_obj->label : ucfirst( $bb_rl_blog_status ) ); ?></span>
			<?php endif; ?>
		</h2>
		<div class="bb-rl-blog-card__byline">
			<span class="bb-rl-blog-card__date"><?php echo esc_html( get_the_date() ); ?></span>
			<?php if ( ! empty( $bb_rl_blog_categories ) && ! is_wp_error( $bb_rl_blog_categories ) ) : ?>
				<a class="bb-rl-blog-card__category" href="<?php echo esc_url( get_category_link( $bb_rl_blog_categories[0] ) ); ?>"><?php echo esc_html( $bb_rl_blog_categories[0]->name ); ?></a>
			<?php endif; ?>
		</div>
		<div class="bb-rl-blog-card__author">
			<a href="<?php echo esc_url( $bb_rl_blog_author_url ); ?>" class="item-avatar bb-rl-author-avatar">
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
				<?php
				printf(
					/* translators: %s: linked author display name. */
					esc_html__( 'By %s', 'buddyboss' ),
					'<a href="' . esc_url( $bb_rl_blog_author_url ) . '">' . esc_html( $bb_rl_blog_author_name ) . '</a>'
				);
				?>
			</span>
		</div>
		<div class="bb-rl-blog-card__excerpt"><?php the_excerpt(); ?></div>
		<a class="bb-rl-blog-card__view" href="<?php the_permalink(); ?>">
			<?php esc_html_e( 'View Post', 'buddyboss' ); ?>
			<i class="bb-icons-rl bb-icons-rl-caret-right" aria-hidden="true"></i>
		</a>
		<a class="bb-rl-blog-card__comments" href="<?php echo esc_url( get_comments_link() ); ?>">
			<i class="bb-icons-rl bb-icons-rl-chat-circle" aria-hidden="true"></i>
			<?php echo esc_html( number_format_i18n( $bb_rl_blog_comment_count ) ); ?>
		</a>
	</div>
</article>
