<?php
/**
 * ReadyLaunch - Document loop template.
 *
 * This template handles the document listing loop with sorting
 * and pagination functionality.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_nouveau_before_loop();

if ( bp_has_document( bp_ajax_querystring( 'document' ) ) ) :

	$get_page    = bb_filter_input_string( INPUT_POST, 'page' );
	$order_by    = bb_filter_input_string( INPUT_POST, 'order_by' );
	$order_by_db = bb_filter_input_string( INPUT_POST, 'orderby' );
	$sort        = bb_filter_input_string( INPUT_POST, 'sort' );
	$scope       = bb_filter_input_string( INPUT_POST, 'scope' );
	$extras      = filter_input( INPUT_POST, 'extras', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

	if ( empty( $get_page ) || 1 === (int) $get_page ) :
		$active_title_class   = '';
		$active_date_class    = '';
		$active_privacy_class = '';
		$active_group_class   = '';
		if ( isset( $order_by ) && isset( $sort ) && 'title' === $order_by && 'DESC' === $sort ) {
			$active_title_class = 'asce';
		} elseif ( isset( $extras['orderby'] ) && isset( $extras['sort'] ) && 'title' === $extras['orderby'] && 'DESC' === $extras['sort'] ) {
			$active_title_class = 'asce';
		} elseif ( isset( $extras['orderby'] ) && isset( $extras['sort'] ) && 'date_modified' === $extras['orderby'] && 'DESC' === $extras['sort'] ) {
			$active_date_class = 'asce';
		} elseif ( isset( $order_by ) && isset( $sort ) && 'date_modified' === $order_by && 'DESC' === $sort ) {
			$active_date_class = 'asce';
		} elseif ( isset( $order_by ) && isset( $sort ) && 'privacy' === $order_by && 'DESC' === $sort ) {
			$active_privacy_class = 'asce';
		} elseif ( isset( $extras['orderby'] ) && isset( $extras['sort'] ) && 'privacy' === $extras['orderby'] && 'DESC' === $extras['sort'] ) {
			$active_privacy_class = 'asce';
		} elseif ( isset( $extras['orderby'] ) && isset( $extras['sort'] ) && 'group_id' === $extras['orderby'] && 'DESC' === $extras['sort'] ) {
			$active_group_class = 'asce';
		} elseif ( isset( $order_by ) && isset( $sort ) && 'group_id' === $order_by && 'DESC' === $sort ) {
			$active_group_class = 'asce';
		}
		?>

		<div id="media-folder-document-data-table" class="document-list item-list bp-list bb-document-list grid">
		<?php
		bp_get_template_part( 'document/activity-document-move' );
		bp_get_template_part( 'document/activity-document-folder-move' );
		bp_get_template_part( 'document/document-edit' );
	endif;

	while ( bp_document() ) :
		bp_the_document();

		bp_get_template_part( 'document/document-entry' );
	endwhile;

	if ( bp_document_has_more_items() ) :
		?>
		<div class="pager">
			<div class="dt-more-container load-more">
				<a class="button outline full" href="<?php bp_document_load_more_link(); ?>"><?php esc_html_e( 'Show More', 'buddyboss' ); ?><i class="bb-icons-rl-caret-down"></i></a>
			</div>
		</div>
		<?php
	endif;

	if ( empty( $get_page ) || 1 === (int) $get_page ) :
		?>
		</div> <!-- #media-folder-document-data-table -->
		<?php
	endif;
else :
	bp_get_template_part( 'document/no-document' );
endif;

bp_nouveau_after_loop();
