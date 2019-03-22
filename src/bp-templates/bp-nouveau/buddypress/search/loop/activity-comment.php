<li class="bp-search-item bp-search-item_activity_comment">
	<div class="list-wrap">
		<div class="activity-avatar item-avatar">
			<a href="<?php bp_activity_user_link(); ?>">
				<?php bp_activity_avatar( array( 'type' => 'full' ) ); ?>
			</a>
		</div>

		<div class="item activity-content">
			<div class="activity-header">
				<a href="<?php bp_activity_user_link(); ?>"><?php echo bp_core_get_user_displayname( bp_get_activity_user_id() ) ?></a>
			</div>
			<?php if ( bp_nouveau_activity_has_content() ) : ?>
				<div class="activity-inner">
					<a href="<?php echo bp_activity_get_permalink( bp_get_activity_id() ) ?>"><?php bp_nouveau_activity_content(); ?></a>
				</div>
			<?php endif; ?>
			<div class="item-meta">
				<time><?php echo human_time_diff( bp_nouveau_get_activity_timestamp() ) . '&nbsp;' . esc_html__( 'ago', 'buddyboss' ) ?></time>
			</div>
		</div>
	</div>
</li>
