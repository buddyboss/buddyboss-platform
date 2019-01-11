<li class="bboss_search_item bboss_search_item_post">
    <h3 class="entry-title">
        <a href="<?php the_permalink(); ?>" title="<?php echo esc_attr( sprintf( __( 'Permalink to %s', 'buddypress-global-search' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php the_title(); ?></a>
    </h3>

    <div class="entry-content entry-summary">

        <?php echo make_clickable( get_the_excerpt() ); ?>

    </div><!-- .entry-content -->
</li>
