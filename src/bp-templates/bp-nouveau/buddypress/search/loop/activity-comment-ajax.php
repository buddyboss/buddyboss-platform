<div class="bp-search-ajax-item bp-search-ajax-item_activity_comment">
	<a href='<?php echo esc_url(add_query_arg( array( 'no_frame' => '1' ), bp_activity_thread_permalink() )); ?>'>
		<div class="item-avatar">
			<?php bp_activity_avatar( array( 'type'=>'thumb', 'height'=>50, 'width'=>50 ) ); ?>
		</div>

		<div class="item">
			<?php if ( bp_activity_has_content() ) : ?>
				<div class="item-title">
					<?php echo bp_search_activity_intro( 30 ); ?>
				</div>
			<?php endif; ?>
			<div class="item-meta activity-header">
				<strong class="activity-user">
					<?php echo bp_core_get_user_displayname( bp_get_activity_user_id() ) ?>
				</strong>
				<span class="middot">&middot;</span>
				<time>
					<?php echo human_time_diff( bp_nouveau_get_activity_timestamp() ) . ' ago' ?>
				</time>
			</div>
		</div>
	</a>
</div>
