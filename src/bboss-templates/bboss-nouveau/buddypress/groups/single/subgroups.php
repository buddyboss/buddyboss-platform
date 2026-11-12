<?php
/**
 * BuddyBoss - Groups Subgroups
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/subgroups.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$is_send_ajax_request = bboss_is_send_ajax_request();

bboss_nouveau_before_groups_directory_content();

if ( ! bboss_nouveau_is_object_nav_in_sidebar() ) {
	bboss_get_template_part( 'common/nav/directory-nav' );
}

if ( 'subgroups' === bboss_current_action() && bboss_enable_content_counts() ) {
	?>
	<div class="bb-item-count">
		<?php
		if ( ! $is_send_ajax_request ) {
			$count = (int) count( bboss_get_descendent_groups( bboss_get_current_group_id(), bboss_loggedin_user_id() ) );
			printf(
				wp_kses(
					/* translators: %d is the group count */
					_n(
						'<span class="bb-count">%d</span> Group',
						'<span class="bb-count">%d</span> Groups',
						$count,
						'buddyboss'
					),
					array( 'span' => array( 'class' => true ) )
				),
				(int) $count
			);

			unset( $count );
		}
		?>
	</div>
	<?php
}
?>
<div class="screen-content">
	<?php bboss_get_template_part( 'common/search-and-filters-bar' ); ?>
	<div id="groups-dir-list" class="groups dir-list" data-bp-list="group_subgroups" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
		<?php
		if ( $is_send_ajax_request ) {
			echo '<div id="bp-ajax-loader">';
			bboss_nouveau_user_feedback( 'directory-groups-loading' );
			echo '</div>';
		} else {
			bboss_get_template_part( 'groups/groups-loop' );
		}
		?>
	</div><!-- #groups-dir-list -->

	<?php bboss_nouveau_after_groups_directory_content(); ?>
</div><!-- // .screen-content -->
