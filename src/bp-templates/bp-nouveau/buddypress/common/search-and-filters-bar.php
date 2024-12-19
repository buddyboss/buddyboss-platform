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
	<button class="subnav-filters-opener" aria-expanded="false" aria-controls="subnav-filters">
		<i class="bb-icon-f bb-icon-search"></i>	
	</button>
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

<span class="bb-subnav-filters-label"><?php echo esc_html_e( 'Show', 'buddyboss' ); ?></span>
<div class="bb-subnav-filters-container bb-subnav-filters-filtering">

	<button class="subnav-filters-opener" aria-expanded="false" aria-controls="bb-subnav-filter-show">
		<span class="selected"><?php echo strtolower( esc_html__( 'All updates', 'buddyboss' ) ); ?></span>
		<i class="bb-icon-l bb-icon-angle-down"></i>
	</button>
	<div id="bb-subnav-filter-show" class="subnav-filters-modal">
		<ul role="listbox">
			<li role="option"><a href="#" data-value="<?php esc_attr_e( $key ); ?>" class="selected"><?php esc_html_e( 'All updates', 'buddyboss' ); ?></a></li>
			<?php
				$activity_filters = bb_get_all_activity_filter_options();
				$avail_filters    = bb_get_enabled_activity_filter_options();
				if ( ! empty ( $avail_filters ) ) {
					foreach( $avail_filters as $key ) {
						$label = $activity_filters[ $key ];
						?>
						<li role="option" data-bp-scope="<?php esc_attr_e( $key ); ?>" data-bp-object="activity"><a href="#" data-value="<?php esc_attr_e( $key ); ?>"><?php echo $label; ?></a></li>
						<?php
					}
				}
			?>
		</ul>
	</div>
	<input type="hidden" name="bb_activity_filter_show" value="all" />
</div>

<?php
$avail_sorting_options = bb_get_enabled_activity_sorting_options();
if ( ! empty ( $avail_sorting_options ) && count( $avail_sorting_options ) > 1 ) {
	?>
	<span class="bb-subnav-filters-label"><?php echo esc_html_e( 'by', 'buddyboss' ); ?></span>
	<div class="bb-subnav-filters-container bb-subnav-filters-filtering">
		<?php $sorting_options = bb_get_all_activity_sorting_options(); ?>
		<button class="subnav-filters-opener" aria-expanded="false" aria-controls="bb-subnav-filter-by">
			<span class="selected"><?php echo strtolower( $sorting_options[ $avail_sorting_options[0] ] ); ?></span>
			<i class="bb-icon-l bb-icon-angle-down"></i>
		</button>

		<div class="subnav-filters-modal" id="bb-subnav-filter-by">
			<ul role="listbox">
				<?php
				if ( ! empty ( $avail_sorting_options ) ) {
					foreach( $avail_sorting_options as $key ) {
						$label = $sorting_options[ $key ];
						?>
						<li role="option" selected='selected'><a href="#" data-value="<?php esc_attr_e( $key ); ?>"><?php echo $label; ?></a></li>
						<?php
					}
				}
				?>
			</ul>
		</div>
		<input type="hidden" name="bb_activity_filter_by" value="<?php esc_attr_e( $avail_sorting_options[0] ); ?>" />
	</div>
	<?php
}
?>
