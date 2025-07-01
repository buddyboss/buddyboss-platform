<?php
/**
 * Pagination for pages of search results
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<?php do_action( 'bbp_template_before_pagination_loop' ); ?>

<div class="bp-pagination">
	<div class="bp-pagination-count">

		<?php bbp_search_pagination_count(); ?>

	</div>

	<div class="bp-pagination-links">

		<?php bbp_search_pagination_links(); ?>

	</div>
</div>

<?php do_action( 'bbp_template_after_pagination_loop' ); ?>
