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
					$current_group = bp_is_group() ? groups_get_current_group() : null;
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
	 * @param array $array Multidimentional array.
	 * @param array $cols Array of sorted column.
	 *
	 * @return array|mixed
	 *
	 * @since BuddyBoss 1.6.0
	 */
	function array_msort( $array, $cols ) {

		$array = json_decode( json_encode( $array ), true );

		$colarr = array();
		foreach ( $cols as $col => $order ) {
			$colarr[ $col ] = array();
			foreach ( $array as $k => $row ) {
				$colarr[ $col ][ '_' . $k ] = strtolower( $row[ $col ] );
			}
		}
		$eval = 'array_multisort(';
		foreach ( $cols as $col => $order ) {
			$eval .= '$colarr[\'' . $col . '\'],' . $order . ',';
		}
		$eval = substr( $eval, 0, - 1 ) . ');';
		eval( $eval );
		$ret = array();
		foreach ( $colarr as $col => $arr ) {
			foreach ( $arr as $k => $v ) {
				$k = substr( $k, 1 );
				if ( ! isset( $ret[ $k ] ) ) {
					$ret[ $k ] = $array[ $k ];
				}
				$ret[ $k ][ $col ] = $array[ $k ][ $col ];
			}
		}

		if ( ! empty( $ret ) ) {
			$i   = 0;
			$arr = array();
			foreach ( $ret as $k => $v ) {
				$ret[ $i ] = (object) $v;
				$arr[ $i ] = (object) $v;
				$i ++;
			}
		}

		return $arr;

	}
}
