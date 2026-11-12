<?php
/**
 * BuddyBoss - Groups Request Membership
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/request-membership.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bboss_nouveau_group_hook( 'before', 'request_membership_content' );

$current_group_id = bboss_get_current_group_id();
$loggedin_user_id = bboss_loggedin_user_id();

if ( bboss_groups_check_user_has_invite( $loggedin_user_id, $current_group_id ) ) {
	?>
	<aside class="bp-feedback bp-messages loading">
		<span class="bp-icon" aria-hidden="true"></span>
		<p>
			<?php
			$inviter = bboss_groups_get_invited_by( $loggedin_user_id, $current_group_id );
			if ( ! empty( $inviter ) ) :
				$groups_link = trailingslashit( bboss_loggedin_user_domain() . bboss_get_groups_slug() );
				printf(
					/* translators: 1: inviter profile link, 2: time since the invitation, 3: link to view the invitation. */
					esc_html__( 'You are already invited to this group by %1$s %2$s. %3$s', 'buddyboss-platform' ),
					sprintf(
						'<a href="%s">%s</a>',
						esc_url( $inviter['url'] ),
						esc_html( $inviter['name'] )
					),
					sprintf(
						'<span class="last-activity">%s</span>',
						esc_html( bboss_core_time_since( $inviter['date_modified'] ) )
					),
					sprintf(
						'<a href="%s" >%s</a>',
						esc_url( trailingslashit( $groups_link . 'invites' ) ),
						esc_html__( 'View Invitation', 'buddyboss-platform' )
					)
				);
				?>
			<?php endif; ?>
		</p>
	</aside>

	<?php
} elseif ( ! bboss_group_has_requested_membership() ) {
	$parent_group_id = bboss_get_parent_group_id( $current_group_id );
	$is_member       = bboss_groups_is_user_member( $loggedin_user_id, $parent_group_id );

	if (
		bboss_groups_user_can_send_membership_requests( $current_group_id ) &&
		(
			empty( $parent_group_id ) ||
			(
				! empty( $parent_group_id ) &&
				false !== $is_member
			) ||
			false === bboss_enable_group_restrict_invites()
		)
	) {

		$get_selected_member_type_join   = array();
		$get_requesting_user_member_type = '';
		if (
			true === bboss_member_type_enable_disable() &&
			true === bboss_disable_group_type_creation()
		) {
			$group_type                      = bboss_groups_get_group_type( $current_group_id );
			$group_type_id                   = bboss_group_get_group_type_id( $group_type );
			$get_selected_member_type_join   = get_post_meta( $group_type_id, '_bp_group_type_enabled_member_type_join', true );
			$get_selected_member_type_join   = ( isset( $get_selected_member_type_join ) && ! empty( $get_selected_member_type_join ) ) ? $get_selected_member_type_join : array();
			$get_requesting_user_member_type = bboss_get_member_type( bboss_loggedin_user_id() );
		}
		if (
			! empty( $get_selected_member_type_join ) &&
			is_array( $get_selected_member_type_join ) &&
			! empty( $get_requesting_user_member_type ) &&
			in_array( $get_requesting_user_member_type, $get_selected_member_type_join, true )
		) {
			bboss_nouveau_user_feedback( 'group-request-join-member-type' );
		} else {
			?>
			<p>
				<?php /* translators: %s: group name. */ echo sprintf( esc_html__( 'You are requesting to become a member of the group "%s".', 'buddyboss-platform' ), esc_html( bboss_get_group_name() ) ); ?>
			</p>

			<form action="<?php bboss_group_form_action( 'request-membership' ); ?>" method="post" name="request-membership-form" id="request-membership-form" class="standard-form">
				<label for="group-request-membership-comments"><?php esc_html_e( 'Comments (optional)', 'buddyboss-platform' ); ?></label>
				<textarea name="group-request-membership-comments" id="group-request-membership-comments" aria-label="<?php esc_attr_e( 'Group Request Comments', 'buddyboss-platform' ); ?>"></textarea>

				<?php bboss_nouveau_group_hook( '', 'request_membership_content' ); ?>

				<p><input type="submit" name="group-request-send" id="group-request-send" value="<?php esc_attr_e( 'Send Request', 'buddyboss-platform' ); ?>" /></p>
				<?php wp_nonce_field( 'groups_request_membership' ); ?>
			</form><!-- #request-membership-form -->
			<?php
		}
	} else {
		$parent_group      = bboss_groups_get_group( $parent_group_id );
		$parent_group_name = sprintf(
			'<a class="bp-parent-group-title" href="%s">%s</a>',
			esc_url( bboss_get_group_permalink( $parent_group ) ),
			esc_html( bboss_get_group_name( $parent_group ) )
		);

		if ( ! empty( $parent_group_id ) ) {
			/* translators: %s: parent group name link. */
			echo wp_kses_post( sprintf( esc_html__( 'You must first be a member of the parent group "%s" before you can join this group.', 'buddyboss-platform' ), $parent_group_name ) );
		}
	}
} else {
	bboss_nouveau_user_feedback( 'group-requested-membership' );
}

bboss_nouveau_group_hook( 'after', 'request_membership_content' );
