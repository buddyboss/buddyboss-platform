<?php

if (!defined('ABSPATH')) {
	die('You are not allowed to call this page directly.');
}

class BB_Utils {

	public static function is_logged_in_and_an_admin()
	{
		return (self::is_user_logged_in() and self::is_bb_admin());
	}

	public static function is_logged_in_and_a_subscriber()
	{
		return (self::is_user_logged_in() and self::is_subscriber());
	}

	public static function get_bb_admin_capability()
	{
		return BB_Hooks::apply_filters('bb-admin-capability', 'remove_users');
	}

	public static function is_bb_admin($user_id = null)
	{
		$bb_cap = self::get_bb_admin_capability();

		if (empty($user_id)) {
			return self::current_user_can($bb_cap);
		} else {
			return user_can($user_id, $bb_cap);
		}
	}

	public static function is_subscriber()
	{
		return (current_user_can('subscriber'));
	}

	public static function current_user_can($role)
	{
		self::include_pluggables('wp_get_current_user');
		return current_user_can($role);
	}

	public static function minutes($n = 1)
	{
		return $n * 60;
	}

	public static function hours($n = 1)
	{
		return $n * self::minutes(60);
	}

	public static function days($n = 1)
	{
		return $n * self::hours(24);
	}

	public static function weeks($n = 1)
	{
		return $n * self::days(7);
	}

	public static function months($n, $base_ts = false, $backwards = false, $day_num = false)
	{
		$base_ts = empty($base_ts) ? time() : $base_ts;

		$month_num  = gmdate('n', $base_ts);
		$day_num    = ( (int) $day_num < 1 || (int) $day_num > 31 ) ? gmdate('j', $base_ts) : $day_num;
		$year_num   = gmdate('Y', $base_ts);
		$hour_num   = gmdate('H', $base_ts);
		$minute_num = gmdate('i', $base_ts);
		$second_num = gmdate('s', $base_ts);

		// We're going to use the FIRST DAY of month for our calc date, then adjust the day of month when we're done
		// This allows us to get the correct target month first, then set the right day of month afterwards
		try {
			$calc_date = new DateTime("{$year_num}-{$month_num}-1 {$hour_num}:{$minute_num}:{$second_num}", new DateTimeZone('UTC'));
		} catch (Exception $e) {
			return 0;
		}

		if ($backwards) {
			$calc_date->modify("-{$n} month");
		} else {
			$calc_date->modify("+{$n} month");
		}

		$days_in_new_month = $calc_date->format('t');

		// Now that we have the right month, let's get the right day of month
		if ($days_in_new_month < $day_num) {
			$calc_date->modify('last day of this month');
		} elseif ($day_num > 1) {
			$add_days = ( $day_num - 1 ); // $calc_date is already at the first day of the month, so we'll minus one day here
			$calc_date->modify("+{$add_days} day");
		}

		// If $backwards is true, this will most likely be a negative number so we'll use abs()
		return abs($calc_date->getTimestamp() - $base_ts);
	}

	public static function years($n, $base_ts = false, $backwards = false, $day_num = false, $month_num = false)
	{
		$base_ts = empty($base_ts) ? time() : $base_ts;

		$day_num    = ( (int) $day_num < 1 || (int) $day_num > 31 ) ? gmdate('j', $base_ts) : $day_num;
		$month_num  = ( (int) $month_num < 1 || (int) $month_num > 12 ) ? gmdate('n', $base_ts) : $month_num;
		$year_num   = gmdate('Y', $base_ts);
		$hour_num   = gmdate('H', $base_ts);
		$minute_num = gmdate('i', $base_ts);
		$second_num = gmdate('s', $base_ts);

		try {
			$calc_date = new DateTime("{$year_num}-{$month_num}-{$day_num} {$hour_num}:{$minute_num}:{$second_num}", new DateTimeZone('UTC'));
		} catch (Exception $e) {
			return 0;
		}

		if ($backwards) {
			$calc_date->modify("-{$n} year");
		} else {
			$calc_date->modify("+{$n} year");
		}

		// If we're counting from Feb 29th on a Leap Year to a non-leap year we need to minus 1 day
		// or we'll end up with a March 1st date
		if ($day_num == 29 && $month_num == 2 && $calc_date->format('L') == 0) {
			$calc_date->modify('-1 day');
		}

		// If $backwards is true, this will most likely be a negative number so we'll use abs()
		return abs($calc_date->getTimestamp() - $base_ts);
	}

	// convert timestamp into approximate minutes
	public static function tsminutes($ts)
	{
		return (int)($ts / 60);
	}

	// convert timestamp into approximate hours
	public static function tshours($ts)
	{
		return (int)(self::tsminutes($ts) / 60);
	}

