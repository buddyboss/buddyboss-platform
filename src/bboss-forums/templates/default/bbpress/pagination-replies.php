<?php

/**
 * Pagination for pages of replies (when viewing a topic)
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<?php do_action( 'bbp_template_before_pagination_loop' ); ?>

<div class="bbp-pagination">
	<div class="bbp-pagination-count">

		<?php bboss_bbp_topic_pagination_count(); ?>

	</div>

	<div class="bbp-pagination-links">

		<?php bboss_bbp_topic_pagination_links(); ?>

	</div>
</div>

<?php do_action( 'bbp_template_after_pagination_loop' ); ?>
