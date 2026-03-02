<?php
/**
 * ReadyLaunch - Groups Subgroups template.
 *
 * This template displays subgroups within a parent group
 * with search filters and AJAX loading support.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$is_send_ajax_request = bb_is_send_ajax_request();

bp_nouveau_before_groups_directory_content();

?>
<div class="screen-content">
	<?php bp_get_template_part( 'common/search-and-filters-bar' ); ?>
	<div id="groups-dir-list" class="groups dir-list bb-rl-groups" data-bp-list="group_subgroups" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
		<?php
		if ( $is_send_ajax_request ) {
			echo '<div id="bp-ajax-loader">';
			?>
				<div class="bb-rl-skeleton-grid <?php bp_nouveau_loop_classes(); ?>">
				<?php for ( $i = 0; $i < 8; $i++ ) : ?>
						<div class="bb-rl-skeleton-grid-block bb-rl-skeleton-grid-block--cover">
							<div class="bb-rl-skeleton-cover bb-rl-skeleton-loader"></div>
							<div class="bb-rl-skeleton-avatar bb-rl-skeleton-loader"></div>
							<div class="bb-rl-skeleton-data">
								<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
								<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
							</div>
							<div class="bb-rl-skeleton-loop">
								<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
								<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
								<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
							</div>
							<div class="bb-rl-skeleton-footer">
								<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
							</div>
						</div>
					<?php endfor; ?>
				</div>
				<?php
				// bp_nouveau_user_feedback( 'directory-groups-loading' );
				echo '</div>';
		} else {
			bp_get_template_part( 'groups/groups-loop' );
		}
		?>
	</div><!-- #groups-dir-list -->

	<?php bp_nouveau_after_groups_directory_content(); ?>
</div><!-- // .screen-content -->
