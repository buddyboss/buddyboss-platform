<?php

/**
 * Pagination for pages of topics (when viewing a forum)
 *
 * @package BuddyBoss\Theme
 */

?>

<?php do_action( 'bbp_template_before_pagination_loop' ); ?>

<div class="bp-pagination top">
	<div class="pag-count top">
		<p class="pag-data">

			<?php bbp_forum_pagination_count(); ?>

		</p>
	</div>

	<div class="bbp-pagination-links">

		<?php bbp_forum_pagination_links(); ?>

	</div>
</div>

<?php do_action( 'bbp_template_after_pagination_loop' ); ?>
