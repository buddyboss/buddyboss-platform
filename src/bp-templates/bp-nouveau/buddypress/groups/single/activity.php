<?php
/**
 * BuddyBoss - Groups Activity
 *
 * This template is used to show group activity.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/activity.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

$is_send_ajax_request = bb_is_send_ajax_request();
?>
<h2 class="bp-screen-title<?php echo ( ! bp_is_group_home() ) ? ' bp-screen-reader-text' : ''; ?>">
	<?php esc_html_e( 'Group Feed', 'buddyboss' ); ?>
</h2>

<?php bp_nouveau_groups_activity_post_form(); ?>

<div class="activity-head-bar">

	<div class="bb-subnav-filters-container bb-subnav-filters-search">
		<button class="subnav-filters-opener" aria-expanded="false" aria-controls="subnav-filters">
			<i class="bb-icon-f bb-icon-search"></i>
		</button>
		<div class="subnav-filters filters clearfix subnav-filters-modal">
			<ul>
				<li class="group-act-search"><?php bp_nouveau_search_form(); ?></li>
			</ul>
			<?php bp_get_template_part( 'common/filters/groups-screens-filters' ); ?>
		</div><!-- .subnav-filters -->
	</div><!-- .bb-subnav-filters-container -->

	<?php
	$avail_sorting_options = bb_get_enabled_activity_sorting_options();
	arsort( $avail_sorting_options );
	$default_selected = key( $avail_sorting_options );
	if ( ! empty( $avail_sorting_options ) && in_array( 1, $avail_sorting_options, true ) && array_count_values( $avail_sorting_options )[1] > 1 ) {
		$hide_class = '';
	} else {
		$hide_class = 'bp-hide';
		if ( empty( $avail_sorting_options ) || ! in_array( 1, $avail_sorting_options, true ) ) {
			$avail_sorting_options = array( 'date_recorded' => 1 );
		}
	}
	?>
	<i class="bb-icon-f bb-icon-loader animate-spin"></i>
	<div class='<?php echo esc_attr( $hide_class ); ?> bb-subnav-filters-container-main'>
		<span class="bb-subnav-filters-label"><?php echo esc_html_e( 'Show by', 'buddyboss' ); ?></span>
		<div class="bb-subnav-filters-container bb-subnav-filters-filtering">
			<?php $sorting_labels = bb_get_activity_sorting_options_labels(); ?>
			<button class="subnav-filters-opener" aria-expanded="false" aria-controls="bb-subnav-filter-by">
				<span class="selected"><?php echo strtolower( esc_html( $sorting_labels[ $default_selected ] ) ); ?></span>
				<i class="bb-icon-l bb-icon-angle-down"></i>
			</button>

			<div class="subnav-filters-modal" id="bb-subnav-filter-by">
				<ul role="listbox">
					<?php
					if ( ! empty( $avail_sorting_options ) ) {
						foreach ( $avail_sorting_options as $key => $is_enabled ) {
							if ( empty( $is_enabled ) || empty( $sorting_labels[ $key ] ) ) {
								continue;
							}
							?>
							<li class="<?php echo ( $key === $default_selected ) ? 'selected' : ''; ?>" role="option" data-bp-order="activity" data-bp-orderby="<?php echo esc_attr( $key ); ?>"><a href="#"><?php echo esc_html( $sorting_labels[ $key ] ); ?></a></li>
							<?php
						}
					}
					?>
				</ul>
			</div>
		</div>
	</div>
</div>

<?php bp_nouveau_group_hook( 'before', 'activity_content' ); ?>

<div id="activity-stream" class="activity single-group" data-bp-list="activity" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
	<?php
	if ( $is_send_ajax_request ) {
		echo '<li id="bp-activity-ajax-loader">';
		?>
		<div class="bb-activity-placeholder">
			<div class="bb-activity-placeholder_head">
				<div class="bb-activity-placeholder_avatar bb-bg-animation bb-loading-bg"></div>
				<div class="bb-activity-placeholder_details">
					<div class="bb-activity-placeholder_title bb-bg-animation bb-loading-bg"></div>
					<div class="bb-activity-placeholder_description bb-bg-animation bb-loading-bg"></div>
				</div>
			</div>
			<div class="bb-activity-placeholder_content">
				<div class="bb-activity-placeholder_title bb-bg-animation bb-loading-bg"></div>
				<div class="bb-activity-placeholder_title bb-bg-animation bb-loading-bg"></div>
			</div>
			<div class="bb-activity-placeholder_actions">
				<div class="bb-activity-placeholder_description bb-bg-animation bb-loading-bg"></div>
				<div class="bb-activity-placeholder_description bb-bg-animation bb-loading-bg"></div>
				<div class="bb-activity-placeholder_description bb-bg-animation bb-loading-bg"></div>
			</div>
		</div>
		<div class="bb-activity-placeholder">
			<div class="bb-activity-placeholder_head">
				<div class="bb-activity-placeholder_avatar bb-bg-animation bb-loading-bg"></div>
				<div class="bb-activity-placeholder_details">
					<div class="bb-activity-placeholder_title bb-bg-animation bb-loading-bg"></div>
					<div class="bb-activity-placeholder_description bb-bg-animation bb-loading-bg"></div>
				</div>
			</div>
			<div class="bb-activity-placeholder_content">
				<div class="bb-activity-placeholder_title bb-bg-animation bb-loading-bg"></div>
				<div class="bb-activity-placeholder_title bb-bg-animation bb-loading-bg"></div>
			</div>
			<div class="bb-activity-placeholder_actions">
				<div class="bb-activity-placeholder_description bb-bg-animation bb-loading-bg"></div>
				<div class="bb-activity-placeholder_description bb-bg-animation bb-loading-bg"></div>
				<div class="bb-activity-placeholder_description bb-bg-animation bb-loading-bg"></div>
			</div>
		</div>
		<?php
		echo '</li>';
	} else {
		bp_get_template_part( 'activity/activity-loop' );
	}
	?>
</div><!-- .activity -->

<?php
bp_nouveau_group_hook( 'after', 'activity_content' );
bp_get_template_part( 'common/js-templates/activity/comments' );
