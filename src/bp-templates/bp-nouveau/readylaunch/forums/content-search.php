<?php
/**
 * Search Content Template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<div id="bbpress-forums" class="bb-rl-forums-topic-page bb-rl-forum-search-page">

	<div class="bb-rl-forums-container-inner">
		<div class="bb-rl-container-inner">

			<?php bbp_set_query_name( bbp_get_search_rewrite_id() ); ?>

			<?php do_action( 'bbp_template_before_search' ); ?>

			<?php if ( bbp_has_search_results() ) : ?>

				<?php bbp_get_template_part( 'loop', 'search' ); ?>

				<?php bbp_get_template_part( 'pagination', 'search' ); ?>

			<?php elseif ( bbp_get_search_terms() ) : ?>

				<?php bbp_get_template_part( 'feedback', 'no-search' ); ?>

			<?php else : ?>

				<?php bbp_get_template_part( 'form', 'search' ); ?>

			<?php endif; ?>

			<?php do_action( 'bbp_template_after_search_results' ); ?>
		</div>
	</div>
</div>

