<li class="bp-search-item bp-search-item_activity_comment">
	<div class="list-wrap">
		<div class="activity-avatar item-avatar">
			<a href="<?php bp_activity_user_link(); ?>">
				<?php bp_activity_avatar( array( 'type' => 'full' ) ); ?>
			</a>
		</div>

		<div class="item activity-content">
			<?php if ( bp_nouveau_activity_has_content() ) : ?>
				<h3 class="entry-title item-title activity-content">
					<a href="<?php echo bp_activity_get_permalink( bp_get_activity_id() ) ?>"><?php bp_nouveau_activity_content(); ?></a>
				</h3>
			<?php endif; ?>
			<div class="item-meta activity-header">
				<strong>
					<a href="<?php bp_activity_user_link(); ?>"><?php echo bp_core_get_user_displayname( bp_get_activity_user_id() ) ?></a>
				</strong>
				<span class="middot">&middot;</span>
				<time>
					<?php echo human_time_diff( bp_nouveau_get_activity_timestamp() ) . ' ago' ?>
				</time>
			</div>
		</div>
	</div>
</li>
