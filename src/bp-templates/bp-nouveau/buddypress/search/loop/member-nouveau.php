<li <?php bp_member_class( array( 'item-entry', 'bboss_search_item' ) ); ?> data-bp-item-id="<?php bp_member_user_id(); ?>" data-bp-item-component="members">
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

				<div class="members-meta action">
					<?php
					bp_nouveau_members_loop_buttons(
						array(
							'container'      => 'ul',
							'button_element' => 'button',
						)
					);
					?>
				</div>

			</div>

			<?php if ( bp_get_member_latest_update() && ! bp_nouveau_loop_is_grid() ) : ?>
				<div class="user-update">
					<p class="update"> <?php bp_member_latest_update(); ?></p>
				</div>
			<?php endif; ?>

		</div><!-- // .item -->



	</div>
</li>
