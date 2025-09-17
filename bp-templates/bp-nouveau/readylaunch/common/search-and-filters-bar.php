<?php
/**
 * ReadyLaunch - The template for Search & filters bar.
 *
 * This template handles the search functionality and filters for various
 * BuddyPress components including activity, members, groups, and media.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div class="subnav-filters filters no-ajax" id="subnav-filters">
	<?php
	$bp_current_component = bp_current_component();
	if (
		'friends' !== $bp_current_component &&
		(
			'members' !== $bp_current_component ||
			bp_disable_advanced_profile_search()
		) &&
		! bp_is_directory() &&
		! bp_is_user_activity()
	) {
		?>
		<div class="subnav-search clearfix"><?php bp_nouveau_search_form(); ?></div>
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
			(
				'groups' === $bp_current_component &&
				! bp_is_group_single()
			)
		) ||
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

	if (
		'groups' === $bp_current_component &&
		! bp_is_group_single()
	) {
		bp_get_template_part( 'common/filters/group-filters' );
	}

	if (
		'media' === $bp_current_component ||
		'document' === $bp_current_component ||
		'video' === $bp_current_component
	) {
		bp_get_template_part( 'common/filters/common-filters' );
	}
	?>
</div><!-- search & filters -->

<?php
if ( bp_is_activity_directory() || bp_is_user_activity() ) {
	?>
	<div class="activity-head-bar">
		<?php

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
		?>
		<i class="bb-rl-loader"></i>
		<div class="bb-subnav-filters-container-main">
			<span class="bb-subnav-filters-label"><?php echo esc_html_e( 'Show', 'buddyboss' ); ?></span>
			<div class="bb-subnav-filters-container bb-subnav-filters-filtering">

				<button class="subnav-filters-opener" aria-expanded="false" aria-controls="bb-subnav-filter-show">
					<span class="selected">
						<?php
							$default_filter_label = $filters_labels[ $default_selected ];
						if ( ! preg_match( '/^(I\'ve|I\'m)/i', $default_filter_label ) ) {
							echo esc_html( strtolower( $default_filter_label ) );
						} else {
							echo esc_html( $default_filter_label );
						}
							unset( $default_filter_label );
						?>
					</span>
					<i class="bb-icons-rl-caret-down"></i>
				</button>
				<div id="bb-subnav-filter-show" class="subnav-filters-modal">
					<ul role="listbox">
						<?php
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
							}

							unset( $activity_filters );
						}
						?>
					</ul>
				</div>
			</div>
		</div>

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
		<div class='<?php echo esc_attr( $hide_class ); ?> bb-subnav-filters-container-main'>
			<span class="bb-subnav-filters-label"><?php echo esc_html_e( 'by', 'buddyboss' ); ?></span>
			<div class="bb-subnav-filters-container bb-subnav-filters-filtering">
				<?php $sorting_labels = bb_get_activity_sorting_options_labels(); ?>
				<button class="subnav-filters-opener" aria-expanded="false" aria-controls="bb-subnav-filter-by">
					<span class="selected"><?php echo esc_html( strtolower( $sorting_labels[ $default_selected ] ) ); ?></span>
					<i class="bb-icons-rl-caret-down"></i>
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
	<?php
}
?>