	// convert timestamp into approximate days
	public static function tsdays($ts)
	{
		return (int)(self::tshours($ts) / 24);
	}

	// convert timestamp into approximate weeks
	public static function tsweeks($ts)
	{
		return (int)(self::tsdays($ts) / 7);
	}

	// Coupons rely on this be careful changing it
	public static function make_ts_date($month, $day, $year, $begin = false)
	{
		if (true === $begin) {
			return mktime(00, 00, 01, $month, $day, $year);
		}
		return mktime(23, 59, 59, $month, $day, $year);
	}

	// Coupons rely on this be careful changing it
	public static function get_date_from_ts($ts, $format = 'M d, Y')
	{
		if ($ts > 0) {
			return gmdate($format, $ts);
		} else {
			return gmdate($format, time());
		}
	}

	public static function db_date_to_ts($mysql_date)
	{
		return strtotime($mysql_date);
	}

	public static function ts_to_mysql_date($ts, $format = 'Y-m-d H:i:s')
	{
		return gmdate($format, $ts);
	}

	public static function db_now($format = 'Y-m-d H:i:s')
	{
		return self::ts_to_mysql_date(time(), $format);
	}

	public static function db_lifetime()
	{
		return '0000-00-00 00:00:00';
	}

	/***
	 * Deprecated mysql* functions
	 ***/
	public static function mysql_date_to_ts($mysql_date)
	{
		return self::db_date_to_ts($mysql_date);
	}

	public static function mysql_now($format = 'Y-m-d H:i:s')
	{
		return self::db_now($format);
	}

	public static function mysql_lifetime()
	{
		return self::db_lifetime();
	}

	public static function array_to_string($my_array, $debug = false, $level = 0)
	{
		return self::object_to_string($my_array);
	}

	public static function object_to_string($object)
	{
		ob_start();
		print_r($object);

		return ob_get_clean();
	}

	// Inserts into an associative array
	public static function a_array_insert($array, $values, $offset)
	{
		return array_slice($array, 0, $offset, true) + $values + array_slice($array, $offset, null, true);
	}

	// Drop in replacement for evil eval
	public static function replace_vals($content, $params, $start_token = '\\{\$', $end_token = '\\}')
	{
		if (!is_array($params)) {
			return $content;
		}

		$callback = function ($k) use ($start_token, $end_token) {
			$k = preg_quote($k, '/');
			return "/{$start_token}" . "[^\W_]*{$k}[^\W_]*" . "{$end_token}/";
		};
		$patterns = array_map($callback, array_keys($params));
		$replacements = array_values($params);

		// Make sure all replacements can be converted to a string yo
		foreach ($replacements as $i => $val) {
			// The method_exists below causes a fatal error for incomplete classes
			if ($val instanceof __PHP_Incomplete_Class) {
				$replacements[$i] = '';
				continue;
			}

			// Numbers and strings and objects with __toString are fine as is
			if (is_string($val) || is_numeric($val) || (is_object($val) && method_exists($val, '__toString'))) {
				continue;
			}

			// Datetime's
			if ($val instanceof DateTime && isset($val->date)) {
				$replacements[$i] = $val->date;
				continue;
			}

			// If we made it here ???
			$replacements[$i] = '';
		}

		$result = preg_replace($patterns, $replacements, $content);

		// Remove unreplaced tags
		return preg_replace('({\$.*?})', '', $result);
	}

	public static function format_tax_percent_for_display($number)
	{
		$number = self::format_float($number, 3) + 0; // Number with period as decimal point - adding 0 will truncate insignificant 0's at the end

		// How many decimal places are left?
		$num_remain_dec = strlen(substr(strrchr($number, '.'), 1));

		return number_format_i18n($number, $num_remain_dec);
	}

	public static function format_float($number, $num_decimals = 2)
	{
		return number_format($number, $num_decimals, '.', '');
	}

	public static function format_float_drop_zero_decimals($n, $num_decimals = 2)
	{
		return ((floor($n) == round($n, $num_decimals)) ? number_format($n, 0, '.', '') : number_format($n, $num_decimals, '.', ''));
	}

	public static function float_value($val)
	{
		$val = str_replace(',', '.', $val);
		$val = preg_replace('/\.(?=.*\.)/', '', $val);

		return floatval($val);
	}

	public static function format_currency_float($number, $num_decimals = 2)
	{
		if (is_string($number)) {
			$number = self::float_value($number);
		}

		if (function_exists('number_format_i18n')) {
			return number_format_i18n($number, $num_decimals); // The wp way
		}

		return self::format_float($number, $num_decimals);
	}

