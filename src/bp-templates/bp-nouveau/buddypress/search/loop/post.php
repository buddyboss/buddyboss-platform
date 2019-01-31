<li class="bboss_search_item bboss_search_item_post">
	<div class="list-wrap">
		<div class="item-avatar">
			<a href="<?php the_permalink(); ?>">
				<?php if( has_post_thumbnail() ) {
					the_post_thumbnail();
				} else {
					// Image url
				} ?>
			</a>
		</div>

		<div class="item">
			<h3 class="entry-title">
				<a href="<?php the_permalink(); ?>" title="<?php echo esc_attr( sprintf( __( 'Permalink to %s', 'buddypress-global-search' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php the_title(); ?></a>
			</h3>

			<div class="entry-content entry-summary">
				<?php echo make_clickable( get_the_excerpt() ); ?>
			</div>
		</div>
	</div>
</li>
