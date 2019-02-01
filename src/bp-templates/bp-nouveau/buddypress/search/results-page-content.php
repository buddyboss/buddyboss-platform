<?php
/**
 * the template file to display content search result page
 * instead create a folder 'buddyboss-global-search' inside your theme, copy this file over there, and make changes there
 */

$instance         = BP_Search::instance();
$current_tab      = $instance->search_args['search_subset'];
$no_results_class =
	! isset( $instance->search_results[ $current_tab ]['items'] ) ||
	empty( $instance->search_results[ $current_tab ]['items'] ) ?
		'bp-search-no-results' : '';

?>

<div class="bboss_search_page buddypress-wrap bp-dir-hori-nav">

	<div class="bboss_search_results_wrapper dir-form <?php echo $no_results_class; ?>">

		<nav class="search_filters item-list-tabs main-navs dir-navs bp-navs no-ajax" role="navigation">
			<ul class="component-navigation search-nav">
				<?php bp_search_filters();?>
			</ul>
		</nav>

		<div class="bboss_search_form_wrapper dir-search no-ajax">
			<?php bp_get_template_part('common/search/search-form');?>
		</div>

		<div class="search_results">
			<?php bp_search_results();?>
		</div>

	</div>

</div><!-- .bboss_search_page -->
