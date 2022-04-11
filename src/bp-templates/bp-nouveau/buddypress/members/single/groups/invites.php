<?php
/**
 * The template for members single group invites
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/groups/invites.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */
?>

<h2 class="screen-heading group-invites-screen"><?php esc_html_e( 'Group Invites', 'buddyboss' ); ?></h2>

<?php bp_nouveau_group_hook( 'before', 'invites_content' ); ?>

<?php if ( bp_has_groups( 'type=invites&user_id=' . bp_displayed_user_id() ) ) : ?>

	<ul id="group-list" class="invites item-list bp-list" data-bp-list="groups_invites">

		<?php
		while ( bp_groups() ) :
			bp_the_group();
		?>

			<li class="item-entry invites-list" data-bp-item-id="<?php bp_group_id(); ?>" data-bp-item-component="groups">

				<div class="wrap">

				<?php if ( ! bp_disable_group_avatar_uploads() ) : ?>
					<div class="item-avatar">
						<a href="<?php bp_group_permalink(); ?>"><?php bp_group_avatar(); ?></a>
					</div>
				<?php endif; ?>

					<div class="item">
						<h2 class="list-title groups-title"><?php bp_group_link(); ?></h2>
						<p class="meta group-details">
                            <?php $inviter = bp_groups_get_invited_by(); ?>
                            <?php if ( ! empty( $inviter ) ) : ?>
                                <span class="small">
                                <?php
								printf(
										__( 'Invited by %1$s &middot; %2$s.', 'buddyboss' ),
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
                                </span>
                            <?php endif; ?>
						</p>

						<p class="desc">
							<?php echo bp_groups_get_invite_messsage_for_user( bp_displayed_user_id(), bp_get_group_id() ); ?>
						</p>

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

		<?php endwhile; ?>
	</ul>

<?php else : ?>

	<?php bp_nouveau_user_feedback( 'member-invites-none' ); ?>

<?php endif; ?>

<?php
bp_nouveau_group_hook( 'after', 'invites_content' );
