<?php
/**
 * Pagination for pages of search results
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<?php do_action( 'bbp_template_before_pagination_loop' ); ?>

<div class="bp-pagination">
	<div class="bp-pagination-count">

		<?php bboss_bbp_search_pagination_count(); ?>

	</div>

	<div class="bp-pagination-links">

		<?php bboss_bbp_search_pagination_links(); ?>

	</div>
</div>

<?php do_action( 'bbp_template_after_pagination_loop' ); ?>
