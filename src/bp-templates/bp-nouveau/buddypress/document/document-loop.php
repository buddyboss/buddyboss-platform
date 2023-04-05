<?php
/**
 * The template for document loop
 *
 * This template can be overridden by copying it to yourtheme/buddypress/document/document-loop.php.
 *
 * @since   BuddyBoss 1.4.0
 * @package BuddyBoss\Core
 * @version 1.4.0
 */

bp_nouveau_before_loop();

if ( bp_has_document( bp_ajax_querystring( 'document' ) ) ) :

	$get_page = bb_filter_input_string( INPUT_POST, 'page' );
	$order_by = bb_filter_input_string( INPUT_POST, 'order_by' );
	$orderby  = bb_filter_input_string( INPUT_POST, 'orderby' );
	$sort     = bb_filter_input_string( INPUT_POST, 'sort' );
	$scope    = bb_filter_input_string( INPUT_POST, 'scope' );
	$extras   = filter_input( INPUT_POST, 'extras', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

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
        <div class="document-data-table-head">
            <div class="data-head data-head-name <?php echo esc_attr( $active_title_class ); ?>" data-target="name">
				<span>
					<?php esc_html_e( 'Name', 'buddyboss' ); ?>
					<i class="bb-icon-f bb-icon-caret-down"></i>
				</span>
            </div>
            <div class="data-head data-head-modified <?php echo esc_attr( $active_date_class ); ?>" data-target="modified">
				<span>
					<?php esc_html_e( 'Modified', 'buddyboss' ); ?>
					<i class="bb-icon-f bb-icon-caret-down"></i>
				</span>
            </div>
            <div class="data-head data-head-origin <?php echo esc_attr( $active_group_class ); ?>" data-target="group">
				<?php
				if ( bp_is_document_directory() && bp_is_active( 'groups' ) && isset( $scope ) && 'personal' !== $scope ) {
					?>
                    <span>
					<?php esc_html_e( 'Group', 'buddyboss' ); ?>
						<i class="bb-icon-f bb-icon-caret-down"></i>
					</span>
					<?php
				}
				?>
            </div>

            <div class="data-head data-head-visibility <?php echo esc_attr( $active_privacy_class ); ?>" data-target="visibility">
				<span>
					<?php esc_html_e( 'Visibility', 'buddyboss' ); ?>
					<i class="bb-icon-f bb-icon-caret-down"></i>
				</span>
            </div>
        </div><!-- .document-data-table-head -->
        <div id="media-folder-document-data-table">
		<?php
		bp_get_template_part( 'document/activity-document-move' );
		bp_get_template_part( 'document/activity-document-folder-move' );
	endif;
	while ( bp_document() ) :
		bp_the_document();

		bp_get_template_part( 'document/document-entry' );
	endwhile;
	if ( bp_document_has_more_items() ) :
		?>
        <div class="pager">
            <div class="dt-more-container load-more">
                <a class="button outline full" href="<?php bp_document_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
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
	bp_nouveau_user_feedback( 'media-loop-document-none' );
endif;

bp_nouveau_after_loop();
