<?php
/**
 * ReadyLaunch - Member Group Invites template.
 *
 * This template handles displaying group invitations for members.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<div class="bb-rl-container-inner bb-rl-profile-container">
	<div class="groups-directory-container">
		<div class="screen-content groups-directory-content bb-rl-groups">

			<h2 class="screen-heading group-invites-screen bb-screen-reader-text">
				<?php esc_html_e( 'Group Invites', 'buddyboss' ); ?>
			</h2>
			<?php
			bp_nouveau_group_hook( 'before', 'invites_content' );

			$bp_displayed_user_id = bp_displayed_user_id();
			if ( bp_has_groups( 'type=invites&user_id=' . $bp_displayed_user_id ) ) {
				?>
				<ul id="group-list" class="invites item-list bp-list groups-list" data-bp-list="groups_invites">
					<?php
					while ( bp_groups() ) :
						bp_the_group();
						$bp_get_group_id = bp_get_group_id();
						?>

						<li class="item-entry invites-list" data-bp-item-id="<?php echo esc_attr( $bp_get_group_id ); ?>" data-bp-item-component="groups">

							<div class="list-wrap bb-rl-group-block">

								<?php if ( ! bp_disable_group_avatar_uploads() ) : ?>
									<div class="item-avatar">
										<a href="<?php bp_group_permalink(); ?>"><?php bp_group_avatar(); ?></a>
									</div>
								<?php endif; ?>

								<div class="item">
									<div class="group-item-wrap">
										<div class="item-block">
											<h2 class="list-title groups-title"><?php bp_group_link(); ?></h2>
											<p class="item-meta group-details">
												<?php
												$inviter = bp_groups_get_invited_by();
												if ( ! empty( $inviter ) ) :
													?>
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

											<p class="item-meta desc">
												<?php echo bp_groups_get_invite_messsage_for_user( $bp_displayed_user_id, $bp_get_group_id ); ?>
											</p>
										</div>
									</div>
									
									<div class="group-footer-wrap">
										<?php
										bp_nouveau_group_hook( '', 'invites_item' );

										bp_nouveau_groups_invite_buttons(
											array(
												'container'      => 'ul',
												'button_element' => 'button',
											)
										);
										?>
									</div>
								</div>

							</div>
						</li>

					<?php endwhile; ?>
				</ul>

				<?php
			} else {
				bp_nouveau_user_feedback( 'member-invites-none' );
			}

			bp_nouveau_group_hook( 'after', 'invites_content' );
			?>
		</div>
	</div>
</div>