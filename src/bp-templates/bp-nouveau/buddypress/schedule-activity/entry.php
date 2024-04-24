<?php
/**
 * The template for BuddyBoss - schedule Activity Feed.
 *
 * This template is used by activity-loop.php and AJAX functions to show
 * each activity.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/schedule-activity/entry.php.
 *
 * @since   BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

bp_nouveau_activity_hook( 'before', 'schedule_entry' );

$activity_id    = bp_get_activity_id();
$activity_metas = bb_activity_get_metadata( $activity_id );

$link_preview_string = '';
$link_url            = '';

$link_preview_data = ! empty( $activity_metas['_link_preview_data'][0] ) ? maybe_unserialize( $activity_metas['_link_preview_data'][0] ) : array();
if ( ! empty( $link_preview_data ) && count( $link_preview_data ) ) {
	$link_preview_string = wp_json_encode( $link_preview_data );
	$link_url            = ! empty( $link_preview_data['url'] ) ? $link_preview_data['url'] : '';
}

$link_embed = $activity_metas['_link_embed'][0] ?? '';
if ( ! empty( $link_embed ) ) {
	$link_url = $link_embed;
}

$activity_date_recorded = bp_get_activity_date_recorded();

// Convert GMT time to local time based on WordPress settings.
$local_time_wp = get_date_from_gmt( $activity_date_recorded );

// Get the date and time formats set in WordPress settings.
$date_format = get_option( 'date_format' );
$time_format = get_option( 'time_format' );

// Format the local time according to the WordPress settings.
$formatted_local_time_wp = trim( date_i18n( $date_format . ' \a\t ' . $time_format, strtotime( $local_time_wp ) ), ' at ' ) ;

$scheduled_date_data['local_date_time'] = $formatted_local_time_wp;
$scheduled_date_data['date_raw']        = get_date_from_gmt( $activity_date_recorded, 'Y-m-d' );
$scheduled_date_data['date']            = get_date_from_gmt( $activity_date_recorded, $date_format );
$scheduled_date_data['time']            = get_date_from_gmt( $activity_date_recorded, 'g:i' );
$scheduled_date_data['meridiem']        = get_date_from_gmt( $activity_date_recorded, 'a' );
$scheduled_date_string                  = wp_json_encode( $scheduled_date_data );

?>

<li
	class="<?php bp_activity_css_class(); ?>"
	id="activity-<?php echo esc_attr( $activity_id ); ?>"
	data-bp-activity-id="<?php echo esc_attr( $activity_id ); ?>"
	data-bp-timestamp="<?php bp_nouveau_activity_timestamp(); ?>"
	data-bp-activity="<?php ( function_exists( 'bp_nouveau_edit_activity_data' ) ) ? bp_nouveau_edit_activity_data() : ''; ?>"
	data-link-preview='<?php echo $link_preview_string; ?>'
	data-link-url='<?php echo esc_url( $link_url ); ?>'
	data-bb-scheduled-time='<?php echo esc_attr( $scheduled_date_string ); ?>'>

	<div class="bb-activity-schedule-actions">
		<a href="#" class="bb-activity-schedule-action bb-activity-schedule_edit">
			<i class="bb-icon-l bb-icon-pencil"></i>
		</a>
		<a href="#" class="bb-activity-schedule-action bb-activity-schedule_delete">
			<i class="bb-icon-l bb-icon-trash"></i>
		</a>
	</div>

	<div class="bp-activity-head">
		<div class="activity-avatar item-avatar">
			<a href="<?php bp_activity_user_link(); ?>"><?php bp_activity_avatar( array( 'type' => 'full' ) ); ?></a>
		</div>

		<div class="activity-header">
			<?php bp_activity_action(); ?>
			<p class="activity-date">
				<span class="schedule-text"><?php esc_html_e( 'Schedule for:', 'buddyboss' ) ?></span>
				<a href="javascript: void(0);">
					<?php
					printf(
						'<span class="time-since">%1$s</span>',
						$formatted_local_time_wp,
					);
					?>
				</a>
				<?php
				if ( function_exists( 'bp_nouveau_activity_is_edited' ) ) {
					bp_nouveau_activity_is_edited();
				}
				?>
			</p>
			<?php
			if ( function_exists( 'bp_nouveau_activity_privacy' ) ) {
				bp_nouveau_activity_privacy();
			}
			?>

		</div>
	</div>

	<?php bp_nouveau_activity_hook( 'before', 'activity_content' ); ?>

	<div class="activity-content <?php ( function_exists( 'bp_activity_entry_css_class' ) ) ? bp_activity_entry_css_class() : ''; ?>">
		<?php
		if ( bp_nouveau_activity_has_content() ) : ?>
			<div class="activity-inner <?php echo ( function_exists( 'bp_activity_has_content' ) && empty( bp_activity_has_content() ) ) ? esc_attr( 'bb-empty-content' ) : esc_attr( '' ); ?>">
				<?php
					bp_nouveau_activity_content();

				if ( function_exists( 'bb_nouveau_activity_inner_buttons' ) ) {
					bb_nouveau_activity_inner_buttons();
				}
				?>
			</div>
			<?php
		endif;
		?>
	</div>

	<?php
	bp_nouveau_activity_hook( 'after', 'activity_content' );
	?>
</li>

<?php
bp_nouveau_activity_hook( 'after', 'schedule_entry' );