	/**
	 * Converts number to US format
	 *
	 * @param  mixed $number
	 * @param  mixed $num_decimals
	 * @return void
	 */
	public static function format_currency_us_float($number, $num_decimals = 2)
	{
		global $wp_locale;

		if (! isset($wp_locale) || false === function_exists('number_format_i18n')) {
			return self::format_float($number, $num_decimals);
		}

		$decimal_point = $wp_locale->number_format['decimal_point'];
		$thousands_sep = $wp_locale->number_format['thousands_sep'];

		// Remove thousand separator
		$number = str_replace($thousands_sep, '', $number);

		// Fix for locales where the thousand seperator is a space -
		// need to check for the html code, (above) as well as the actual space (handled with preg_replace below) and ascii 160 (str_replace below)
		// and for some reason str_replace doesn't always work on spaces but the preg_replace does
		if ($thousands_sep == '&nbsp;' || $thousands_sep == ' ' || $thousands_sep == "\xc2\xa0") {
			$number = preg_replace('/\s+/', '', $number);
			$number = str_replace("\xc2\xa0", '', $number);
		}

		// Replaces decimal separator
		$index = strrpos($number, $decimal_point);
		if ($index !== false) {
			$number[ $index ] = '.';
		}

		return (float) $number;
	}

	public static function protocol()
	{
		if (
			is_ssl() ||
			( defined('BB_SECURE_PROXY') && // USER must define this in wp-config.php if they're doing HTTPS between the proxy
			  isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
			  strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https' )
		) {
			return 'https';
		} else {
			return 'http';
		}
	}

	// Less problemmatic replacement for WordPress' is_ssl() function
	public static function is_ssl()
	{
		return (self::protocol() === 'https');
	}

	public static function get_property($className, $property)
	{
		if (!class_exists($className)) {
			return null;
		}
		if (!property_exists($className, $property)) {
			return null;
		}

		$vars = get_class_vars($className);

		return $vars[$property];
	}

	public static function random_string($length = 10, $lowercase = true, $uppercase = false, $symbols = false)
	{
		$characters = '0123456789';
		$characters .= $uppercase ? 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' : '';
		$characters .= $lowercase ? 'abcdefghijklmnopqrstuvwxyz' : '';
		$characters .= $symbols ? '@#*^%$&!' : '';
		$string = '';
		$max_index = strlen($characters) - 1;

		for ($p = 0; $p < $length; $p++) {
			$string .= $characters[mt_rand(0, $max_index)];
		}

		return $string;
	}

	public static function sanitize_string($string)
	{
		// Converts "Hey there buddy-boy!" to "hey_there_buddy_boy"
		return str_replace('-', '_', sanitize_title($string));
	}
	public static function is_associative_array($arr)
	{
		return array_keys($arr) !== range(0, count($arr) - 1);
	}

	public static function get_post_meta_with_default($post_id, $meta_key, $single = false, $default = null)
	{
		$pms = get_post_custom($post_id);
		$var = get_post_meta($post_id, $meta_key, $single);

		if (($single and $var == '') or (!$single and $var == [])) {
			// Since false bools are stored as empty string ('') we need
			// to see if the meta_key is actually stored in the db and
			// it's a bool value before we blindly return default
			if (isset($pms[$meta_key]) and is_bool($default)) {
				return false;
			} else {
				return $default;
			}
		} else {
			return $var;
		}
	}

	public static function get_post_meta_values($meta_key)
	{
		global $wpdb;

		$query = $wpdb->prepare("SELECT * FROM {$wpdb->postmeta} WHERE meta_key=%s", $meta_key);
		$metas = $wpdb->get_results($query);

		for ($i = 0; $i < count($metas); $i++) {
			$metas[$i]->meta_value = maybe_unserialize($metas[$i]->meta_value);
		}

		return $metas;
	}

	public static function convert_to_plain_text($text)
	{
		$text = preg_replace('~<style[^>]*>[^<]*</style>~', '', $text);
		$text = strip_tags($text);
		$text = trim($text);
		$text = preg_replace("~\r~", '', $text); // Make sure we're only dealint with \n's here
		$text = preg_replace("~\n\n+~", "\n\n", $text); // reduce 1 or more blank lines to 1

		return $text;
	}

	public static function array_splice_assoc(&$input, $offset, $length, $replacement)
	{
		$replacement = (array) $replacement;
		$key_indices = array_flip(array_keys($input));

		if (isset($input[$offset]) && is_string($offset)) {
			$offset = $key_indices[$offset];
		}
		if (isset($input[$length]) && is_string($length)) {
			$length = $key_indices[$length] - $offset;
		}

		$input = array_slice($input, 0, $offset, true)
		         + $replacement
		         + array_slice($input, $offset + $length, null, true);
	}

	public static function post_uri($post_id)
	{
		return preg_replace('!' . preg_quote(home_url(), '!') . '!', '', get_permalink($post_id));
	}

	// Get the current post, and account for non-singular views
	public static function get_current_post()
	{
		global $post;

		if (in_the_loop()) {
			$post_id = get_the_ID(); // returns false or ID

			if ($post_id !== false && $post_id > 0) {
				$new_post = get_post($post_id); // Returns WP_Post or null
			}
		}

		if (!isset($new_post) && isset($post) && $post instanceof WP_Post && $post->ID > 0) {
			$new_post = get_post($post->ID); // Returns WP_Post or null
		}

		return (isset($new_post)) ? $new_post : false;
	}

	public static function array_insert($array, $index, $insert)
	{
		$pos    = array_search($index, array_keys($array));
		$pos    = empty($pos) ? 0 : (int)$pos;
		$before = array_slice($array, 0, $pos + 1);
		$after  = array_slice($array, $pos);
		$array  = $before + $insert + $after;

		return $array;
	}

	/*
		Convert a snake-case string to camel case. The 'lower' parameter
	 * will allow you to choose 'lower' camelCase or 'upper' CamelCase.
	 */
	public static function camelcase($str, $type = 'lower')
	{
		// Level the playing field
		$str = strtolower($str);
		// Replace dashes and/or underscores with spaces to prepare for ucwords
		$str = preg_replace('/[-_]/', ' ', $str);
		// Ucwords bro ... uppercase the first letter of every word
		$str = ucwords($str);
		// Now get rid of the spaces
		$str = preg_replace('/ /', '', $str);

		if ($type == 'lower') {
			// Lowercase the first character of the string
			$str[0] = strtolower($str[0]);
		}

		return $str;
	}

	public static function lower_camelcase($str)
	{
		return self::camelcase($str, 'lower');
	}

	public static function upper_camelcase($str)
	{
		return self::camelcase($str, 'upper');
	}

	public static function snakecase($str, $delim = '_')
	{
		// Search for '_-' then just lowercase and ensure correct delim
		if (preg_match('/[-_]/', $str)) {
			$str = preg_replace('/[-_]/', $delim, $str);
		} else { // assume camel case
			$str = preg_replace('/([A-Z])/', $delim . '$1', $str);
			$str = preg_replace('/^' . preg_quote($delim) . '/', '', $str);
		}

		return strtolower($str);
	}

	public static function kebabcase($str)
	{
		return self::snakecase($str, '-');
	}

	public static function humancase($str, $delim = ' ')
	{
		$str = self::snakecase($str, $delim);
		return ucwords($str);
	}

	public static function unsanitize_title($str)
	{
		if (!is_string($str)) {
			return __('Unknown', 'buddyboss');
		}

		$str = str_replace(['-', '_'], [' ', ' '], $str);
		return ucwords($str);
	}

	// Deep convert to associative array using JSON
	// TODO: Find some cleaner way to do a deep convert to an assoc array
	public static function deep_convert_to_associative_array($struct)
	{
		return json_decode(json_encode($struct), true);
	}

	public static function hex_encode($str, $delim = '%')
	{
		$encoded = bin2hex($str);
		$encoded = chunk_split($encoded, 2, $delim);
		$encoded = $delim . substr($encoded, 0, strlen($encoded) - strlen($delim));

		return $encoded;
	}

	public static function user_meta_exists($user_id, $meta_key)
	{
		global $wpdb;

		$q = "SELECT COUNT(*)
            FROM {$wpdb->usermeta} AS um
           WHERE um.user_id=%d
             AND um.meta_key=%s";
		$q = $wpdb->prepare($q, $user_id, $meta_key);
		$count = $wpdb->get_var($q);

		return ($count > 0);
	}

	public static function clean($str)
	{
		return sanitize_text_field($str);
	}

	/**
	 * This is for converting an array that would look something like this into an SQL where clause:
	 *        array(
	 *          array(
	 *            'var' => 'tr.id',
	 *            'val' => '28'
	 *          ),
	 *          array(
	 *            'cond' => 'OR',
	 *            'var'  => 'tr.txn_type',
	 *            'op'   => '<>',
	 *            'val'  => 'payment'
	 *          )
	 *        )
	 *
	 *      This is mainly used with params coming in from the URL so we don't get any sql injection happening.
	 */
	public static function build_where_clause($q, $where = '')
	{
		global $wpdb;

		if (!empty($q)) {
			foreach ($q as $qk => $qv) {
				if (isset($qv['var']) && isset($qv['val'])) {
					$cond = ' ';
					$cond .= ((isset($qv['cond']) && preg_match('/^(AND|OR)$/i', $qv['cond'])) ? $qv['cond'] : 'AND');
					$cond .= ' ';
					$cond .= preg_match('/^`[\w\.]+`$/', $qv['var']) ? $qv['var'] : '`' . $qv['var'] . '`';
					$cond .= ((isset($qv['op']) && preg_match('/^(<>|<|>|<=|>=)$/i', $qv['op'])) ? $qv['op'] : '=');
					$cond .= is_numeric($qv['val']) ? '%d' : '%s';
					$where .= $wpdb->prepare($cond, $qv['val']);
				}
			}
		}

		return $where;
	}

	public static function compress_css($buffer)
	{
		/* remove comments */
		$buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);

		/* remove tabs, spaces, newlines, etc. */
		$buffer = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $buffer);

