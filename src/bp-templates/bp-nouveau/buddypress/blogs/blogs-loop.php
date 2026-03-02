<?php
/**
 * The template for BuddyBoss - Blogs Loop
 *
 * This template can be overridden by copying it to yourtheme/buddypress/blogs/blogs-loop.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

bp_nouveau_before_loop();

if ( bp_has_blogs( bp_ajax_querystring( 'blogs' ) ) ) :
	bp_nouveau_pagination( 'top' );
	?>

	<ul id="blogs-list" class="<?php bp_nouveau_loop_classes(); ?>">
		<?php
		while ( bp_blogs() ) :
			bp_the_blog();

			$blog_permalink = bp_get_blog_permalink();
			?>

			<li <?php bp_blog_class( array( 'item-entry' ) ); ?>>
				<div class="list-wrap">
					<div class="item-avatar">
						<a href="<?php echo esc_url( $blog_permalink ); ?>"><?php bp_blog_avatar( bp_nouveau_avatar_args() ); ?></a>
					</div>
					<div class="item">
						<div class="item-block">
							<h2 class="list-title blogs-title"><a href="<?php echo esc_url( $blog_permalink ); ?>"><?php bp_blog_name(); ?></a></h2>
							<p class="last-activity item-meta"><?php bp_blog_last_active(); ?></p>
							<?php if ( bp_nouveau_blog_has_latest_post() ) : ?>
								<p class="meta last-post">
									<?php bp_blog_latest_post(); ?>
								</p>
								<?php
							endif;
							bp_nouveau_blogs_loop_buttons( array( 'container' => 'ul' ) );
							?>
						</div>
						<?php bp_nouveau_blogs_loop_item(); ?>
					</div>
				</div>
			</li>
		<?php endwhile; ?>
	</ul>

	<?php
	bp_nouveau_pagination( 'bottom' );
else :
	bp_nouveau_user_feedback( 'blogs-loop-none' );
endif;

bp_nouveau_after_loop();
