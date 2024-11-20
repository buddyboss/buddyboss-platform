<?php
/**
 * BP Nouveau - Groups Directory
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/index.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

$is_send_ajax_request = bb_is_send_ajax_request();

bp_nouveau_before_groups_directory_content();
bp_nouveau_template_notices();

if ( ! bp_nouveau_is_object_nav_in_sidebar() ) {
	bp_get_template_part( 'common/nav/directory-nav' );
}
?>
<div class="screen-content">

	<?php
	$bp_nouveau = bp_nouveau();
	if ( 'directory' === $bp_nouveau->displayed_nav && isset( $bp_nouveau->sorted_nav[0]->count ) ) {
		$count = $bp_nouveau->sorted_nav[0]->count;
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
	
	bp_get_template_part( 'common/search-and-filters-bar' ); ?>

	<div id="groups-dir-list" class="groups dir-list" data-bp-list="groups" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
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
