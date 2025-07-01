<?php
/**
 * ReadyLaunch - Member Notifications Loop template.
 *
 * This template handles displaying member notifications in a loop.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( bp_has_notifications( bp_ajax_querystring( 'notifications' ) ) ) :

	?>

	<form action="" method="post" id="notifications-bulk-management" class="standard-form">
		<ul class="notification-list bb-nouveau-list bs-item-list list-view">
			<?php
			while ( bp_the_notifications() ) :
				bp_the_notification();
				$bp                     = buddypress();
				$bp_the_notification_id = bp_get_the_notification_id();
				$readonly               = isset( $bp->notifications->query_loop->notification->readonly ) ? $bp->notifications->query_loop->notification->readonly : false;
				?>
				<li class="bs-item-wrap">
					<div class="bulk-select-check">
						<span class="bb-input-wrap">
							<input id="<?php echo esc_attr( $bp_the_notification_id ); ?>" type="checkbox" name="notifications[]" value="<?php echo esc_attr( $bp_the_notification_id ); ?>" class="notification-check bs-styled-checkbox" data-readonly="<?php echo esc_attr( $readonly ); ?>"/>
							<label for="<?php echo esc_attr( $bp_the_notification_id ); ?>"></label>
						</span>
					</div>
					<div class="notification-avatar">
						<?php bb_notification_avatar(); ?>
					</div>

					<div class="notification-content">
						<span><?php bp_the_notification_description(); ?></span>
						<span class="posted"><?php bp_the_notification_time_since(); ?></span>
					</div>

					<div class="actions">
						<div class="bb-rl-more_dropdown-wrap">
							<a href="#" class="bb_rl_more_dropdown__action" data-balloon-pos="up" data-balloon="More actions">
								<i class="bb-icons-rl-dots-three"></i>
							</a>
							<div class="bb_rl_more_dropdown">
								<?php bp_the_notification_action_links(); ?>
							</div>
						</div>
					</div>
				</li>
			<?php endwhile; ?>
		</ul>
	</form>

	<?php
	bp_nouveau_pagination( 'bottom' );
else :
	bp_nouveau_user_feedback( 'member-notifications-none' );
endif;
