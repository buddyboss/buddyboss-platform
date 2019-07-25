<?php
/**
 * BuddyBoss - Groups Request Membership
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

bp_nouveau_group_hook( 'before', 'request_membership_content' ); ?>

<?php if ( ! bp_group_has_requested_membership() ) : ?>
	<p>
		<?php echo sprintf( __( 'You are requesting to become a member of the group "%s".', 'buddyboss' ), bp_get_group_name() ); ?>
	</p>

	<form action="<?php bp_group_form_action( 'request-membership' ); ?>" method="post" name="request-membership-form" id="request-membership-form" class="standard-form">
		<label for="group-request-membership-comments"><?php esc_html( 'Comments (optional)', 'buddyboss' ); ?></label>
		<textarea name="group-request-membership-comments" id="group-request-membership-comments"></textarea>

		<?php bp_nouveau_group_hook( '', 'request_membership_content' ); ?>

		<p><input type="submit" name="group-request-send" id="group-request-send" value="<?php esc_attr_e( 'Send Request', 'buddyboss' ); ?>" />

		<?php wp_nonce_field( 'groups_request_membership' ); ?>
	</form><!-- #request-membership-form -->

<?php else : ?>
    <?php bp_nouveau_user_feedback( 'group-requested-membership' ); ?>
<?php endif; ?>

<?php
bp_nouveau_group_hook( 'after', 'request_membership_content' );
