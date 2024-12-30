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
	if ( bp_is_activity_directory() && bb_is_activity_search_enabled() ) {
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
<?php if ( bp_is_activity_directory() ) { ?>
	<div class="bb-subnav-filters-container-main">
		<i class="bb-icon-f bb-icon-loader animate-spin"></i>
		<span class="bb-subnav-filters-label"><?php echo esc_html_e( 'Show', 'buddyboss' ); ?></span>
		<div class="bb-subnav-filters-container bb-subnav-filters-filtering">

			<button class="subnav-filters-opener" aria-expanded="false" aria-controls="bb-subnav-filter-show">
				<span class="selected"><?php echo strtolower( esc_html__( 'All updates', 'buddyboss' ) ); ?></span>
				<i class="bb-icon-l bb-icon-angle-down"></i>
			</button>
			<div id="bb-subnav-filter-show" class="subnav-filters-modal">
				<ul role="listbox">
					<?php
						$filters_labels   = bb_get_activity_filter_options_labels();
						$activity_filters = bb_get_enabled_activity_filter_options();
						if ( ! empty ( $activity_filters ) ) {
							// Skip filters based on user login or component active.
							$skip_conditions = [
								'friends'   => ! bp_is_active( 'friends' ),
								'following' => ! bp_is_activity_follow_active(),
								'groups'    => ! bp_is_active( 'groups' ),
								'mentions'  => ! bp_activity_do_mentions(),
							];
							foreach( $activity_filters as $key => $is_enabled ) {

								// Skip filters not enabled or without labels.
								if ( empty( $is_enabled ) || empty( $filters_labels[ $key ] ) ) {
									continue;
								}

								if ( 'all' !== $key && ! is_user_logged_in() ) {
									continue;
								}
						
								if ( isset( $skip_conditions[ $key ] ) && $skip_conditions[ $key ] ) {
									continue;
								}
								?>
								<li role="option" data-bp-scope="<?php esc_attr_e( $key ); ?>" data-bp-object="activity"><a href="#"><?php echo $filters_labels[ $key ]; ?></a></li>
								<?php
							}
						}
					?>
				</ul>
			</div>
		</div>
	</div>

	<?php
	$avail_sorting_options = bb_get_enabled_activity_sorting_options();
	arsort( $avail_sorting_options );
	if ( ! empty ( $avail_sorting_options ) && in_array( 1, $avail_sorting_options, false ) && array_count_values( $avail_sorting_options )[1] > 1 ) {
		$hide_class = '';
	} else {
		$hide_class = 'bp-hide';
		if ( empty ( $avail_sorting_options ) || ! in_array( 1, $avail_sorting_options, false ) ) {
			$avail_sorting_options = array( 'date_recorded' => 1 );
		}
	}
	?>
	<div class='<?php echo esc_attr( $hide_class ); ?> bb-subnav-filters-container-main'>
		<span class="bb-subnav-filters-label"><?php echo esc_html_e( 'by', 'buddyboss' ); ?></span>
		<div class="bb-subnav-filters-container bb-subnav-filters-filtering">
			<?php $sorting_labels = bb_get_activity_sorting_options_labels(); ?>
			<button class="subnav-filters-opener" aria-expanded="false" aria-controls="bb-subnav-filter-by">
				<span class="selected"><?php echo strtolower( $sorting_labels[ key( $avail_sorting_options ) ] ); ?></span>
				<i class="bb-icon-l bb-icon-angle-down"></i>
			</button>

			<div class="subnav-filters-modal" id="bb-subnav-filter-by">
				<ul role="listbox">
					<?php
					if ( ! empty ( $avail_sorting_options ) ) {
						foreach( $avail_sorting_options as $key => $is_enabled ) {
							if ( empty( $is_enabled ) || empty( $sorting_labels[ $key ] ) ) {
								continue;
							}
							?>
							<li role="option" data-bp-order="activity" data-bp-orderby="<?php esc_attr_e( $key ); ?>"><a href="#"><?php echo $sorting_labels[ $key ]; ?></a></li>
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
