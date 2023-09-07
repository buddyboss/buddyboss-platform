<?php
/**
 * BuddyPress Members Widgets.
 *
 * @package BuddyBoss\Members\Widgets
 * @since BuddyPress 2.2.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register bp-members widgets.
 *
 * Previously, these widgets were registered in bp-core.
 *
 * @since BuddyPress 2.2.0
 */
function bp_members_register_widgets() {
	add_action(
		'widgets_init',
		function() {
			return register_widget( 'BP_Core_Members_Widget' );
		}
	);
	add_action(
		'widgets_init',
		function() {
			return register_widget( 'BP_Core_Whos_Online_Widget' );
		}
	);
	add_action(
		'widgets_init',
		function() {
			return register_widget( 'BP_Core_Recently_Active_Widget' );
		}
	);
}
add_action( 'bp_register_widgets', 'bp_members_register_widgets' );

/**
 * AJAX request handler for Members widgets.
 *
 * @since BuddyPress 1.0.0
 *
 * @see BP_Core_Members_Widget
 */
function bp_core_ajax_widget_members() {
	global $members_template;
	check_ajax_referer( 'bp_core_widget_members' );

	// Setup some variables to check.
	$filter      = ! empty( $_POST['filter'] ) ? $_POST['filter'] : 'recently-active-members';
	$max_members = ! empty( $_POST['max-members'] ) ? absint( $_POST['max-members'] ) : 5;

	// Determine the type of members query to perform.
	switch ( $filter ) {

		// Newest activated.
		case 'newest-members':
			$type = 'newest';
			break;

		// Popular by friends.
		case 'popular-members':
			if ( bp_is_active( 'friends' ) ) {
				$type = 'popular';
			} else {
				$type = 'active';
			}
			break;

		// Default.
		case 'recently-active-members':
		default:
			$type = 'active';
			break;
	}

	// Setup args for querying members.
	$members_args = array(
		'user_id'         => 0,
		'type'            => $type,
		'per_page'        => $max_members,
		'max'             => false,
		'populate_extras' => true,
		'search_terms'    => false,
		'exclude'         => ( function_exists( 'bp_get_users_of_removed_member_types' ) && ! empty( bp_get_users_of_removed_member_types() ) ) ? bp_get_users_of_removed_member_types() : '',
	);
	$result       = array();
	$content      = '';
	// Query for members.
	if ( bp_has_members( $members_args ) ) :

		$result['success']   = 1;
		$result['show_more'] = ( $members_template->total_member_count > $max_members ) ? true : false;
		ob_start();
		while ( bp_members() ) :
			bp_the_member();

			$moderation_class = function_exists( 'bp_moderation_is_user_suspended' ) && bp_moderation_is_user_suspended( $members_template->member->id ) ? 'bp-user-suspended' : '';
			$moderation_class = function_exists( 'bp_moderation_is_user_blocked' ) && bp_moderation_is_user_blocked( $members_template->member->id ) ? $moderation_class . ' bp-user-blocked' : $moderation_class;
			?>
			<li class="vcard">
				<div class="item-avatar">
					<a href="<?php bp_member_permalink(); ?>" class="<?php echo esc_attr( $moderation_class ); ?>">
						<?php bp_member_avatar(); ?>
						<?php bb_user_presence_html( $members_template->member->id ); ?>
					</a>
				</div>

				<div class="item">
					<div class="item-title fn"><a href="<?php bp_member_permalink(); ?>"><?php bp_member_name(); ?></a></div>
					<div class="item-meta">

						<?php if ( isset( $settings['member_default'] ) && 'newest' === $settings['member_default'] ) : ?>
							<span class="activity" data-livestamp="<?php bp_core_iso8601_date( bp_get_member_registered( array( 'relative' => false ) ) ); ?>"><?php bp_member_registered(); ?></span>
						<?php elseif ( isset( $settings['member_default'] ) && 'active' === $settings['member_default'] ) : ?>
							<span class="activity" data-livestamp="<?php bp_core_iso8601_date( bp_get_member_last_active( array( 'relative' => false ) ) ); ?>"><?php bp_member_last_active(); ?></span>
						<?php elseif ( bp_is_active( 'friends' ) ) : ?>
							<span class="activity"><?php bp_member_total_friend_count(); ?></span>
						<?php endif; ?>
					</div>
				</div>
				<div class="member_last_visit"></div>
			</li>
			<?php
		endwhile;
		$content .= ob_get_clean();
		?>

		<?php
	else :
		$result['success']   = 0;
		$result['show_more'] = false;
		ob_start();
		?>
		<?php esc_html_e( 'There were no members found, please try another filter.', 'buddyboss' ); ?>
		<?php echo '</li>'; ?>
		<?php
		$content .= ob_get_clean();
	endif;
	$result['data'] = $content;
	echo wp_json_encode( $result );
	exit;
}
add_action( 'wp_ajax_widget_members', 'bp_core_ajax_widget_members' );
add_action( 'wp_ajax_nopriv_widget_members', 'bp_core_ajax_widget_members' );

