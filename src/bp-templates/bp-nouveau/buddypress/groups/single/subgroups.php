<?php
/**
 * BuddyBoss - Groups Subgroups
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/subgroups.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

$is_send_ajax_request = bb_is_send_ajax_request();

bp_nouveau_before_groups_directory_content();

if ( ! bp_nouveau_is_object_nav_in_sidebar() ) {
	bp_get_template_part( 'common/nav/directory-nav' );
}

if ( 'subgroups' === bp_current_action() ) {
	$enable_group_count = bb_group_directory_count_enable();
	$count              = $enable_group_count ? (int) count( bp_get_descendent_groups( bp_get_current_group_id(), bp_loggedin_user_id() ) ) : false;
	if ( false !== $count ) {
		?>
		<div class="bb-item-count">
			<?php
			if ( ! $is_send_ajax_request ) {

				/* translators: %d is the group count */
				printf(
					wp_kses( _n( '<span class="bb-count">%d</span> Group', '<span class="bb-count">%d</span> Groups', $count, 'buddyboss' ), array( 'span' => array( 'class' => true ) ) ),
					$count
				);
			}
			?>
		</div>
		<?php
	}
}
?>
<div class="screen-content">
	<?php bp_get_template_part( 'common/search-and-filters-bar' ); ?>
	<div id="groups-dir-list" class="groups dir-list" data-bp-list="group_subgroups" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
		<?php
		if ( $is_send_ajax_request ) {
			echo '<div id="bp-ajax-loader">';
			bp_nouveau_user_feedback( 'directory-groups-loading' );
			echo '</div>';
		} else {
			bp_get_template_part( 'groups/groups-loop' );
		}
		?>
	</div><!-- #groups-dir-list -->

	<?php bp_nouveau_after_groups_directory_content(); ?>
</div><!-- // .screen-content -->
