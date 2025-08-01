<?php
/**
 * ReadyLaunch - Groups screens filters template.
 *
 * This template handles the sorting filters for group activity streams
 * providing options to sort by date, alphabetical, and other criteria.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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
<i class="bb-rl-loader"></i>
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
