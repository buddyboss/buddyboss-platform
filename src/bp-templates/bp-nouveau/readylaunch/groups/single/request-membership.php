<?php
/**
 * ReadyLaunch - Groups Request Membership template.
 *
 * This template handles group membership requests with invitation checks,
 * parent group validation, and membership request forms.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_nouveau_group_hook( 'before', 'request_membership_content' );

$current_group_id = bp_get_current_group_id();
$loggedin_user_id = bp_loggedin_user_id();

if ( groups_check_user_has_invite( $loggedin_user_id, $current_group_id ) ) {
	?>
	<aside class="bp-feedback bp-messages loading">
		<span class="bp-icon" aria-hidden="true"></span>
		<p>
			<?php
			$inviter = bp_groups_get_invited_by( $loggedin_user_id, $current_group_id );
			if ( ! empty( $inviter ) ) :
				$groups_link = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() );
				printf(
					esc_html__( 'You are already invited to this group by %1$s %2$s. %3$s', 'buddyboss' ),
					sprintf(
						'<a href="%s">%s</a>',
						$inviter['url'],
						$inviter['name']
					),
					sprintf(
						'<span class="last-activity">%s</span>',
						bp_core_time_since( $inviter['date_modified'] )
					),
					sprintf(
						'<a href="%s" >%s</a>',
						esc_url( trailingslashit( $groups_link . 'invites' ) ),
						esc_html__( 'View Invitation', 'buddyboss' )
					)
				);
				?>
			<?php endif; ?>
		</p>
	</aside>

	<?php
} elseif ( ! bp_group_has_requested_membership() ) {
	$parent_group_id = bp_get_parent_group_id( $current_group_id );
	$is_member       = groups_is_user_member( $loggedin_user_id, $parent_group_id );

	if (
		bb_groups_user_can_send_membership_requests( $current_group_id ) &&
		(
			empty( $parent_group_id ) ||
			(
				! empty( $parent_group_id ) &&
				false !== $is_member
			) ||
			false === bp_enable_group_restrict_invites()
		)
	) {
		?>

	<p>
		<?php printf( esc_html__( 'You are requesting to become a member of the group "%s".', 'buddyboss' ), bp_get_group_name() ); ?>
	</p>

	<form action="<?php bp_group_form_action( 'request-membership' ); ?>" method="post" name="request-membership-form" id="request-membership-form" class="standard-form">
		<label for="group-request-membership-comments"><?php esc_html_e( 'Comments (optional)', 'buddyboss' ); ?></label>
		<textarea name="group-request-membership-comments" id="group-request-membership-comments"></textarea>

		<?php bp_nouveau_group_hook( '', 'request_membership_content' ); ?>

		<p><input type="submit" name="group-request-send" id="group-request-send" value="<?php esc_attr_e( 'Send Request', 'buddyboss' ); ?>" />

		<?php wp_nonce_field( 'groups_request_membership' ); ?>
	</form><!-- #request-membership-form -->
		<?php
	} else {
		$parent_group      = groups_get_group( $parent_group_id );
		$parent_group_name = sprintf(
			'<a class="bp-parent-group-title" href="%s">%s</a>',
			esc_url( bp_get_group_permalink( $parent_group ) ),
			esc_html( bp_get_group_name( $parent_group ) )
		);

		if ( ! empty( $parent_group_id ) ) {
			printf( esc_html__( 'You must first be a member of the parent group "%s" before you can join this group.', 'buddyboss' ), $parent_group_name );
		}
	}
} else {
	bp_nouveau_user_feedback( 'group-requested-membership' );
}

bp_nouveau_group_hook( 'after', 'request_membership_content' );
