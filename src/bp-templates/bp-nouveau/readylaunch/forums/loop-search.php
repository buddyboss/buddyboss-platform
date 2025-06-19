<?php

/**
 * Search Loop
 *
 * @package BuddyBoss\Theme
 */

?>

<?php do_action( 'bbp_template_before_search_results_loop' ); ?>

<ul id="bbp-search-results" class="forums bbp-search-results">

	<li class="bbp-header">

		<div class="bbp-search-author"><?php _e( 'Author', 'buddyboss' ); ?></div><!-- .bbp-reply-author -->

		<div class="bbp-search-content">

			<?php _e( 'Search Results', 'buddyboss' ); ?>

		</div><!-- .bbp-search-content -->

	</li><!-- .bbp-header -->

	<li class="bbp-body">

		<?php
		while ( bbp_search_results() ) :
			bbp_the_search_result();
			?>

			<?php bbp_get_template_part( 'loop', 'search-' . get_post_type() ); ?>

		<?php endwhile; ?>

	</li><!-- .bbp-body -->

	<li class="bbp-footer">

		<div class="bbp-search-author"><?php _e( 'Author', 'buddyboss' ); ?></div>

		<div class="bbp-search-content">

			<?php _e( 'Search Results', 'buddyboss' ); ?>

		</div><!-- .bbp-search-content -->

	</li><!-- .bbp-footer -->

</ul><!-- #bbp-search-results -->

<?php do_action( 'bbp_template_after_search_results_loop' ); ?>
