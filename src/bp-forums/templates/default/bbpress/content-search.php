<?php

/**
 * Search Content Part
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div id="bbpress-forums">

	<?php bboss_bbp_breadcrumb(); ?>

	<?php bboss_bbp_set_query_name( bboss_bbp_get_search_rewrite_id() ); ?>

	<?php do_action( 'bbp_template_before_search' ); ?>

	<?php if ( bboss_bbp_has_search_results() ) : ?>

		 <?php bboss_bbp_get_template_part( 'pagination', 'search' ); ?>

		 <?php bboss_bbp_get_template_part( 'loop', 'search' ); ?>

		 <?php bboss_bbp_get_template_part( 'pagination', 'search' ); ?>

	<?php elseif ( bboss_bbp_get_search_terms() ) : ?>

		 <?php bboss_bbp_get_template_part( 'feedback', 'no-search' ); ?>

	<?php else : ?>

		<?php bboss_bbp_get_template_part( 'form', 'search' ); ?>

	<?php endif; ?>

	<?php do_action( 'bbp_template_after_search_results' ); ?>

</div>

