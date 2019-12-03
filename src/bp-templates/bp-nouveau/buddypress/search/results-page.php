<?php
/**
 * the template file to display search result page having buddypress container
 * dont make changes to this file,
 * instead create a folder 'buddyboss-global-search' inside your theme, copy this file over there, and make changes there
 */

$post_title = BP_Search::instance()->has_search_results() ?
	sprintf( __( 'Showing results for \'%s\'', 'buddyboss' ), $_GET['s'] ) :
	sprintf( __( 'No results for \'%s\'', 'buddyboss' ), $_GET['s'] );
?>
<header class="entry-header">
	<h1 class="entry-title">
		<?php echo $post_title; ?>
	</h1>
</header>

<div id="buddypress">

	<?php bp_get_template_part( 'search/results-page-content' ); ?>

</div>
