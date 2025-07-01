<?php
/**
 * ReadyLaunch - Member Groups template.
 *
 * This template handles displaying member groups with loading placeholders.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$is_send_ajax_request = bb_is_send_ajax_request();
?>
<div class="bb-rl-sub-ctrls flex items-center justify-between">
	<?php
	if ( bp_is_my_profile() ) {
		bp_get_template_part( 'members/single/parts/item-subnav' );
	}

	if ( ! bp_is_current_action( 'invites' ) ) {
		bp_get_template_part( 'common/search-and-filters-bar' );
	}
	?>
</div>
<?php

switch ( bp_current_action() ) :

	// Home/My Groups
	case 'my-groups':
		bp_nouveau_member_hook( 'before', 'groups_content' );
		?>
		<div class="groups mygroups bb-rl-groups" data-bp-list="groups" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
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
				echo '</div>';
			} else {
				bp_get_template_part( 'groups/groups-loop' );
			}
			?>
		</div>
		<?php
		bp_nouveau_member_hook( 'after', 'groups_content' );
		break;

	// Group Invitations
	case 'invites':
		bp_get_template_part( 'members/single/groups/invites' );
		break;

	// Any other
	default:
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;
