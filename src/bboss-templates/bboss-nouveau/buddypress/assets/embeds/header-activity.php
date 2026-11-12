<?php
/**
 * @version 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div id="bp-embed-header">
	<div class="bp-embed-avatar">
		<a href="<?php bboss_displayed_user_link(); ?>">
			<?php bboss_displayed_user_avatar( 'type=thumb&width=45&height=45' ); ?>
		</a>
	</div>

	<?php
	if ( bboss_activity_embed_has_activity( bboss_current_action() ) ) :
		while ( bboss_activities() ) :
			bboss_the_activity();
			?>
			<p class="bp-embed-activity-action">
				<?php bboss_activity_action( array( 'no_timestamp' => true ) ); ?>
			</p>
			<?php
		endwhile;
	endif;
	?>

	<p class="bp-embed-header-meta">
		<?php if ( bboss_is_active( 'activity' ) && bboss_activity_do_mentions() ) : ?>
			<span class="bp-embed-mentionname">@<?php bboss_displayed_user_mentionname(); ?> &middot; </span>
		<?php endif; ?>

		<span class="bp-embed-timestamp"><a href="<?php bboss_activity_thread_permalink(); ?>"><?php echo esc_html( date_i18n( get_option( 'time_format' ) . ' - ' . get_option( 'date_format' ), strtotime( bboss_get_activity_date_recorded() ) ) ); ?></a></span>
	</p>
</div>
