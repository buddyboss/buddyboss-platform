<?php
/**
 * BuddyBoss - Groups Members
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/members.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

bp_get_template_part( 'groups/single/parts/members-subnav' );
?>

<div class="subnav-filters filters clearfix no-subnav">
	<?php
	bp_nouveau_search_form();
	bp_get_template_part( 'common/filters/grid-filters' );
	bp_get_template_part( 'common/filters/groups-screens-filters' );
	?>
</div>

<?php
$is_send_ajax_request = bb_is_send_ajax_request();
switch ( bp_action_variable( 0 ) ) :

	// Groups/All Members
	case 'all-members':
		?>
		<div id="bb-rl-members-group-list" class="group_members dir-list bb-rl-members" data-bp-list="group_members" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
			<?php
			if ( $is_send_ajax_request ) {
				echo '<div id="bp-ajax-loader">';
				?>
					<div class="bb-rl-skeleton-grid <?php bp_nouveau_loop_classes(); ?>">
					<?php for ( $i = 0; $i < 8; $i++ ) : ?>
							<div class="bb-rl-skeleton-grid-block">
								<div class="bb-rl-skeleton-avatar bb-rl-skeleton-loader"></div>
								<div class="bb-rl-skeleton-data">
									<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
									<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
									<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
								</div>
								<div class="bb-rl-skeleton-footer">
									<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
									<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
									<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
								</div>
							</div>
						<?php endfor; ?>
					</div>
					<?php
					echo '</div>';
			} else {
				bp_get_template_part( 'groups/single/members-loop' );
			}
			?>
		</div><!-- #members-dir-list -->

		<?php
		break;

	case 'leaders':
		?>
		<div id="bb-rl-members-group-list" class="group_members dir-list bb-rl-members" data-bp-list="group_members" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
			<?php
				if ( $is_send_ajax_request ) {
					echo '<div id="bp-ajax-loader">';
					?>
					<div class="bb-rl-skeleton-grid <?php bp_nouveau_loop_classes(); ?>">
						<?php for ( $i = 0; $i < 8; $i++ ) : ?>
							<div class="bb-rl-skeleton-grid-block">
								<div class="bb-rl-skeleton-avatar bb-rl-skeleton-loader"></div>
								<div class="bb-rl-skeleton-data">
									<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
									<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
									<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
								</div>
								<div class="bb-rl-skeleton-footer">
									<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
									<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
									<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
								</div>
							</div>
						<?php endfor; ?>
					</div>
					<?php
					echo '</div>';
				} else {
					bp_get_template_part( 'groups/single/members-loop' );
				}
			?>
		</div><!-- #members-dir-list -->
		<?php
		break;

	// Any other
	default:
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;
