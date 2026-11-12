<?php
/**
 * The template for members single group invites
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/groups/invites.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<h2 class="screen-heading group-invites-screen"><?php esc_html_e( 'Group Invites', 'buddyboss' ); ?></h2>
<?php
bboss_nouveau_group_hook( 'before', 'invites_content' );

$bp_displayed_user_id = bboss_displayed_user_id();
if ( bboss_has_groups( 'type=invites&user_id=' . $bp_displayed_user_id ) ) {
	?>
	<ul id="group-list" class="invites item-list bp-list" data-bp-list="groups_invites">
		<?php
		while ( bboss_groups() ) :
			bboss_the_group();
			$bp_get_group_id = bboss_get_group_id();
			?>

			<li class="item-entry invites-list" data-bp-item-id="<?php echo esc_attr( $bp_get_group_id ); ?>" data-bp-item-component="groups">

				<div class="wrap">

					<?php if ( ! bboss_disable_group_avatar_uploads() ) : ?>
						<div class="item-avatar">
							<a href="<?php bboss_group_permalink(); ?>"><?php bboss_group_avatar(); ?></a>
						</div>
					<?php endif; ?>

					<div class="item">
						<h2 class="list-title groups-title"><?php bboss_group_link(); ?></h2>
						<p class="meta group-details">
							<?php
							$inviter = bboss_groups_get_invited_by();
							if ( ! empty( $inviter ) ) : ?>
								<span class="small">
								<?php
								echo wp_kses_post(
									sprintf(
										/* translators: 1: inviter profile link, 2: time since the invitation. */
										__( 'Invited by %1$s &middot; %2$s.', 'buddyboss' ),
										sprintf(
											'<a href="%s">%s</a>',
											esc_url( $inviter['url'] ),
											esc_html( $inviter['name'] )
										),
										sprintf(
											'<span class="last-activity">%s</span>',
											esc_html( bboss_core_time_since( $inviter['date_modified'] ) )
										)
									)
								);
								?>
								</span>
							<?php endif; ?>
						</p>

						<p class="desc">
							<?php echo wp_kses_post( bboss_groups_get_invite_messsage_for_user( $bp_displayed_user_id, $bp_get_group_id ) ); ?>
						</p>
						<?php
						bboss_nouveau_group_hook( '', 'invites_item' );

						bboss_nouveau_groups_invite_buttons(
							array(
								'container'      => 'ul',
								'button_element' => 'button',
							)
						);
						?>
					</div>

				</div>
			</li>

		<?php endwhile; ?>
	</ul>

	<?php
} else {
	bboss_nouveau_user_feedback( 'member-invites-none' );
}

bboss_nouveau_group_hook( 'after', 'invites_content' );
