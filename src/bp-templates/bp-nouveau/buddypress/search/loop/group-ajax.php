<div class="bboss_ajax_search_item bboss_ajax_search_group">
	<a href="<?php echo esc_url(add_query_arg( array( 'no_frame' => '1' ), bp_get_group_permalink() )); ?>">
		<div class="item-avatar">
			<?php bp_group_avatar( 'type=thumb&width=50&height=50' ); ?>
		</div>

		<div class="item">
			<div class="item-title"><?php bp_group_name(); ?></div>
			<?php if ( bp_nouveau_group_has_meta() ) : ?>
				<p class="item-meta group-details"><?php bp_nouveau_group_meta(); ?></p>
			<?php endif; ?>
		</div>
	</a>
</div>
