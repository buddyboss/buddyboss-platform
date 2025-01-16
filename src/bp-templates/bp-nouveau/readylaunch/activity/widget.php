<?php
/**
 * The template for ReadyLaunch Activity Widget template.
 *
 * @since   BuddyBoss [BBVERSION]
 *
 * @package ReadyLaunch
 */

if ( bp_has_activities( bp_nouveau_activity_widget_query() ) ) : ?>
	<div class="activity-list item-list">
		<?php
		while ( bp_activities() ) :
			bp_the_activity();
			?>
			<div class="activity-update">
				<div class="update-item">
					<cite>
						<a href="<?php bp_activity_user_link(); ?>" class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php echo esc_attr( bp_get_activity_member_display_name() ); ?>">
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
