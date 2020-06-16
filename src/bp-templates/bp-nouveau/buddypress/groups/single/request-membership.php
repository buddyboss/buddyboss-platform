<?php
/**
 * BuddyBoss - Groups Request Membership
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

global $bp;
bp_nouveau_group_hook( 'before', 'request_membership_content' );

if ( groups_check_user_has_invite( bp_loggedin_user_id(), $bp->groups->current_group->id ) ) {

	bp_nouveau_group_hook( 'before', 'invites_content' );
	?>

	<div class="entry-content">
		<ul id="groups-list" class="invites item-list bp-list item-list groups-list" data-bp-list="groups_invites">

			<li <?php bp_group_class( array( 'item-entry' ) ); ?> data-bp-item-id="<?php bp_group_id(); ?>" data-bp-item-component="groups">
				<div class="list-wrap">

					<?php if ( ! bp_disable_group_avatar_uploads() ) : ?>
						<div class="item-avatar">
							<a href="<?php bp_group_permalink(); ?>"><?php bp_group_avatar( bp_nouveau_avatar_args() ); ?></a>
						</div>
					<?php endif; ?>

					<div class="item">

						<div class="item-block">

							<h2 class="list-title groups-title"><?php bp_group_link(); ?></h2>

							<p class="item-meta group-details">
								<?php $inviter = bp_groups_get_invited_by( bp_loggedin_user_id(), $bp->groups->current_group->id ); ?>
								<?php if ( ! empty( $inviter ) ) : ?>
									<?php
									printf(
										__( 'Invited by %1$s &middot; %2$s.', 'buddyboss-theme' ),
										sprintf(
											'<a href="%s">%s</a>',
											$inviter['url'],
											$inviter['name']
										),
										sprintf(
											'<span class="last-activity">%s</span>',
											bp_core_time_since( $inviter['date_modified'] )
										)
									);
									?>
								<?php endif; ?>
							</p>

							<p class="desc item-meta invite-message">
								<?php echo bp_groups_get_invite_messsage_for_user( bp_displayed_user_id(), bp_get_group_id() ); ?>
							</p>

						</div>

						<?php bp_nouveau_group_hook( '', 'invites_item' ); ?>

						<?php
						bp_nouveau_groups_invite_buttons(
							array(
								'container'      => 'ul',
								'button_element' => 'button',
							)
						);
						?>

					</div>
				</div>
			</li>
		</ul>
	</div>

	<?php

	bp_nouveau_group_hook( 'after', 'invites_content' );

} else if ( ! bp_group_has_requested_membership() ) : ?>
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
