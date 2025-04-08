<?php
/**
 * The template for BP Nouveau Search & filters bar
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/search-and-filters-bar.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

?>
<div class="bb-subnav-filters-container bb-subnav-filters-search">
	<?php
	if ( ( bp_is_activity_directory() || bp_is_user_activity() ) && bb_is_activity_search_enabled() ) {
		?>
		<button class="subnav-filters-opener" aria-expanded="false" aria-controls="subnav-filters">
			<i class="bb-icon-f bb-icon-search"></i>	
		</button>
		<?php
	}
	?>
	<div class="subnav-filters filters no-ajax subnav-filters-modal" id="subnav-filters">
		<?php
		$bp_current_component = bp_current_component();
		if (
			'friends' !== $bp_current_component &&
			(
				'members' !== $bp_current_component ||
				bp_disable_advanced_profile_search()
			)
		) {
			?>
			<div class="subnav-search clearfix">
				<?php bp_nouveau_search_form(); ?>
			</div>
			<?php
		}

		if (
			(
				'members' === $bp_current_component ||
				'groups' === $bp_current_component ||
				'friends' === $bp_current_component
			) &&
			! bp_is_current_action( 'requests' )
		) {
			bp_get_template_part( 'common/filters/grid-filters' );
		}

		if (
			(
				'members' === $bp_current_component ||
				'groups' === $bp_current_component ) ||
				(
					bp_is_user() &&
					(
						! bp_is_current_action( 'requests' ) &&
						! bp_is_current_action( 'mutual' )
					)
				)
		) {
			bp_get_template_part( 'common/filters/directory-filters' );
		}

		if (
			'members' === $bp_current_component ||
			(
				'friends' === $bp_current_component &&
				'my-friends' === bp_current_action()
			)
		) {
			bp_get_template_part( 'common/filters/member-filters' );
		}

		if ( 'groups' === $bp_current_component ) {
			bp_get_template_part( 'common/filters/group-filters' );
		}
		?>
	</div><!-- search & filters -->
</div>
<?php
if ( bp_is_activity_directory() || bp_is_user_activity() ) {

	// Timeline filters.
	if ( bp_is_user_activity() ) {
		$activity_filters = bb_get_enabled_activity_timeline_filter_options();
		$filters_labels   = bb_get_activity_timeline_filter_options_labels();
	} else {
		$activity_filters = bb_get_enabled_activity_filter_options();
		$filters_labels   = bb_get_activity_filter_options_labels();
	}

	// Allow valid options only.
	$activity_filters = bb_filter_activity_filter_scope_keys( $activity_filters );

	arsort( $activity_filters );
	$default_selected = key( $activity_filters );

	$activity_filters_list_html = '';
	$activity_filters_count     = 0;
	ob_start();

	// List items for the filters.
	if ( ! empty( $activity_filters ) ) {
		foreach ( $activity_filters as $key => $is_enabled ) {

			// Skip filters not enabled or without labels.
			if (
				empty( $is_enabled ) ||
				empty( $filters_labels[ $key ] ) ||
				( bp_is_activity_directory() && 'all' !== $key && ! is_user_logged_in() )
			) {
				continue;
			}

			?>
				<li class="<?php echo ( $key === $default_selected ) ? 'selected' : ''; ?>" role="option" data-bp-scope="<?php echo esc_attr( $key ); ?>" data-bp-object="activity"><a href="#"><?php echo esc_html( $filters_labels[ $key ] ); ?></a></li>
			<?php
			$activity_filters_count++;
		}

		unset( $activity_filters );
	}
	$activity_filters_list_html = ob_get_clean();
	$labels_only_class          = '';
	if ( 1 === $activity_filters_count ) {
		$labels_only_class = 'bb-subnav-filters-labels-only';
	}
	?>
	<i class="bb-icon-f bb-icon-loader animate-spin"></i>
	<div class="bb-subnav-filters-container-main">
		<span class="bb-subnav-filters-label"><?php echo esc_html_e( 'Show', 'buddyboss' ); ?></span>
		<div class="bb-subnav-filters-container bb-subnav-filters-filtering <?php echo esc_attr( $labels_only_class ); ?>">

			<button class="subnav-filters-opener" aria-expanded="false" aria-controls="bb-subnav-filter-show">
				<span class="selected">
					<?php
					$default_filter_label = $filters_labels[ $default_selected ];
					if ( ! preg_match( '/^(I\'ve|I\'m)/i', $default_filter_label ) ) {
						$default_filter_label = strtolower( $default_filter_label );
					}
					echo esc_html( $default_filter_label );
					unset( $default_filter_label );
					?>
				</span>
				<i class="bb-icon-l bb-icon-angle-down"></i>
			</button>
			<div id="bb-subnav-filter-show" class="subnav-filters-modal">
				<ul role="listbox">
					<?php
						echo $activity_filters_list_html; // phpcs:ignore
					?>
				</ul>
			</div>
		</div>
	</div>

	<?php
	unset( $activity_filters_list_html, $activity_filters_count, $labels_only_class );
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
	<div class='<?php echo esc_attr( $hide_class ); ?> bb-subnav-filters-container-main'>
		<span class="bb-subnav-filters-label"><?php echo esc_html_e( 'by', 'buddyboss' ); ?></span>
		<div class="bb-subnav-filters-container bb-subnav-filters-filtering">
			<?php $sorting_labels = bb_get_activity_sorting_options_labels(); ?>
			<button class="subnav-filters-opener" aria-expanded="false" aria-controls="bb-subnav-filter-by">
				<span class="selected"><?php echo esc_html( strtolower( $sorting_labels[ $default_selected ] ) ); ?></span>
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
	<?php
}
?>
