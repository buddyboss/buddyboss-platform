<?php
/**
 * ReadyLaunch - The template for Activity Widget.
 *
 * This template handles the display of activities in widget format
 * with user avatars, actions, and activity information.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( bp_has_activities( bp_nouveau_activity_widget_query() ) ) : ?>
	<div class="bb-rl-activity-list bb-rl-item-list">
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
