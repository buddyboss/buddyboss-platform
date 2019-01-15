<?php
/**
 * the template file to display content search result page
 * instead create a folder 'buddyboss-global-search' inside your theme, copy this file over there, and make changes there
 */
?>


<div class="bboss_search_page buddypress-wrap">
		<div class="bboss_search_form_wrapper dir-search no-ajax">
			<?php get_search_form();?>
		</div>
		<div class="bboss_search_results_wrapper dir-form">
			<div class="search_filters item-list-tabs bp-navs no-ajax" role="navigation">
				<ul>
					<?php buddyboss_global_search_filters();?>
				</ul>
			</div>
			<div class="search_results">
				<?php buddyboss_global_search_results();?>
			</div>
		</div>
</div><!-- .bboss_search_page -->