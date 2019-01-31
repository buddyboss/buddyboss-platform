<li class="bboss_search_item bboss_search_item_activity <?php bp_activity_css_class(); ?>" id="activity-<?php bp_activity_id(); ?>" data-bp-activity-id="<?php bp_activity_id(); ?>" data-bp-timestamp="<?php bp_nouveau_activity_timestamp(); ?>">

	<div class="activity-avatar item-avatar">

		<a href="<?php bp_activity_user_link(); ?>">

			<?php bp_activity_avatar( array( 'type' => 'full' ) ); ?>

		</a>

	</div>

	<div class="activity-content">

		<?php if ( bp_nouveau_activity_has_content() ) : ?>

			<div class="activity-inner">

				<?php bp_nouveau_activity_content(); ?>

			</div>

		<?php endif; ?>

		<div class="activity-header">
			<?php bp_activity_action(); ?>
		</div>

	</div>

</li>
