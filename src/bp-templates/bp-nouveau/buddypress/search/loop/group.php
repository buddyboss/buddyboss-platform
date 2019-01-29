<li <?php bp_group_class( array( 'item-entry bboss_search_item bboss_search_item_group' ) ); ?> data-bp-item-id="<?php bp_group_id(); ?>" data-bp-item-component="groups">
	<div class="list-wrap">

		<?php if ( ! bp_disable_group_avatar_uploads() ) : ?>
			<div class="item-avatar">
				<a href="<?php bp_group_permalink(); ?>"><?php bp_group_avatar( bp_nouveau_avatar_args() ); ?></a>
			</div>
		<?php endif; ?>

		<div class="item">

			<div class="item-block">

				<h2 class="list-title groups-title"><?php bp_group_link(); ?></h2>

				<p class="last-activity item-meta">
					<?php
					printf(
					/* translators: %s = last activity timestamp (e.g. "active 1 hour ago") */
						__( 'active %s', 'buddypress' ),
						bp_get_group_last_active()
					);
					?>
				</p>

			</div>

		</div>

	</div>
</li>
