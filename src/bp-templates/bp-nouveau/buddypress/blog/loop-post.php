<?php
/**
 * BuddyBoss - Member profile blog post item (one list item per loop iteration).
 *
 * Loaded by `members/single/blog.php` inside the member Blogs tab loop. Kept as
 * a separate template part so the same item markup can be reused across loops
 * (e.g. the Blogs and Bookmarks sub-tabs), mirroring the ReadyLaunch pack's
 * `blog/loop-post.php`.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Blogging
 */

defined( 'ABSPATH' ) || exit;

$bb_blog_status     = get_post_status();
$bb_blog_status_obj = get_post_status_object( $bb_blog_status );
?>
<li class="bb-member-blog__item">
	<?php if ( has_post_thumbnail() ) : ?>
		<a class="bb-member-blog__thumb" href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'thumbnail' ); ?></a>
	<?php endif; ?>
	<div class="bb-member-blog__body">
		<h3 class="bb-member-blog__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			<?php if ( 'publish' !== $bb_blog_status ) : ?>
				<span class="bb-member-blog__status"><?php echo esc_html( $bb_blog_status_obj ? $bb_blog_status_obj->label : ucfirst( $bb_blog_status ) ); ?></span>
			<?php endif; ?>
		</h3>
		<span class="bb-member-blog__date"><?php echo esc_html( get_the_date() ); ?></span>
	</div>
</li>
