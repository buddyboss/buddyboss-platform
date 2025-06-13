<?php

/**
 * Pagination for pages of replies (when viewing a topic)
 *
 * @package BuddyBoss\Theme
 */

?>

<?php do_action( 'bbp_template_before_pagination_loop' ); ?>

<div class="bp-pagination">
	<div class="bp-pagination-count">

		<?php bbp_topic_pagination_count(); ?>

	</div>

	<div class="bp-pagination-links">

		<?php bbp_topic_pagination_links(); ?>

	</div>
</div>

<?php do_action( 'bbp_template_after_pagination_loop' ); ?>
