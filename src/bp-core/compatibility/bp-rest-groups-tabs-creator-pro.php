<?php
/**
 * Filters related to the BuddyPress Groups Tabs Creator Pro integration.
 *
 * @package BuddyBoss
 * @since   BuddyBoss 1.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Apply WordPress defined filters.
add_filter( 'bp_rest_group_tabs', 'bp_rest_group_tabs_creator_pro', 10, 1 );

/* Filters *******************************************************************/
/**
 * Group tab create function.
 */
if ( ! function_exists( 'bp_rest_group_tabs_creator_pro' ) ) {

	/**
	 * Get BuddyPress Groups Tabs Creator Pro tasb getting.
	 *
	 * @param array $navigation Array of navigation items.
	 *
	 * @return array|mixed
	 *
	 * @since BuddyBoss 1.6.0
	 */
	function bp_rest_group_tabs_creator_pro( $navigation ) {
		if ( function_exists( 'bpgtc_get_active_group_tab_entries' ) ) {
			$active_tabs = bpgtc_get_active_group_tab_entries();
			$tabs        = array();

			if ( ! empty( $active_tabs ) ) {
				foreach ( $active_tabs as $active_tab ) {

					// if not active or no unique key, do not register.
					if ( ! $active_tab->is_active || ! $active_tab->slug ) {
						continue;
					}

					$current_group = bp_is_group() ? groups_get_current_group() : null;

					// do not add tab if does not apply to this group.
					if ( ! bpgtc_is_tab_available( $active_tab, $current_group ) ) {
						continue;
					}

					if ( ! empty( $active_tab->link ) ) {
						$link = trailingslashit( bpgtc_parse_group_tab_url( $active_tab->link ) );
					} else {
						$group_link = bp_get_group_permalink( $current_group );
						$link       = trailingslashit( $group_link . $active_tab->slug );
					}

					$tab = array(
						'id'              => $active_tab->slug,
						'title'           => $active_tab->label,
						'count'           => false,
						'position'        => $active_tab->position,
						'default'         => false,
						'user_has_access' => ! empty( $current_group ) ? $current_group->user_has_access : false,
						'link'            => $link,
						'children'        => array(),
					);

					$tabs[] = $tab;
				}

				$navigation = array_merge( $navigation, $tabs );
				$navigation = array_msort( $navigation, array( 'position' => 'SORT_ASC' ) );
			}
		}

		return $navigation;
	}
}

/**
 * Array msort function.
 */
if ( ! function_exists( 'array_msort' ) ) {
	/**
	 * Sort data based on order.
	 *
	 * Sorts a multidimensional array by one or more columns. Each entry in $cols
	 * maps a column key to a direction string: 'SORT_ASC' or 'SORT_DESC'.
	 * Comparison is case-insensitive; numeric values are compared numerically.
	 *
	 * DEPS-02: refactored from dynamic-execution to usort() closure.
	 *
	 * @param array $array Multidimensional array.
	 * @param array $cols  Map of column name => direction ('SORT_ASC'|'SORT_DESC').
	 *
	 * @return array
	 *
	 * @since BuddyBoss 1.6.0
	 */
	function array_msort( $array, $cols ) {

		$array = json_decode( wp_json_encode( $array ), true );

		if ( empty( $array ) ) {
			return array();
		}

		// DEPS-02: refactored from dynamic-execution (array_multisort via eval) to
		// usort() with a closure. Behavior preserved: multi-key sort, per-column
		// ascending/descending, case-insensitive string comparison via strnatcmp.
		usort(
			$array,
			static function ( $a, $b ) use ( $cols ) {
				foreach ( $cols as $col => $direction ) {
					if ( ! array_key_exists( $col, $a ) || ! array_key_exists( $col, $b ) ) {
						continue;
					}
					$val_a = strtolower( (string) $a[ $col ] );
					$val_b = strtolower( (string) $b[ $col ] );
					if ( is_numeric( $a[ $col ] ) && is_numeric( $b[ $col ] ) ) {
						$cmp = ( (float) $a[ $col ] <=> (float) $b[ $col ] );
					} else {
						$cmp = strnatcmp( $val_a, $val_b );
					}
					if ( 0 !== $cmp ) {
						return ( 'SORT_DESC' === $direction ) ? -$cmp : $cmp;
					}
				}
				return 0;
			}
		);

		return array_values( $array );
	}
}
