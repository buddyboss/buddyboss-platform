<?php
/**
 * ReadyLaunch - Member Friends template.
 *
 * This template handles displaying member connections with loading placeholders.
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
	bp_get_template_part( 'members/single/parts/item-subnav' );
	bp_get_template_part( 'common/search-and-filters-bar' );
	?>
</div>
<?php
switch ( bp_current_action() ) :

	// Home/My Connections
	case 'my-friends':
		bp_nouveau_member_hook( 'before', 'friends_content' );
		?>
		<div class="members friends bb-rl-members" data-bp-list="members" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
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
				// bp_nouveau_user_feedback( 'member-friends-loading' );
				echo '</div>';
			} else {
				bp_get_template_part( 'members/members-loop' );
			}
			?>
		</div><!-- .members.friends -->
		<?php
		bp_nouveau_member_hook( 'after', 'friends_content' );
		break;

	case 'requests':
		bp_get_template_part( 'members/single/friends/requests' );
		break;

	case 'mutual':
		bp_nouveau_member_hook( 'before', 'friends_content' );
		?>
		<div class="members mutual-friends bb-rl-members" data-bp-list="members" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
			<?php
			if ( $is_send_ajax_request ) {
				echo '<div id="bp-ajax-loader">';
				bp_nouveau_user_feedback( 'member-mutual-friends-loading' );
				echo '</div>';
			} else {
				bp_get_template_part( 'members/members-loop' );
			}
			?>
		</div><!-- .members.mutual-friends -->
		<?php
		bp_nouveau_member_hook( 'after', 'friends_content' );
		break;

	// Any other
	default:
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;
