<?php
/**
 * The template for users connections
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/friends.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$is_send_ajax_request = bboss_is_send_ajax_request();
$bp_current_action    = bboss_current_action();
bboss_get_template_part( 'members/single/parts/item-subnav' );

if ( 'my-friends' === $bp_current_action && bboss_enable_content_counts() ) {
	?>
		<div class="bb-item-count">
			<?php
			if ( ! $is_send_ajax_request ) {
				$count = bboss_friends_get_total_friend_count();
				if ( 'requests' === $bp_current_action ) {
					printf(
						wp_kses(
							/* translators: %d is the count */
							_n(
								'<span class="bb-count">%d</span> Request',
								'<span class="bb-count">%d</span> Requests',
								$count,
								'buddyboss-platform'
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
								'<span class="bb-count">%d</span> Connection',
								'<span class="bb-count">%d</span> Connections',
								$count,
								'buddyboss-platform'
							),
							array( 'span' => array( 'class' => true ) )
						),
						(int) $count
					);
				}

				unset( $count );
			}
			?>
		</div>
	<?php
}

bboss_get_template_part( 'common/search-and-filters-bar' );

switch ( $bp_current_action ) :

	// Home/My Connections
	case 'my-friends':
		bboss_nouveau_member_hook( 'before', 'friends_content' );
		?>
		<div class="members friends" data-bp-list="members" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
			<?php
			if ( $is_send_ajax_request ) {
				echo '<div id="bp-ajax-loader">';
				bboss_nouveau_user_feedback( 'member-friends-loading' );
				echo '</div>';
			} else {
				bboss_get_template_part( 'members/members-loop' );
			}
			?>
		</div><!-- .members.friends -->
		<?php
		bboss_nouveau_member_hook( 'after', 'friends_content' );
		break;

	case 'requests':
		bboss_get_template_part( 'members/single/friends/requests' );
		break;

	case 'mutual':
		bboss_nouveau_member_hook( 'before', 'friends_content' );
		?>
		<div class="members mutual-friends" data-bp-list="members" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
			<?php
			if ( $is_send_ajax_request ) {
				echo '<div id="bp-ajax-loader">';
				bboss_nouveau_user_feedback( 'member-mutual-friends-loading' );
				echo '</div>';
			} else {
				bboss_get_template_part( 'members/members-loop' );
			}
			?>
		</div><!-- .members.mutual-friends -->
		<?php
		bboss_nouveau_member_hook( 'after', 'friends_content' );
		break;

	// Any other
	default:
		bboss_get_template_part( 'members/single/plugins' );
		break;
endswitch;
