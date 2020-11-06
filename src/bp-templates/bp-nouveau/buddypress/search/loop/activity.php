<li class="bp-search-item bp-search-item_activity <?php bp_activity_css_class(); ?>" id="activity-<?php bp_activity_id(); ?>" data-bp-activity-id="<?php bp_activity_id(); ?>" data-bp-timestamp="<?php bp_nouveau_activity_timestamp(); ?>">
	<div class="list-wrap">
		<div class="activity-avatar item-avatar">
			<a href="<?php bp_activity_user_link(); ?>">
				<?php bp_activity_avatar( array( 'type' => 'full' ) ); ?>
			</a>
		</div>

		<div class="item activity-content">
			<div class="activity-header">
				<?php echo bp_get_activity_action( [ 'no_timestamp' => true ] ); ?>
			</div>
			<?php if ( bp_nouveau_activity_has_content() ) : ?>
				<div class="activity-inner"><?php echo wp_trim_words( $GLOBALS['activities_template']->activity->content, '20', '...' ) ?></div>
			<?php endif; ?>
			<div class="item-meta">
				<a href="<?php bp_activity_thread_permalink(); ?>">
					<time>
						<?php echo human_time_diff( bp_nouveau_get_activity_timestamp() ) . '&nbsp;' . esc_html__( 'ago', 'buddyboss' ) ?>
					</time>
				</a>
			</div>
		</div>
	</div>
</li>
