<?php
/**
 * The template for users groups
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/groups.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$is_send_ajax_request = bboss_is_send_ajax_request();

if ( bboss_is_my_profile() ) {
	bboss_get_template_part( 'members/single/parts/item-subnav' );
}

$bp_current_action = bboss_current_action();
if ( bboss_enable_content_counts() ) {
	?>
	<div class="bb-item-count">
		<?php
			if ( ! $is_send_ajax_request ) {
				$count = false;
				if ( 'my-groups' === $bp_current_action ) {
					$count = bboss_get_total_group_count_for_user( bboss_displayed_user_id() );
				} elseif ( 'invites' === $bp_current_action ) {
					$count = bboss_groups_get_invite_count_for_user( bboss_displayed_user_id() );
				}

				if ( false !== $count ) {
					if ( 'invites' === $bp_current_action ) {
						printf(
							wp_kses(
								/* translators: %d is the count */
								_n(
									'<span class="bb-count">%d</span> Invite',
									'<span class="bb-count">%d</span> Invites',
									$count,
									'buddyboss'
								),
								array( 'span' => array( 'class' => true ) )
							),
							(int) $count
						);
					} else {
						printf(
							wp_kses(
								/* translators: %d is the count */
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
					}
				}

				unset( $count );
			}
		?>
	</div>
	<?php
}
if ( ! bboss_is_current_action( 'invites' ) ) {
	bboss_get_template_part( 'common/search-and-filters-bar' );
}

switch ( $bp_current_action ) :

	// Home/My Groups
	case 'my-groups':
		bboss_nouveau_member_hook( 'before', 'groups_content' );
		?>
		<div class="groups mygroups" data-bp-list="groups" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
			<?php
			if ( $is_send_ajax_request ) {
				echo '<div id="bp-ajax-loader">';
				bboss_nouveau_user_feedback( 'member-groups-loading' );
				echo '</div>';
			} else {
				bboss_get_template_part( 'groups/groups-loop' );
			}
			?>
		</div>
		<?php
		bboss_nouveau_member_hook( 'after', 'groups_content' );
		break;

	// Group Invitations
	case 'invites':
		bboss_get_template_part( 'members/single/groups/invites' );
		break;

	// Any other
	default:
		bboss_get_template_part( 'members/single/plugins' );
		break;
endswitch;
