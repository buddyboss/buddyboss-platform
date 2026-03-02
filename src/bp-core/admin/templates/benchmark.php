<?php

/**
 * PHP Script to benchmark PHP and MySQL-Server
 *
 * inspired by / thanks to:
 * - www.php-benchmark-script.com  (Alessandro Torrisi)
 * - www.webdesign-informatik.de
 *
 * @author odan
 * @version 2014.02.23
 * @license MIT
 */
// -----------------------------------------------------------------------------
// Setup
// -----------------------------------------------------------------------------
set_time_limit( 120 ); // 2 minutes

$arr_cfg = array();

// optional: mysql performance test.
// $arr_cfg['db.host'] = DB_HOST;
// $arr_cfg['db.user'] = DB_USER;
// $arr_cfg['db.pw'] = DB_PASSWORD;
// $arr_cfg['db.name'] = DB_NAME;.

// -----------------------------------------------------------------------------
// Benchmark functions
// -----------------------------------------------------------------------------

function bb_test_benchmark( $arr_cfg ) {

	$arr_return                           = array();
	$arr_return['version']                = '1.6';
	$arr_return['sysinfo']['time']        = date( 'Y-m-d H:i:s' );
	$arr_return['sysinfo']['php_version'] = PHP_VERSION;
	$arr_return['sysinfo']['platform']    = PHP_OS;
	$arr_return['sysinfo']['server_name'] = $_SERVER['SERVER_NAME'];
	$arr_return['sysinfo']['server_addr'] = $_SERVER['SERVER_ADDR'];

	$time_start = microtime( true );

	bb_test_math( $arr_return );

	bb_test_string( $arr_return );

	bb_test_loops( $arr_return );

	bb_test_ifelse( $arr_return );

	if ( isset( $arr_cfg['db.host'] ) ) {
		bb_test_mysql( $arr_return, $arr_cfg );
	}

	$arr_return['total'] = bb_timer_diff( $time_start );

	return $arr_return;
}

function bb_test_math( &$arr_return, $count = 99999 ) {
	$time_start = microtime( true );

	for ( $i = 0; $i < $count; $i++ ) {
		sin( $i );
		asin( $i );
		cos( $i );
		acos( $i );
		tan( $i );
		atan( $i );
		abs( $i );
		floor( $i );
		exp( $i );
		is_finite( $i );
		is_nan( $i );
		sqrt( $i );
		log10( $i );
	}

	$arr_return['benchmark']['math'] = bb_timer_diff( $time_start );
}

function bb_test_string( &$arr_return, $count = 99999 ) {
	$time_start = microtime( true );
	$string     = 'the quick brown fox jumps over the lazy dog';
	for ( $i = 0; $i < $count; $i++ ) {
		addslashes( $string );
		chunk_split( $string );
		metaphone( $string );
		strip_tags( $string );
		md5( $string );
		sha1( $string );
		strtoupper( $string );
		strtolower( $string );
		strrev( $string );
		strlen( $string );
		soundex( $string );
		ord( $string );
	}
	$arr_return['benchmark']['string'] = bb_timer_diff( $time_start );
}

function bb_test_loops( &$arr_return, $count = 999999 ) {
	$time_start = microtime( true );
	for ($i = 0; $i < $count; ++$i);
	$i = 0;
	while ( $i < $count ) {
		++$i;
	}

	$arr_return['benchmark']['loops'] = bb_timer_diff( $time_start );
}

function bb_test_ifelse( &$arr_return, $count = 999999 ) {
	$time_start = microtime( true );
	for ( $i = 0; $i < $count; $i++ ) {
		if ( $i == -1 ) {

		} elseif ( $i == -2 ) {

		} elseif ( $i == -3 ) {

		}
	}
	$arr_return['benchmark']['ifelse'] = bb_timer_diff( $time_start );
}

