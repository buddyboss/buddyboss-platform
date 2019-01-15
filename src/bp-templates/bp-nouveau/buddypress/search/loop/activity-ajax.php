<div class="bboss_ajax_search_item bboss_ajax_search_item_activity">
	<a href='<?php echo esc_url(add_query_arg( array( 'no_frame' => '1' ), bp_activity_thread_permalink() )); ?>'>
		<div class="item-avatar">
			<?php bp_activity_avatar( array( 'type'=>'thumb', 'height'=>50, 'width'=>50 ) ); ?>
		</div>

		<div class="item">
			<div class="item-title">
				<?php echo wp_strip_all_tags( bp_get_activity_action() ); ?>
			</div>

			<?php if ( bp_activity_has_content() ) : ?>
				<div class="item-desc">
					<?php echo buddyboss_global_search_activity_intro( 30 ); ?>
				</div>
			<?php endif; ?>
		</div>
	</a>
</div>