<?php

/**
 * Single View Content Part
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div id="bbpress-forums">

	<?php bboss_bbp_breadcrumb(); ?>

	<?php bboss_bbp_set_query_name( bboss_bbp_get_view_rewrite_id() ); ?>

	<?php if ( bboss_bbp_view_query() ) : ?>

		<?php bboss_bbp_get_template_part( 'pagination', 'topics' ); ?>

		<?php bboss_bbp_get_template_part( 'loop', 'topics' ); ?>

		<?php bboss_bbp_get_template_part( 'pagination', 'topics' ); ?>

	<?php else : ?>

		<?php bboss_bbp_get_template_part( 'feedback', 'no-topics' ); ?>

	<?php endif; ?>

	<?php bboss_bbp_reset_query_name(); ?>

</div>
