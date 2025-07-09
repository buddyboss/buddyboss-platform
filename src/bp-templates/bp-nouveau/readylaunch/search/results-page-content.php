<?php
/**
 * ReadyLaunch - Search Results Page Content template.
 *
 * Template for displaying the search results page content.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $bb_rl_search_nav;

$no_results_class = ! BP_Search::instance()->has_search_results() ? 'bp-search-no-results' : '';
?>

<div class="bp-search-page buddypress-wrap bp-dir-hori-nav">

	<div class="bp-search-results-wrapper dir-form <?php echo $no_results_class; ?>">

		<div id="bb-rl-search-modal" class="bb-rl-search-modal" style="display: none;">
			<transition name="modal">
				<div class="bb-rl-modal-mask">
					<div class="bb-rl-modal-wrapper">
						<div class="bp-search-form-wrapper dir-search no-ajax">
							<?php bp_search_buffer_template_part( 'search-form' ); ?>
						</div>
					</div>
				</div>
			</transition>
		</div>

		<div class="search_results">
			<?php do_action( 'bp_search_before_result' ); ?>
			<?php bp_search_results(); ?>
			<?php do_action( 'bp_search_after_result' ); ?>
		</div>

	</div>

</div><!-- .bp-search-page -->