function bb_test_mysql( &$arr_return, $arr_cfg ) {

	$time_start = microtime( true );

	// detect socket connection.
	if ( stripos( $arr_cfg['db.host'], '.sock' ) !== false ) {
		// parse socket location.
		// set a default guess.
		$socket     = '/var/lib/mysql.sock';
		$serverhost = explode( ':', $arr_cfg['db.host'] );
		if ( count( $serverhost ) === 2 && $serverhost[0] === 'localhost' ) {
			$socket = $serverhost[1];
		}
		$link = mysqli_connect( 'localhost', $arr_cfg['db.user'], $arr_cfg['db.pw'], $arr_cfg['db.name'], null, $socket );
	} else {
		// parse out port number if exists.
		$port = 3306;// default.
		if ( stripos( $arr_cfg['db.host'], ':' ) ) {
			$port               = substr( $arr_cfg['db.host'], stripos( $arr_cfg['db.host'], ':' ) + 1 );
			$arr_cfg['db.host'] = substr( $arr_cfg['db.host'], 0, stripos( $arr_cfg['db.host'], ':' ) );
		}
		$link = mysqli_connect( $arr_cfg['db.host'], $arr_cfg['db.user'], $arr_cfg['db.pw'], $arr_cfg['db.name'], $port );
	}
	$arr_return['benchmark']['mysql_connect'] = bb_timer_diff( $time_start );

	$result                                 = mysqli_query( $link, 'SELECT VERSION() as version;' );
	$arr_row                                = mysqli_fetch_assoc( $result );
	$arr_return['sysinfo']['mysql_version'] = $arr_row['version'];
	$arr_return['benchmark']['mysql_query_version'] = bb_timer_diff( $time_start );

	$query  = "SELECT BENCHMARK(5000000, AES_ENCRYPT(CONCAT('WPHostingBenchmarks.com',RAND()), UNHEX(SHA2('is part of Review Signal.com',512))))";
	$result = mysqli_query( $link, $query );
	$arr_return['benchmark']['mysql_query_benchmark'] = bb_timer_diff( $time_start );

	mysqli_close( $link );

	$arr_return['benchmark']['mysql_total'] = bb_timer_diff( $time_start );

	return $arr_return;
}

function bb_test_wordpress() {

	// create dummy text to insert into database.
	$dummytextseed = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque sollicitudin iaculis libero id pellentesque. Donec sodales nunc id lorem rutrum molestie. Duis ac ornare diam. In hac habitasse platea dictumst. Donec nec mi ipsum. Aenean dictum imperdiet erat, at lacinia mi ultrices ut. Phasellus quis nibh ornare, pulvinar dui sit amet, venenatis arcu. Suspendisse eget vehicula ligula, et placerat sapien. Cras enim erat, scelerisque sit amet tellus vel, tempor venenatis risus. In ultricies tristique ante, eu lobortis leo. Cras ullamcorper eleifend libero, quis sollicitudin massa venenatis a. Vestibulum sed pellentesque urna, nec consectetur nulla. Vestibulum sodales purus metus, non scelerisque.';
	$dummytext     = '';
	for ( $x = 0; $x < 100; $x++ ) {
		$dummytext .= str_shuffle( $dummytextseed );
	}

	// start timing WordPress mysql functions.
	$time_start = microtime( true );
	global $wpdb;
	$table      = $wpdb->prefix . 'options';
	$optionname = 'wpperformancetesterbenchmark_';
	$count      = 250;
	for ( $x = 0; $x < $count;$x++ ) {
		// insert.
		$data = array(
			'option_name'  => $optionname . $x,
			'option_value' => $dummytext,
		);
		$wpdb->insert( $table, $data );
		// select.
		$select = "SELECT option_value FROM $table WHERE option_name='$optionname" . $x . "'";
		$wpdb->get_var( $select );
		// update.
		$data  = array( 'option_value' => $dummytextseed );
		$where = array( 'option_name' => $optionname . $x );
		$wpdb->update( $table, $data, $where );
		// delete.
		$where = array( 'option_name' => $optionname . $x );
		$wpdb->delete( $table, $where );
	}

	$time    = bb_timer_diff( $time_start );
	$queries = ( $count * 4 ) / $time;
	return array(
		'time'    => $time,
		'queries' => $queries,
	);
}


function bb_timer_diff( $time_start ) {
	return number_format( microtime( true ) - $time_start, 3 );
}

