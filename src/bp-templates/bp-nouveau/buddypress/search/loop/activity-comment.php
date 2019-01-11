<li class="bboss_search_item bboss_search_item_activity_comment">
	
	<div class="item-avatar activity-avatar">
		<a href="<?php bp_activity_user_link(); ?>">
			<?php bp_activity_avatar(); ?>
		</a>
	</div>

	<div class="item activity-content">
		<div class="item-meta activity-header">
			<?php bp_activity_action(); ?>
		</div>

		<div class="item-desc">
			<?php if ( bp_activity_has_content() ) : ?>
				<div class="activity-inner">
					<?php echo buddyboss_global_search_activity_intro(); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	
</li>