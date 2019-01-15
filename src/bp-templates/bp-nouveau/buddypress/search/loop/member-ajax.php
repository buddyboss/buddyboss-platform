<div class="bboss_ajax_search_item bboss_ajax_search_member">
	<a href="<?php echo esc_url(add_query_arg( array( 'no_frame' => '1' ), bp_get_member_permalink() )); ?>">
		<div class="item-avatar">
			<?php bp_member_avatar( 'type=thumb&width=60&height=60' ); ?>
		</div>

		<div class="item">
			<div class="item-title"><?php bp_member_name(); ?></div>
		</div>
	</a>
</div>