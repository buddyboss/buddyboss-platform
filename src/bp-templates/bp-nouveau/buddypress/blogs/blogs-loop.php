<?php
/**
 * The template for BuddyBoss - Blogs Loop
 *
 * This template can be overridden by copying it to yourtheme/buddypress/blogs/blogs-loop.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bboss_nouveau_before_loop();

if ( bboss_has_blogs( bboss_ajax_querystring( 'blogs' ) ) ) :
	bboss_nouveau_pagination( 'top' );
	?>

	<ul id="blogs-list" class="<?php bboss_nouveau_loop_classes(); ?>">
		<?php
		while ( bboss_blogs() ) :
			bboss_the_blog();

			$blog_permalink = bboss_get_blog_permalink();
			?>

			<li <?php bboss_blog_class( array( 'item-entry' ) ); ?>>
				<div class="list-wrap">
					<div class="item-avatar">
						<a href="<?php echo esc_url( $blog_permalink ); ?>"><?php bboss_blog_avatar( bboss_nouveau_avatar_args() ); ?></a>
					</div>
					<div class="item">
						<div class="item-block">
							<h2 class="list-title blogs-title"><a href="<?php echo esc_url( $blog_permalink ); ?>"><?php bboss_blog_name(); ?></a></h2>
							<p class="last-activity item-meta"><?php bboss_blog_last_active(); ?></p>
							<?php if ( bboss_nouveau_blog_has_latest_post() ) : ?>
								<p class="meta last-post">
									<?php bboss_blog_latest_post(); ?>
								</p>
								<?php
							endif;
							bboss_nouveau_blogs_loop_buttons( array( 'container' => 'ul' ) );
							?>
						</div>
						<?php bboss_nouveau_blogs_loop_item(); ?>
					</div>
				</div>
			</li>
		<?php endwhile; ?>
	</ul>

	<?php
	bboss_nouveau_pagination( 'bottom' );
else :
	bboss_nouveau_user_feedback( 'blogs-loop-none' );
endif;

bboss_nouveau_after_loop();