		return $buffer;
	}

	public static function is_ip($ip)
	{
		// return preg_match('#^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$#',$ip);
		return ((bool)filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || (bool)filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6));
	}

	public static function country_by_ip($ip = null, $source = 'geoplugin')
	{
		return (($loc = self::locate_by_ip()) ? $loc->country : '' );
	}

	public static function state_by_ip($ip = null, $source = 'geoplugin')
	{
		return (($loc = self::locate_by_ip()) ? $loc->state : '' );
	}

	public static function base36_encode($base10)
	{
		return base_convert($base10, 10, 36);
	}

	public static function base36_decode($base36)
	{
		return base_convert($base36, 36, 10);
	}

	public static function is_date($str)
	{
		if (!is_string($str)) {
			return false;
		}

		if ('d/m/Y' === get_option('date_format')) {
			$str = preg_replace('/([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})/', '$2/$1/$3', $str);
		}

		$d = strtotime($str);

		return ($d !== false);
	}

	public static function is_url($str)
	{
		return preg_match('/https?:\/\/[\w-]+(\.[\w-]{2,})*(:\d{1,5})?/', $str);
	}

	public static function is_email($str)
	{
		return is_email($str);
	}

	public static function is_phone($str)
	{
		return preg_match('/\(?\d{3}\)?[- ]\d{3}-\d{4}/', $str);
	}

	public static function get_delim($link)
	{
		return ((preg_match('#\?#', $link)) ? '&' : '?');
	}

	public static function http_status_codes()
	{
		return [
			100 => 'Continue',
			101 => 'Switching Protocols',
			102 => 'Processing',
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			207 => 'Multi-Status',
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			306 => 'Switch Proxy',
			307 => 'Temporary Redirect',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',
			418 => 'I\'m a teapot',
			422 => 'Unprocessable Entity',
			423 => 'Locked',
			424 => 'Failed Dependency',
			425 => 'Unordered Collection',
			426 => 'Upgrade Required',
			449 => 'Retry With',
			450 => 'Blocked by Windows Parental Controls',
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported',
			506 => 'Variant Also Negotiates',
			507 => 'Insufficient Storage',
			509 => 'Bandwidth Limit Exceeded',
			510 => 'Not Extended',
		];
	}

	public static function exit_with_status($status, $message = '')
	{
		$codes = self::http_status_codes();
		header("HTTP/1.1 {$status} {$codes[$status]}", true, $status);
		exit($message);
	}

	public static function error_log($error)
	{
		error_log(sprintf(__('*** MemberPress Error: %s', 'buddyboss'), $error));
	}

	public static function is_wp_error($obj)
	{
		if (is_wp_error($obj)) {
			self::error_log($obj->get_error_message());
			return true;
		}

		return false;
	}

	public static function filter_array_keys($sarray, $keys)
	{
		$rarray = [];
		foreach ($sarray as $key => $value) {
			if (in_array($key, $keys)) {
				$rarray[$key] = $value;
			}
		}
		return $rarray;
	}

	public static function match_uri($pattern, $uri, &$matches, $include_query_string = false)
	{
		if ($include_query_string) {
			$uri = urldecode($uri);
		} else {
			// Remove query string and decode
			$uri = preg_replace('#(\?.*)?$#', '', urldecode($uri));
		}

		// Resolve WP installs in sub-directories
		preg_match('!^https?://[^/]*?(/.*)$!', site_url(), $m);

		$subdir = ( isset($m[1]) ? $m[1] : '' );
		$regex = '!^' . $subdir . $pattern . '$!';
		return preg_match($regex, $uri, $matches);
	}

	public static function build_query_string(
		$add_params = [],
		$include_query_string = false,
		$exclude_params = [],
		$exclude_referer = true
	) {
		$query_string = '';
		if ($include_query_string) {
			$query_string = $_SERVER['QUERY_STRING'];
		}

		if (empty($query_string)) {
			$query_string = http_build_query($add_params);
		} else {
			$query_string = $query_string . '&' . http_build_query($add_params);
		}

		if ($exclude_referer) {
			$exclude_params[] = '_wp_http_referer';
		}

		foreach ($exclude_params as $param) {
			$query_string = preg_replace('!&?' . preg_quote($param, '!') . '=[^&]*!', '', $query_string);
		}

		return $query_string;
	}

	// $add_nonce = [$action,$name]
	public static function admin_url(
		$path,
		$add_nonce = [],
		$add_params = [],
		$include_query_string = false,
		$exclude_params = [],
		$exclude_referer = true
	) {
		$delim = BBUtils::get_delim($path);

		// Automatically exclude the nonce if it's present
		if (!empty($add_nonce)) {
			$nonce_action = $add_nonce[0];
			$nonce_name = (isset($add_nonce[1]) ? $add_nonce[1] : '_wpnonce');
			$exclude_params[] = $nonce_name;
		}

		$url = admin_url($path . $delim . self::build_query_string($add_params, $include_query_string, $exclude_params, $exclude_referer));

		if (empty($add_nonce)) {
			return $url;
		} else {
			return html_entity_decode(wp_nonce_url($url, $nonce_action, $nonce_name));
		}
	}

	public static function pretty_permalinks_using_index()
	{
		$permalink_structure = get_option('permalink_structure');
		return preg_match('!^/index.php!', $permalink_structure);
	}

	/**
	 * Returns an array to be used with wp_remote_request
	 */
	public static function jwt_header($jwt, $domain)
	{
		return [
			'Authorization' => 'Bearer ' . $jwt,
			'Accept'        => 'application/json;ver=1.0',
			'Content-Type'  => 'application/json; charset=UTF-8',
			'Host'          => $domain,
		];
	}

	/**
	 * A more robust way to get a header.
	 */
	public static function get_http_header($header_name)
	{
		$header_name = strtoupper($header_name);
		$server_header_name = 'HTTP_' . str_replace('-', '_', $header_name);

		if (isset($_SERVER[$server_header_name])) {
			return $_SERVER[$server_header_name];
		} elseif (function_exists('getallheaders')) {
			$myheaders = getallheaders();

			$headers_upper = array_change_key_case($myheaders, CASE_UPPER);
			if (isset($headers_upper[$header_name])) {
				return $headers_upper[$header_name];
			}
		}

		return false;
	}

	public static function get_current_user_id()
	{
		self::include_pluggables('wp_get_current_user');
		return get_current_user_id();
	}

	public static function get_user_by($field, $value)
	{
		self::include_pluggables('get_user_by');

		return get_user_by($field, $value);
	}

	public static function is_user_logged_in()
	{
		self::include_pluggables('is_user_logged_in');

		return is_user_logged_in();
	}

	public static function get_avatar($id, $size)
	{
		self::include_pluggables('get_avatar');

		return get_avatar($id, $size);
	}

	public static function wp_hash_password($password_str)
	{
		self::include_pluggables('wp_hash_password');

		return wp_hash_password($password_str);
	}

	public static function wp_generate_password($length, $special_chars) /*dontTest*/
	{
		self::include_pluggables('wp_generate_password');

		return wp_generate_password($length, $special_chars);
	}

	// Special handling for protocol
	public static function get_permalink($id = 0, $leavename = false)
	{
		$permalink = get_permalink($id, $leavename);

		if (self::is_ssl()) {
			$permalink = preg_replace('!^https?://!', 'https://', $permalink);
		}

		return $permalink;
	}

	public static function get_current_url_without_params()
	{
		return explode('?', $_SERVER['REQUEST_URI'], 2)[0];
	}

	/**
	 * Get account page URL
	 *
	 * @param  WP_Post $post The post object
	 * @return string
	 */
	public static function get_account_url($post = null)
	{
		if (null === $post) {
			global $post;
		}

		// Permalink is empty when set to Plain (default)
		$pretty_permalink = get_option('permalink_structure');

		if (empty($pretty_permalink) && isset($post->ID) && $post->ID > 0) {
			$account_url = BB_Utils::get_permalink($post->ID);
		} else {
			$account_url = BB_Utils::get_current_url_without_params();
		}

		return $account_url;
	}

	public static function wp_redirect($location, $status = 302)
	{
		self::include_pluggables('wp_redirect');

		// Don't cache redirects YO!
		header('Cache-Control: private, no-cache, no-store, max-age=0, must-revalidate, proxy-revalidate');
		// header("Cache-Control: post-check=0, pre-check=0", false);
		header('Pragma: no-cache');
		header('Expires: Fri, 01 Jan 2016 00:00:01 GMT', true); // Some date in the past
		wp_redirect($location, $status);

		exit;
	}

	// Probably shouldn't use this any more to authenticate passwords - see BB_Utils::wp_check_password instead
	public static function wp_authenticate($username, $password)
	{
		self::include_pluggables('wp_authenticate');
		return wp_authenticate($username, $password);
	}

	public static function wp_check_password($user, $password)
	{
		self::include_pluggables('wp_check_password');
		return wp_check_password($password, $user->data->user_pass, $user->ID);
	}

	public static function check_ajax_referer($slug, $param)
	{
		self::include_pluggables('check_ajax_referer');
		return check_ajax_referer($slug, $param);
	}

	public static function include_pluggables($function_name)
	{
		if (!function_exists($function_name)) {
			require_once(ABSPATH . WPINC . '/pluggable.php');
		}
	}

	public static function site_domain()
	{
		return preg_replace('#^https?://(www\.)?([^\?\/]*)#', '$2', get_option('home'));
	}

	public static function is_curl_enabled()
	{
		return function_exists('curl_version');
	}

	public static function is_post_request()
	{
		if (isset($_SERVER['REQUEST_METHOD'])) {
			return (strtolower($_SERVER['REQUEST_METHOD']) == 'post');
		} else {
			return (isset($_POST) && !empty($_POST));
		}
	}

	public static function is_get_request()
	{
		if (isset($_SERVER['REQUEST_METHOD'])) {
			return (strtolower($_SERVER['REQUEST_METHOD']) == 'get');
		} else {
			return (!isset($_POST) || empty($_POST));
		}
	}

	/* Pieces together the current url like a champ */
	public static function request_url()
	{
		$url = (self::is_ssl()) ? 'https://' : 'http://';

		if ($_SERVER['SERVER_PORT'] != '80') {
			$url .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
		} else {
			$url .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		}

		return $url;
	}

	/**
	 * Get the ID of the current screen
	 *
	 * @return string|null
	 */
	public static function get_current_screen_id()
	{
		global $current_screen;

		if ($current_screen instanceof WP_Screen) {
			return $current_screen->id;
		}

		return null;
	}

	/**
	 * Formats and translates a date or time
	 *
	 * @param  string            $format   The format of the returned date
	 * @param  DateTimeInterface $date     The DateTime or DateTimeImmutable instance representing the moment of time in UTC, or null to use the current time
	 * @param  DateTimeZone      $timezone The timezone of the returned date, will default to the WP timezone if omitted
	 * @return string|false                The formatted date or false if there was an error
	 */
	public static function date($format, DateTimeInterface $date = null, DateTimeZone $timezone = null)
	{
		if (!$date) {
			$date = date_create('@' . time());

			if (!$date) {
				return false;
			}
		}

		$timestamp = $date->getTimestamp();

		if ($timestamp === false || !function_exists('wp_date')) {
			$timezone = $timezone ? $timezone : self::get_timezone();
			$date->setTimezone($timezone);

			return $date->format($format);
		}

		return wp_date($format, $timestamp, $timezone);
	}

	/**
	 * Get the WP timezone as a DateTimeZone instance
	 *
	 * Duplicate of wp_timezone() for WP <5.3.
	 *
	 * @return DateTimeZone
	 */
	public static function get_timezone()
	{
		if (function_exists('wp_timezone')) {
			return wp_timezone();
		}

		$timezone_string = get_option('timezone_string');

		if ($timezone_string) {
			return new DateTimeZone($timezone_string);
		}

		$offset  = (float) get_option('gmt_offset');
		$hours   = (int) $offset;
		$minutes = ($offset - $hours);

		$sign      = ($offset < 0) ? '-' : '+';
		$abs_hour  = abs($hours);
		$abs_mins  = abs($minutes * 60);
		$tz_offset = sprintf('%s%02d:%02d', $sign, $abs_hour, $abs_mins);

		return new DateTimeZone($tz_offset);
	}

	/**
	 * Matches each symbol of PHP date format standard
	 * with datepicker format
	 *
	 * @param  string $format php date format
	 * @return string reformatted string
	 */
	public static function datepicker_format($format)
	{
		$supported_options = [
			'd'    => 'dd',  // Day, leading 0
			'j'    => 'd',   // Day, no 0
			'z'    => 'o',   // Day of the year, no leading zeroes,
			// 'D' => 'D',   // Day name short, not sure how it'll work with translations
			'l '   => 'DD ',  // Day name full, idem before
			'l, '  => 'DD, ',  // Day name full, idem before
			'm'    => 'mm',  // Month of the year, leading 0
			'n'    => 'm',   // Month of the year, no leading 0
			// 'M' => 'M',   // Month, Short name
			'F '   => 'MM ',  // Month, full name,
			'F, '  => 'MM, ',  // Month, full name,
			'y'    => 'y',   // Year, two digit
			'Y'    => 'yy',  // Year, full
			'H'    => 'HH',  // Hour with leading 0 (24 hour)
			'G'    => 'H',   // Hour with no leading 0 (24 hour)
			'h'    => 'hh',  // Hour with leading 0 (12 hour)
			'g'    => 'h',   // Hour with no leading 0 (12 hour),
			'i'    => 'mm',  // Minute with leading 0,
			's'    => 'ss',  // Second with leading 0,
			'a'    => 'tt',  // am/pm
			'A'    => 'TT',// AM/PM
		];

		foreach ($supported_options as $php => $js) {
			$format = preg_replace("~(?<!\\\\)$php~", $js, $format);
		}

		$supported_options = [
			'l' => 'DD',  // Day name full, idem before
			'F' => 'MM',  // Month, full name,
		];

		if (isset($supported_options[ $format ])) {
			$format = $supported_options[ $format ];
		}

		$format = preg_replace_callback('~(?:\\\.)+~', [__CLASS__, 'wrap_escaped_chars'], $format);

		return $format;
	}

	/**
	 * Helper function
	 *
	 * @param  $value Value to wrap/escape
	 * @return string Modified value
	 */
	public static function wrap_escaped_chars($value)
	{
		return '&#39;' . str_replace('\\', '', $value[0]) . '&#39;';
	}

	/**
	 * Get the site title (blogname)
	 *
	 * @return string
	 */
	public static function blogname()
	{
		return wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
	}

	/**
	 * Determine whether our Black Friday promotion is active.
	 *
	 * @return boolean
	 */
	public static function is_black_friday_time()
	{
		// Currently runs between November 22 and December 3, 2021
		return time() > strtotime('2021-11-22 00:00:00 America/Denver') && time() < strtotime('2021-12-04 00:00:00 America/Denver');
	}

	/**
	 * Determine whether our promotion is active.
	 *
	 * @return boolean
	 */
	public static function is_promo_time()
	{
		// Start date - end date
		return time() < strtotime('2022-08-30 00:00:00 America/Denver');
	}

	/**
	 * Performs a case-sensitive check indicating if needle is
	 * contained in haystack.
	 *
	 * @param  string $haystack The string to search in.
	 * @param  string $needle   The substring to search for in the `$haystack`.
	 * @return boolean True if `$needle` is in `$haystack`, otherwise false.
	 */
	public static function str_contains($haystack, $needle)
	{
		if ('' === $needle) {
			return true;
		}

		return false !== strpos($haystack, $needle);
	}

	/**
	 * Validate a JSON request
	 *
	 * @param string $nonce_action The nonce action to verify
	 */
	public static function validate_json_request($nonce_action)
	{
		if (!self::is_post_request()) {
			wp_send_json_error(__('Bad request.', 'buddyboss'));
		}

		if (!self::is_logged_in_and_an_admin()) {
			wp_send_json_error(__('Sorry, you don\'t have permission to do this.', 'buddyboss'));
		}

		if (!check_ajax_referer($nonce_action, false, false)) {
			wp_send_json_error(__('Security check failed.', 'buddyboss'));
		}
	}

	/**
	 * Get the request data from a JSON request
	 *
	 * @param  string $nonce_action The nonce action to verify
	 * @return array
	 */
	public static function get_json_request_data($nonce_action)
	{
		self::validate_json_request($nonce_action);

		if (!isset($_POST['data']) || !is_string($_POST['data'])) {
			wp_send_json_error(__('Bad request.', 'buddyboss'));
		}

		$data = json_decode(wp_unslash($_POST['data']), true);

		if (!is_array($data)) {
			wp_send_json_error(__('Bad request.', 'buddyboss'));
		}

		return $data;
	}

	/**
	 * Formats the given content for display.
	 *
	 * Parses blocks, shortcodes and formats paragraphs.
	 *
	 * @param  string $content The content to format.
	 * @return string The formatted content.
	 */
	public static function format_content($content): string
	{
		return do_shortcode(shortcode_unautop(wpautop(do_blocks($content))));
	}

	/**
	 * Sanitize the given name field value.
	 *
	 * @param  string $value
	 * @return string
	 */
	public static function sanitize_name_field($value): string
	{
		$value = sanitize_text_field($value);
		$value = preg_replace('/https?:\/\/\S+/', '', $value);

		return trim($value);
	}

	/**
	 * Ensure the given number $x is between $min and $max inclusive.
	 *
	 * @param  mixed $x
	 * @param  mixed $min
	 * @param  mixed $max
	 * @return mixed
	 */
	public static function clamp($x, $min, $max)
	{
		return min(max($x, $min), $max);
	}
}
