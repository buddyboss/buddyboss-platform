<?php

$ld_group_id =  bp_ld_sync( 'buddypress' )->helpers->getLearndashGroupId( bp_get_current_group_id() );

if ( $ld_group_id ) {
	$post_label_prefix  = 'group';
	$meta              = learndash_get_setting( $ld_group_id );
	$post_price_type   = ( isset( $meta[ $post_label_prefix . '_price_type' ] ) ) ? $meta[ $post_label_prefix . '_price_type' ] : '';
	$post_price        = ( isset( $meta[ $post_label_prefix . '_price' ] ) ) ? $meta[ $post_label_prefix . '_price' ] : '';
	// format the Course price to be proper XXX.YY no leading dollar signs or other values.
	if ( ( 'paynow' === $post_price_type ) || ( 'subscribe' === $post_price_type ) ) {
		if ( '' !== $post_price ) {
			$post_price = preg_replace( '/[^0-9.]/', '', $post_price );
			$post_price = number_format( floatval( $post_price ), 2, '.', '' );
		}
	}
	if ( ! empty( $post_price ) && ! learndash_is_user_in_group( bp_loggedin_user_id(), $ld_group_id )  ) {
		?>
		<div class="bp-feedback error">
			<span class="bp-icon" aria-hidden="true"></span>
			<p><?php echo esc_html__( 'You are not allowed to access group courses. Please purchase membership and try again.', 'buddyboss' ); ?></p>
		</div>
		<?php
		return;
	}
}

global $courses_new;
$count 		 = count( bp_ld_sync( 'buddypress' )->courses->getGroupCourses() );
$courses_new = bp_ld_sync( 'buddypress' )->courses->getGroupCourses();

if ( $count > 1 ) {
	$view              = get_option( 'bb_theme_learndash_grid_list', 'grid' );
	$class_grid_active = ( 'grid' === $view ) ? 'active' : '';
	$class_list_active = ( 'list' === $view ) ? 'active' : '';
	$class_grid_show   = ( 'grid' === $view ) ? 'grid-view bb-grid' : '';
	$class_list_show   = ( 'list' === $view ) ? 'list-view bb-list' : '';
	?>
    <div class="item-body-inner">
        <div id="bb-learndash_profile">
            <div id="learndash-content" class="learndash-course-list">
                <form id="bb-courses-directory-form" class="bb-courses-directory" method="get" action="">
                    <div class="flex align-items-center bb-courses-header">
                        <div id="courses-dir-search" class="bs-dir-search" role="search"></div>
                        <div class="bb-secondary-list-tabs flex align-items-center" id="subnav" aria-label="Members directory secondary navigation" role="navigation">
                            <div class="grid-filters" data-view="ld-course">
                                <a href="#" class="layout-view layout-view-course layout-grid-view bp-tooltip <?php echo esc_attr( $class_grid_active ); ?>" data-view="grid" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Grid View', 'buddyboss' ); ?>">
                                    <i class="dashicons dashicons-screenoptions" aria-hidden="true"></i>
                                </a>
                                <a href="#" class="layout-view layout-view-course layout-list-view bp-tooltip <?php echo esc_attr( $class_list_active ); ?>" data-view="list" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'List View', 'buddyboss' ); ?>">
                                    <i class="dashicons dashicons-menu" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="grid-view bb-grid">
                        <div id="course-dir-list" class="course-dir-list bs-dir-list">
                            <ul id="courses-list" class="bb-course-items <?php echo esc_attr( $class_grid_show . $class_list_show ); ?>">
								<?php
								foreach ( bp_ld_sync( 'buddypress' )->courses->getGroupCourses() as $post ) :
									setup_postdata( $post );
									bp_locate_template( 'groups/single/courses-loop.php', true, false );
								endforeach;
								wp_reset_postdata();
								?>
                            </ul>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
	<?php
}
if ( 1 === $count ) {
	bp_locate_template( 'groups/single/courses-content-display.php', true, false );
}
