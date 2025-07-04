<?php
/**
 * The template for BP Nouveau Activity Widget template.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/activity/widget.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

if ( bp_has_activities( bp_nouveau_activity_widget_query() ) ) : ?>
	<div class="activity-list item-list">
		<?php
		while ( bp_activities() ) :
			bp_the_activity();
			$activity_user_id           = bp_get_activity_user_id();
			$activity_user_display_name = bp_get_activity_member_display_name();
			?>
			<div class="activity-update">

				<div class="update-item">
					<cite>
						<a href="<?php bp_activity_user_link(); ?>" class="bp-tooltip" data-bp-tooltip-pos="up" <?php echo ! empty( $activity_user_display_name ) ? 'data-bp-tooltip="' . esc_attr( $activity_user_display_name ) . '"' : ''; ?> <?php echo ! empty( $activity_user_id ) ? 'data-bb-hp-profile="' . esc_attr( $activity_user_id ) . '"' : ''; ?>">
							<?php
							bp_activity_avatar(
								array(
									'type'   => 'thumb',
									'width'  => '40',
									'height' => '40',
								)
							);
							?>
						</a>
					</cite>

					<div class="bp-activity-info">
						<?php bp_activity_action(); ?>
					</div>
				</div>

			</div>
		<?php endwhile; ?>
	</div>
<?php else : ?>
	<div class="widget-error">
		<?php bp_nouveau_user_feedback( 'activity-loop-none' ); ?>
	</div>
<?php endif; ?>
