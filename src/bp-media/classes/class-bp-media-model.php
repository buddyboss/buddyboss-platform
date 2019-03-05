<?php
/**
 * BuddyBoss Media Model
 *
 * @package BuddyBoss\Media\Model
 * @since BuddyBoss 1.0.0
 */

abstract class BP_Media_Model {

	static $primary_key = 'id';

	private static function _table() {
		$bp_prefix       = bp_core_get_table_prefix();
		$tablename = strtolower( get_called_class() );
		return $bp_prefix . $tablename;
	}

	private static function _fetch_sql( $value ) {
		global $wpdb;
		$sql = sprintf( 'SELECT * FROM %s WHERE %s = %%s', self::_table(), static::$primary_key );
		return $wpdb->prepare( $sql, $value );
	}

	static function get( $value ) {
		global $wpdb;
		return $wpdb->get_row( self::_fetch_sql( $value ) );
	}

	static function insert( $data ) {
		global $wpdb;
		$wpdb->insert( self::_table(), $data );
	}

	static function update( $data, $where ) {
		global $wpdb;
		$wpdb->update( self::_table(), $data, $where );
	}

	static function delete( $value, $media_id = false ) {
		global $wpdb;

		if ( empty( $media_id ) ){
			$media = self::get( $value );
			$media_id = $media->media_id;
		}
		wp_delete_post( $media_id, true );

		$sql = sprintf( 'DELETE FROM %s WHERE %s = %%s', self::_table(), static::$primary_key );
		return $wpdb->query( $wpdb->prepare( $sql, $value ) );
	}

	static function where( $columns, $offset = false, $per_page = false, $order_by = 'id desc' ) {
		$select = 'SELECT * FROM ' . self::_table();
		$where  = ' where 2=2 ';
		foreach ( $columns as $colname => $colvalue ) {
			if ( is_array( $colvalue ) ) {
				if ( ! isset( $colvalue['compare'] ) ) {
					$compare = 'IN';
				} else {
					$compare = $colvalue['compare'];
				}
				if ( ! isset( $colvalue['value'] ) ) {
					$colvalue['value'] = $colvalue;
				}
				$col_val_compare = ( $colvalue['value'] ) ? '(\'' . implode( "','", $colvalue['value'] ) . '\')' : '';
				$where .= " AND " . self::_table() . ".{$colname} {$compare} {$col_val_compare}";
			} else {
				$where .= " AND " . self::_table() . ".{$colname} = '{$colvalue}'";
			}
		}
		$sql = $select . $where;
		$sql .= " ORDER BY " . self::_table() . ".$order_by";
		if ( false !== $offset ) {
			if ( ! is_integer( $offset ) ) {
				$offset = 0;
			}
			if ( intval( $offset ) < 0 ) {
				$offset = 0;
			}
			if ( ! is_integer( $per_page ) ) {
				$per_page = 1;
			}
			if ( intval( $per_page ) < 0 ) {
				$per_page = 1;
			}
			$sql .= ' LIMIT ' . $offset . ',' . $per_page;
		}
		global $wpdb;
		return $wpdb->get_results( $sql );
	}

	static function rows( $columns ) {
		$select = 'SELECT COUNT(*) FROM ' . self::_table();
		$where  = ' where 2=2 ';
		foreach ( $columns as $colname => $colvalue ) {
			if ( is_array( $colvalue ) ) {
				if ( ! isset( $colvalue['compare'] ) ) {
					$compare = 'IN';
				} else {
					$compare = $colvalue['compare'];
				}
				if ( ! isset( $colvalue['value'] ) ) {
					$colvalue['value'] = $colvalue;
				}
				$col_val_comapare = ( $colvalue['value'] ) ? '(\'' . implode( "','", $colvalue['value'] ) . '\')' : '';
				$where .= " AND " . self::_table() . ".{$colname} {$compare} {$col_val_comapare}";
			} else {
				$where .= " AND " . self::_table() . ".{$colname} = '{$colvalue}'";
			}
		}
		$sql = $select . $where;
		global $wpdb;
		return $wpdb->get_var( $sql );
	}

	static function insert_id() {
		global $wpdb;
		return $wpdb->insert_id;
	}

	static function time_to_date( $time ) {
		return gmdate( 'Y-m-d H:i:s', $time );
	}

	static function now() {
		return self::time_to_date( time() );
	}

	static function date_to_time( $date ) {
		return strtotime( $date . ' GMT' );
	}

}