<?php
/**
 * BuddyBoss - Members Mutual Connections
 *
 * @since BuddyBoss 3.1.1
 * @version 3.1.1
 */
?>

<?php bp_nouveau_member_hook( 'before', 'friend_mutual_friends_content' ); ?>

<?php if ( bp_has_members( 'type=alphabetical&include=' . bp_get_mutual_friendships() ) ) : ?>

	<?php bp_nouveau_pagination( 'top' ); ?>

	<ul id="friend-list" class="<?php bp_nouveau_loop_classes(); ?>" data-bp-list="mutual_friendship">
		<?php
		while ( bp_members() ) :
			bp_the_member();
			?>

            <li <?php bp_member_class( array( 'item-entry' ) ); ?> data-bp-item-id="<?php bp_member_user_id(); ?>" data-bp-item-component="members">
                <div class="list-wrap">

                    <div class="item-avatar">
                        <a href="<?php bp_member_permalink(); ?>"><?php bp_member_avatar( bp_nouveau_avatar_args() ); ?></a>
                    </div>

                    <div class="item">

                        <div class="item-block">

                            <h2 class="list-title member-name">
                                <a href="<?php bp_member_permalink(); ?>"><?php bp_member_name(); ?></a>
                            </h2>

							<?php if ( bp_nouveau_member_has_meta() ) : ?>
                                <p class="item-meta last-activity">
									<?php bp_nouveau_member_meta(); ?>
                                </p><!-- #item-meta -->
							<?php endif; ?>

							<?php
							bp_nouveau_members_loop_buttons(
								array(
									'container'      => 'ul',
									'button_element' => 'button',
								)
							);
							?>

                        </div>

                    </div><!-- // .item -->



                </div>
            </li>

		<?php endwhile; ?>
	</ul>

	<?php bp_nouveau_friend_hook( 'mutual_friends_content' ); ?>

	<?php bp_nouveau_pagination( 'bottom' ); ?>

<?php else : ?>

	<?php bp_nouveau_user_feedback( 'member-mutual-friends-none' ); ?>

<?php endif; ?>

<?php
bp_nouveau_member_hook( 'after', 'friend_mutual_friends_content' );
