<li <?php bp_group_class( array( 'item-entry bp-search-item bp-search-item_group' ) ); ?> data-bp-item-id="<?php bp_group_id(); ?>" data-bp-item-component="groups">
	<div class="list-wrap">
		<?php if ( ! bp_disable_group_avatar_uploads() ) : ?>
			<div class="item-avatar">
				<a href="<?php bp_group_permalink(); ?>"><?php bp_group_avatar( bp_nouveau_avatar_args() ); ?></a>
			</div>
		<?php endif; ?>

		<div class="item">
			<h2 class="item-title groups-title"><?php bp_group_link(); ?></h2>
			<?php if ( bp_nouveau_group_has_meta() ) : ?>
				<p class="item-meta group-details"><?php bp_nouveau_group_meta(); ?></p>
			<?php endif; ?>
		</div>
	</div>
</li>
