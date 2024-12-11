<?php

if (!defined('ABSPATH')) {
	die('You are not allowed to call this page directly.');
}

class BB_Utils
{
	// Maybe this should be in MeprUser?
	public static function get_user_id_by_email($email)
	{
		$user = self::get_user_by('email', $email);
		if (is_object($user)) {
			return $user->ID;
		}

		return '';
	}

	public static function is_image($filename)
	{
		if (!file_exists($filename)) {
			return false;
		}

		$file_meta = @getimagesize($filename); // @ suppress errors if $filename is not an image

		if (!is_array($file_meta)) {
			return false;
		}

		$image_mimes = ['image/gif', 'image/jpeg', 'image/png'];

		return in_array($file_meta['mime'], $image_mimes);
	}

	/**
	 * Looks up month names
	 *
	 * @parameter $abbreviations=false If true then will return month name abbreviations
	 * @parameter $index If false then will return the full array of month names
	 * @parameter $one_based_index If true then will grab the index of the months array as if the index were one based (meaning January = 1
	 * @return    mixed -- an array if $index=false and a string if $index=0-12
	 */
	public static function month_names($abbreviations = true, $index = false, $one_based_index = false)
	{
		if ($abbreviations) {
			$months = [__('Jan', 'buddyboss-platform'), __('Feb', 'buddyboss-platform'), __('Mar', 'buddyboss-platform'), __('Apr', 'buddyboss-platform'), __('May', 'buddyboss-platform'), __('Jun', 'buddyboss-platform'), __('Jul', 'buddyboss-platform'), __('Aug', 'buddyboss-platform'), __('Sept', 'buddyboss-platform'), __('Oct', 'buddyboss-platform'), __('Nov', 'buddyboss-platform'), __('Dec', 'buddyboss-platform')];
		} else {
			$months = [__('January', 'buddyboss-platform'), __('February', 'buddyboss-platform'), __('March', 'buddyboss-platform'), __('April', 'buddyboss-platform'), __('May', 'buddyboss-platform'), __('June', 'buddyboss-platform'), __('July', 'buddyboss-platform'), __('August', 'buddyboss-platform'), __('September', 'buddyboss-platform'), __('October', 'buddyboss-platform'), __('November', 'buddyboss-platform'), __('December', 'buddyboss-platform')];
		}

		if ($index === false) {
			return $months; // No index then return the full array
		}

		$index = $one_based_index ? $index - 1 : $index;

		return $months[$index];
	}

	/**
	 * Convert days to weeks, months or years ... or leave as days.
	 * Eventually we may want to make this more accurate but for now
	 * it's just a quick way to more nicely format the trial days.
	 */
	public static function period_type_from_days($days)
	{
		// maybe Convert to years
		// For now we don't care about leap year
		if ($days % 365 === 0) {
			return ['years', (int) round($days / 365)];
		} elseif ($days % 30 === 0) {
			// not as exact as we'd like but as close as it's gonna get for now
			return ['months', (int) round($days / 30)];
		} elseif ($days % 7 === 0) {
			// Of course this is exact ... easy peasy
			return ['weeks', (int) round($days / 7)];
		} else {
			return ['days', $days];
		}
	}

	public static function period_type_name($period_type, $count = 1)
	{
		$count = (int)$count;

		switch ($period_type) {
			case 'hours':
				return _n('Hour', 'Hours', $count, 'buddyboss-platform');
			case 'days':
				return _n('Day', 'Days', $count, 'buddyboss-platform');
			case 'weeks':
				return _n('Week', 'Weeks', $count, 'buddyboss-platform');
			case 'months':
				return _n('Month', 'Months', $count, 'buddyboss-platform');
			case 'years':
				return _n('Year', 'Years', $count, 'buddyboss-platform');
			default:
				return $period_type;
		}
	}

	public static function rewriting_on()
	{
		$permalink_structure = get_option('permalink_structure');
		return ($permalink_structure and !empty($permalink_structure));
	}

	public static function is_logged_in_and_current_user($user_id)
	{
		$current_user = self::get_currentuserinfo();

		return (self::is_user_logged_in() and (is_object($current_user) && $current_user->ID == $user_id));
	}

	public static function is_logged_in_and_an_admin()
	{
		return (self::is_user_logged_in() and self::is_mepr_admin());
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
			( defined('MEPR_SECURE_PROXY') && // USER must define this in wp-config.php if they're doing HTTPS between the proxy
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
	// Format a protected version of a cc num from the last 4 digits
	public static function cc_num($last4 = '****')
	{
		// If a full cc num happens to get here then it gets reduced to the last4 here
		$last4 = substr($last4, -4);

		return "**** **** **** {$last4}";
	}

	public static function calculate_proration_by_subs($old_sub, $new_sub, $reset_period = false)
	{
		// If no money has changed hands yet then no proration
		if ($old_sub->trial && $old_sub->trial_amount <= 0.00 && $old_sub->txn_count < 1) {
			return (object)[
				'proration' => 0.00,
				'days' => 0,
			];
		}

		// If the subscription has a trial and we're in that first trial payment use trial amount
		// Otherwise use regular price
		if ($old_sub->trial && $old_sub->trial_amount > 0.00 && $old_sub->txn_count == 1) {
			$old_price = $old_sub->trial_amount; // May need to be updated to trial_total when taxes in paid trials feature is in place
		} else {
			$old_price = $old_sub->price;
		}

		$new_price = $new_sub->price;
		$days_in_new_period = $new_sub->days_in_this_period(true);

		if (
			$new_sub->trial && $new_sub->trial_amount > 0.00 ||
			($coupon = $new_sub->coupon()) && ($coupon->discount_mode == 'first-payment' || $coupon->discount_mode == 'trial-override')
		) {
			$new_price = $new_sub->trial_amount;
			$days_in_new_period = $new_sub->trial_days;
		}

		$res = self::calculate_proration(
			$old_price,
			$new_price,
			$old_sub->days_in_this_period(),
			$days_in_new_period,
			$old_sub->days_till_expiration(),
			$reset_period,
			$old_sub,
			$new_sub
		);

		return $res;
	}

	public static function calculate_proration(
		$old_amount,
		$new_amount,
		$old_period = 'lifetime',
		$new_period = 'lifetime',
		$old_days_left = 'lifetime',
		$reset_period = false,
		$old_sub = false, /*These will be false on non auto-recurring*/
		$new_sub = false  /*These will be false on non auto-recurring*/
	) {
		// By default days left in the new sub are equal to the days left in the old
		$new_days_left = $old_days_left;

		if (is_numeric($old_period) && is_numeric($new_period) && $new_sub !== false && $old_amount > 0) {
			// recurring to recurring
			if ($old_days_left > $new_period || $reset_period) {
				// What if the days left exceed the $new_period?
				// And the new outstanding amount is greater?
				// Days left should be reset to the new period
				$new_days_left = $new_period;
			}

			$old_per_day_amount = $old_amount / $old_period;
			$new_per_day_amount = $new_amount / $new_period;

			$old_outstanding_amount = $old_per_day_amount * (int) $old_days_left;
			$new_outstanding_amount = $new_per_day_amount * (int) $new_days_left;

			$proration = $new_outstanding_amount - $old_outstanding_amount;

			$days = $new_days_left;
			if ($proration < 0) {
				$proration = 0;

				if ($new_per_day_amount > 0 && $old_outstanding_amount > 0) {
					$days = $old_outstanding_amount / $new_per_day_amount;
				} else {
					$days = ($new_amount > 0 ? ((abs($proration) + $new_amount) / $new_amount) * $new_days_left : 0);
				}
			}
		} elseif (is_numeric($old_period) && is_numeric($old_days_left) && ($new_period == 'lifetime' || $new_sub === false) && $old_amount > 0) {
			// recurring to lifetime
			// apply outstanding amount to lifetime purchase
			// calculate amount of money left on old sub
			$old_outstanding_amount = (($old_amount / $old_period) * $old_days_left);

			$proration = max($new_amount - $old_outstanding_amount, 0.00);
			$days = 0; // we just do this thing
		} elseif ($old_period == 'lifetime' && is_numeric($new_period) && $old_amount > 0) {
			// lifetime to recurring{
			$proration = max($new_amount - $old_amount, 0.00);
			$days = $new_period; // (is_numeric($old_days_left) && !$reset_period)?$old_days_left:$new_period;
		} elseif ($old_period == 'lifetime' && $new_period == 'lifetime' && $old_amount > 0) {
			// lifetime to lifetime
			$proration = max(($new_amount - $old_amount), 0.00);
			$days = 0; // We be lifetime brah
		} else {
			// Default
			$proration = 0;
			$days = 0;
		}

		// Don't allow amounts that are less than a dollar but greater than zero
		$proration = (($proration > 0.00 && $proration < 1.00) ? 1.00 : $proration);
		$days = ceil($days);
		$proration = self::format_float($proration);

		// Make sure we don't do more than 1 year on days
		if ($days > 365) {
			$days = 365;
		}

		$prorations = (object)compact('proration', 'days');

		return BB_Hooks::apply_filters('mepr-proration', $prorations, $old_amount, $new_amount, $old_period, $new_period, $old_days_left, $old_sub, $new_sub, $reset_period);
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

	public static function get_sub_type($sub)
	{
		if ($sub instanceof MeprSubscription) {
			return 'recurring';
		} elseif ($sub instanceof MeprTransaction) {
			return 'single';
		}

		return false;
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

	public static function render_json($struct, $filename = '', $is_debug = false)
	{
		header('Content-Type: text/json');

		if (!$is_debug and !empty($filename)) {
			header("Content-Disposition: attachment; filename=\"{$filename}.json\"");
		}

		die(json_encode($struct));
	}

	protected function render_xml($struct, $filename = '', $is_debug = false)
	{
		header('Content-Type: text/xml');

		if (!$is_debug and !empty($filename)) {
			header("Content-Disposition: attachment; filename=\"{$filename}.xml\"");
		}

		die(self::to_xml($struct));
	}

	public static function render_csv($struct, $filename = '', $is_debug = false)
	{
		if (!$is_debug) {
			header('Content-Type: text/csv');

			if (!empty($filename)) {
				header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
			}
		}

		header('Content-Type: text/plain');

		die(self::to_csv($struct));
	}

	public static function render_unauthorized($message)
	{
		header('WWW-Authenticate: Basic realm="' . self::blogname() . '"');
		header('HTTP/1.0 401 Unauthorized');
		die(sprintf(__('UNAUTHORIZED: %s', 'buddyboss-platform'), $message));
	}

	/**
	 * The main function for converting to an XML document.
	 * Pass in a multi dimensional array and this recrusively loops through and builds up an XML document.
	 *
	 * @param  array            $data
	 * @param  string           $root_node_name - what you want the root node to be - defaultsto data.
	 * @param  SimpleXMLElement $xml            - should only be used recursively
	 * @return string XML
	 */
	public static function to_xml($data, $root_node_name = 'memberpressData', $xml = null, $parent_node_name = '')
	{
		// turn off compatibility mode as simple xml throws a wobbly if you don't.
		// Deprecated as of PHP 5.3
		// if(ini_get('zend.ze1_compatibility_mode') == 1) {
		// ini_set('zend.ze1_compatibility_mode', 0);
		// }
		if (is_null($xml)) {
			$xml = simplexml_load_string('<?xml version=\'1.0\' encoding=\'utf-8\'?' . '><' . $root_node_name . ' />');
		}

		// loop through the data passed in.
		foreach ($data as $key => $value) {
			// no numeric keys in our xml please!
			if (is_numeric($key)) {
				if (empty($parent_node_name)) {
					$key = 'unknownNode_' . (string)$key; // make string key...
				} else {
					$key = preg_replace('/s$/', '', $parent_node_name); // We assume that there's an 's' at the end of the string?
				}
			}

			// replace anything not alpha numeric
			// $key = preg_replace('/[^a-z]/i', '', $key);
			$key = self::camelcase($key);

			// if there is another array found recrusively call this function
			if (is_array($value)) {
				$node = $xml->addChild($key);
				// recrusive call.
				self::to_xml($value, $root_node_name, $node, $key);
			} else {
				// add single node.
				$value = htmlentities($value);
				$xml->addChild($key, $value);
			}
		}

		// pass back as string. or simple xml object if you want!
		return $xml->asXML();
	}

	/**
	 * Formats an associative array as CSV and returns the CSV as a string.
	 * Can handle nested arrays, headers are named by associative array keys.
	 * Adapted from http://us3.php.net/manual/en/function.fputcsv.php#87120
	 */
	public static function to_csv(
		$struct,
		$delimiter = ',',
		$enclosure = '"',
		$enclose_all = false,
		$telescope = '.',
		$null_to_mysql_null = false
	) {
		$struct = self::deep_convert_to_associative_array($struct);

		if (self::is_associative_array($struct)) {
			$struct = [$struct];
		}

		$csv = '';
		$headers = [];
		$lines = [];

		foreach ($struct as $row) {
			$last_path = ''; // tracking for the header
			$lines[] = self::process_csv_row(
				$row,
				$headers,
				$last_path,
				'',
				$delimiter,
				$enclosure,
				$enclose_all,
				$telescope,
				$null_to_mysql_null
			);
		}

		// Always enclose headers
		$csv .= $enclosure . implode($enclosure . $delimiter . $enclosure, array_keys($headers)) . $enclosure . "\n";

		foreach ($lines as $line) {
			$csv_line = array_merge($headers, $line);
			$csv .= implode($delimiter, array_values($csv_line)) . "\n";
		}

		return $csv;
	}

	/**
	 * Expects an associative array for a row of this data structure. Should
	 * handle nested arrays by telescoping header values with the $telescope arg.
	 */
	private static function process_csv_row(
		$row,
		&$headers,
		&$last_path,
		$path = '',
		$delimiter = ',',
		$enclosure = '"',
		$enclose_all = false,
		$telescope = '.',
		$null_to_mysql_null = false
	) {

		$output = [];

		foreach ($row as $label => $field) {
			$new_path = (empty($path) ? $label : $path . $telescope . $label);

			if (is_null($field) and $null_to_mysql_null) {
				$headers = self::header_insert($headers, $new_path, $last_path);
				$last_path = $new_path;
				$output[$new_path] = 'NULL';

				continue;
			}

			$field = BB_Hooks::apply_filters('mepr_process_csv_cell', $field, $label);

			if (is_array($field)) {
				$output += self::process_csv_row($field, $headers, $last_path, $new_path, $delimiter, $enclosure, $enclose_all, $telescope, $null_to_mysql_null);
			} else {
				$delimiter_esc = preg_quote($delimiter, '/');
				$enclosure_esc = preg_quote($enclosure, '/');
				$headers = self::header_insert($headers, $new_path, $last_path);
				$last_path = $new_path;

				// Enclose fields containing $delimiter, $enclosure or whitespace
				if ($enclose_all or preg_match("/(?:{$delimiter_esc}|{$enclosure_esc}|\s)/", $field)) {
					$output[$new_path] = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure;
				} else {
					$output[$new_path] = $field;
				}
			}
		}

		return $output;
	}

	private static function header_insert($headers, $new_path, $last_path)
	{
		if (!isset($headers[$new_path])) {
			$headers = self::array_insert($headers, $last_path, [$new_path => '']);
		}

		return $headers;
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
			return __('Unknown', 'buddyboss-platform');
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

	/**
	 * Parses a CSV file and returns an associative array
	 */
	public static function parse_csv_file($filepath, $validations = [], $mappings = [])
	{
		$assoc = $headers = [];
		$col_count = 0;
		$row = 1;

		if (($handle = fopen($filepath, 'r')) !== false) {
			$delimiter = self::get_file_delimiter($filepath);
			// Check for BOM - Byte Order Mark
			$bom = "\xef\xbb\xbf";
			// Move pointer to the 4th byte to check if we have a BOM
			if (fgets($handle, 4) !== $bom) {
				// BOM not found - rewind pointer to start of file.
				rewind($handle);
			}
			while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
				if ($row === 1) {
					foreach ($data as $i => $header) {
						if (!empty($header)) {
							if (isset($mappings[$header])) {
								$headers[$i] = $mappings[$header];
							} else {
								$headers[$i] = $header;
							}
						}
						foreach ($validations as $col => $v) {
							if (in_array('required', $v) && !in_array($col, $headers)) {
								throw new Exception(sprintf(__('Your CSV file must contain the column: %s', 'buddyboss-platform')));
							}
						}
					}
					$col_count = count($headers);
				} else {
					if (!self::csv_row_is_blank($data)) {
						$new_row = [];
						for ($i = 0; $i < $col_count; $i++) {
							$new_row[$headers[$i]] = $data[$i];
						}
						foreach ($validations as $col => $v) {
							if (in_array('required', $v) && !in_array($col, $headers)) {
								throw new Exception(sprintf(__('Your CSV file must contain the column: %s', 'buddyboss-platform')));
							}
						}
						$assoc[] = $new_row;
					}
				}
				$row++;
			}
			fclose($handle);
		}

		return $assoc;
	}

	/**
	 * Retrieves the file delimiter based on the first line of the provided CSV file.
	 *
	 * @param  string $filepath The path to the CSV file.
	 * @return string The detected delimiter character.
	 */
	private static function get_file_delimiter($filepath)
	{
		$delimiters = apply_filters(
			'mepr-csv-tax-rate-delimiters',
			[
				';' => 0,
				',' => 0,
				"\t" => 0,
				'|' => 0,
			],
			$filepath
		);

		$handle = fopen($filepath, 'r');

		if ($handle) {
			$first_line = fgets($handle);
			fclose($handle);

			foreach ($delimiters as $delimiter => &$count) {
				$count = count(str_getcsv($first_line, $delimiter));
			}

			if (max($delimiters) > 0) {
				return array_search(max($delimiters), $delimiters);
			}
		}

		return ','; // Default to comma
	}

	private static function csv_row_is_blank($row)
	{
		foreach ($row as $i => $cell) {
			if (!empty($cell)) {
				return false;
			}
		}

		return true;
	}

	public static function countries($prioritize_my_country = true)
	{
		$countries = require(MEPR_I18N_PATH . '/countries.php');

		if ($prioritize_my_country) {
			$country_code = get_option('mepr_biz_country');

			if (!empty($country_code) && isset($countries[$country_code])) {
				$my_country = [$country_code => $countries[$country_code]];
				unset($countries[$country_code]);
				$countries = array_merge($my_country, $countries);
			}
		}

		return BB_Hooks::apply_filters(
			'mepr_countries',
			$countries,
			$prioritize_my_country
		);
	}

	public static function country_name($code)
	{
		$countries = self::countries(false);
		return (isset($countries[$code]) ? $countries[$code] : $code);
	}

	public static function states()
	{
		$states = [];
		$sfiles = @glob(MEPR_I18N_PATH . '/states/[A-Z][A-Z].php', GLOB_NOSORT);
		foreach ($sfiles as $sfile) {
			require($sfile);
		}

		return BB_Hooks::apply_filters(
			'mepr_states',
			$states
		);
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

	public static function load_price_table_css_url()
	{
		return MEPR_SCRIPT_URL . '&action=mepr_load_css&t=price_table';
	}

	public static function locate_by_ip($ip = null, $source = 'geoplugin')
	{
		$ip = (is_null($ip) ? $_SERVER['REMOTE_ADDR'] : $ip);
		if (!self::is_ip($ip)) {
			return false;
		}

		$lockey = 'mp_locate_by_ip_' . md5($ip . $source);
		$loc = get_transient($lockey);

		if (false === $loc) {
			if ($source == 'freegeoip') {
				$url    = "https://freegeoip.net/json/{$ip}";
				$cindex = 'country_code';
				$sindex = 'region_code';
			} else { // geoplugin
				$url    = "http://www.geoplugin.net/json.gp?ip={$ip}";
				$cindex = 'geoplugin_countryCode';
				$sindex = 'geoplugin_regionCode';
			}

			$res = wp_remote_get($url);
			$obj = json_decode($res['body']);

			$state = (isset($obj->{$sindex}) ? $obj->{$sindex} : '');
			$country = (isset($obj->{$cindex}) ? $obj->{$cindex} : '');

			// If the state is goofy then just blank it out
			if (file_exists(MEPR_I18N_PATH . '/states/' . $country . '.php')) {
				$states = [];
				require(MEPR_I18N_PATH . '/states/' . $country . '.php');
				if (!isset($states[$country][$state])) {
					$state = '';
				}
			}

			$loc = (object)compact('state', 'country');
			set_transient($lockey, $loc, DAY_IN_SECONDS);
		}

		return $loc;
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
		error_log(sprintf(__('*** MemberPress Error: %s', 'buddyboss-platform'), $error));
	}

	public static function debug_log($message)
	{
		// Getting some complaints about using WP_DEBUG here
		if (defined('WP_MEPR_DEBUG') && WP_MEPR_DEBUG) {
			error_log(sprintf(__('*** MemberPress Debug: %s', 'buddyboss-platform'), $message));
		}
	}

	public static function is_wp_error($obj)
	{
		if (is_wp_error($obj)) {
			self::error_log($obj->get_error_message());
			return true;
		}

		return false;
	}

	/**
	 * EMAIL NOTICE METHODS
	 **/
	public static function send_notices($obj, $user_class = null, $admin_class = null, $force = false)
	{
		if ($obj instanceof MeprSubscription) {
			$params = MeprSubscriptionsHelper::get_email_params($obj);
		} elseif ($obj instanceof MeprTransaction) {
			$params = MeprTransactionsHelper::get_email_params($obj);
		} else {
			return false;
		}

		$usr = $obj->user();
		$disable_email = BB_Hooks::apply_filters('mepr_send_email_disable', false, $obj, $user_class, $admin_class);

		try {
			if (!is_null($user_class) && false == $disable_email) {
				$uemail = MeprEmailFactory::fetch($user_class);
				$uemail->to = $usr->formatted_email();

				if ($force) {
					$uemail->send($params);
				} else {
					$uemail->send_if_enabled($params);
				}
			}

			if (!is_null($admin_class) && false == $disable_email) {
				$aemail = MeprEmailFactory::fetch($admin_class);

				if ($force) {
					$aemail->send($params);
				} else {
					$aemail->send_if_enabled($params);
				}
			}
		} catch (Exception $e) {
			// Fail silently for now
		}
	}

	public static function send_signup_notices($txn, $force = false, $send_admin_notices = true)
	{
		$admin_one_off_class = ($send_admin_notices ? 'MeprAdminNewOneOffEmail' : null);
		$admin_class = ($send_admin_notices ? 'MeprAdminSignupEmail' : null);
		$user = $txn->user();

		$prd_sent = self::maybe_send_product_welcome_notices($txn, $user, false);

		// If this is a one-off send that email too
		if (empty($txn->subscription_id)) {
			self::send_notices($txn, null, $admin_one_off_class, $force);
		}

		// Send New Signup Emails?
		if (!$user->signup_notice_sent) {
			// Don't send the MemberPress Welcome Email if the Product Welcome Email was sent instead
			if ($prd_sent) {
				self::send_notices($txn, null, $admin_class, $force);
			} else {
				self::send_notices($txn, 'MeprUserWelcomeEmail', $admin_class, $force);
			}

			$user->signup_notice_sent = true;
			$user->store();

			// Maybe move this to the bottom of this method outside of an if statement?
			// Not sure if this should happen on each new signup, or only on a member's first signup
			MeprEvent::record('member-signup-completed', $user, (object)$txn->rec); // have to use ->rec here for some reason
		}
	}

	public static function send_new_sub_notices($sub)
	{
		self::send_notices($sub, null, 'MeprAdminNewSubEmail');
	}

	public static function send_transaction_receipt_notices($txn)
	{
		/**
		 * TODO: These events should probably be moved ... but
		 * 'tis very convenient to put them here for now.
		 */
		MeprEvent::record('transaction-completed', $txn);

		// This is a recurring payment
		if (($sub = $txn->subscription())) {
			MeprEvent::record('recurring-transaction-completed', $txn);

			if ($sub->txn_count > 1) {
				MeprEvent::record('renewal-transaction-completed', $txn);
			}
		} elseif (!$sub) {
			MeprEvent::record('non-recurring-transaction-completed', $txn);
		}

		self::send_notices(
			$txn,
			'MeprUserReceiptEmail',
			'MeprAdminReceiptEmail'
		);
	}

	public static function send_suspended_sub_notices($sub)
	{
		self::send_notices(
			$sub,
			'MeprUserSuspendedSubEmail',
			'MeprAdminSuspendedSubEmail'
		);
		MeprEvent::record('subscription-paused', $sub);
	}

	public static function send_resumed_sub_notices($sub, $event_args = '')
	{
		self::send_notices(
			$sub,
			'MeprUserResumedSubEmail',
			'MeprAdminResumedSubEmail'
		);
		MeprEvent::record('subscription-resumed', $sub, $event_args);
	}

	public static function send_cancelled_sub_notices($sub)
	{
		self::send_notices(
			$sub,
			'MeprUserCancelledSubEmail',
			'MeprAdminCancelledSubEmail'
		);
		MeprEvent::record('subscription-stopped', $sub);
	}

	public static function send_upgraded_txn_notices($txn)
	{
		self::send_upgraded_sub_notices($txn);
	}

	public static function send_upgraded_sub_notices($sub)
	{
		self::send_notices(
			$sub,
			'MeprUserUpgradedSubEmail',
			'MeprAdminUpgradedSubEmail'
		);
	}

	public static function record_upgraded_sub_events($obj, $event_txn)
	{
		MeprEvent::record('subscription-upgraded', $obj);

		if ($event_txn instanceof MeprTransaction) {
			MeprEvent::record('subscription-changed', $event_txn, $obj->first_txn_id); // first_txn_id works best here for Corporate Accounts
		}

		if ($obj instanceof MeprTransaction) {
			MeprEvent::record('subscription-upgraded-to-one-time', $obj);
		} else {
			MeprEvent::record('subscription-upgraded-to-recurring', $obj);
		}
	}

	public static function send_downgraded_txn_notices($txn)
	{
		self::send_downgraded_sub_notices($txn);
	}

	public static function send_downgraded_sub_notices($sub)
	{
		self::send_notices(
			$sub,
			'MeprUserDowngradedSubEmail',
			'MeprAdminDowngradedSubEmail'
		);
	}

	public static function record_downgraded_sub_events($obj, $event_txn)
	{
		MeprEvent::record('subscription-downgraded', $obj);

		if ($event_txn instanceof MeprTransaction) {
			MeprEvent::record('subscription-changed', $event_txn, $obj->first_txn_id); // first_txn_id works best here for Corporate Accounts
		}

		if ($obj instanceof MeprTransaction) {
			MeprEvent::record('subscription-downgraded-to-one-time', $obj);
		} else {
			MeprEvent::record('subscription-downgraded-to-recurring', $obj);
		}
	}

	public static function send_refunded_txn_notices($txn)
	{
		self::send_notices(
			$txn,
			'MeprUserRefundedTxnEmail',
			'MeprAdminRefundedTxnEmail'
		);
		MeprEvent::record('transaction-refunded', $txn);

		// This is a recurring payment
		if (($sub = $txn->subscription()) && $sub->txn_count > 0) {
			MeprEvent::record('recurring-transaction-refunded', $txn);
		}
	}

	public static function send_failed_txn_notices($txn)
	{
		self::send_notices(
			$txn,
			'MeprUserFailedTxnEmail',
			'MeprAdminFailedTxnEmail'
		);

		MeprEvent::record('transaction-failed', $txn);

		// This is a recurring payment
		if (($sub = $txn->subscription()) && $sub->txn_count > 0) {
			MeprEvent::record('recurring-transaction-failed', $txn);
		}
	}

	public static function send_cc_expiration_notices($txn)
	{
		$sub = $txn->subscription();

		if (
			$sub instanceof MeprSubscription &&
			$sub->cc_expiring_before_next_payment()
		) {
			self::send_notices(
				$sub,
				'MeprUserCcExpiringEmail',
				'MeprAdminCcExpiringEmail'
			);
		}
	}

	public static function maybe_send_product_welcome_notices($txn, $user, $force_global = true)
	{
		$sent = false;

		try {
			$params = MeprTransactionsHelper::get_email_params($txn);
			$uemail = MeprEmailFactory::fetch(
				'MeprUserProductWelcomeEmail',
				'MeprBaseProductEmail',
				[
					[
						'product_id' => $txn->product_id,
					],
				]
			);
			$uemail->to = $user->formatted_email();

			if (isset($uemail->product->emails['MeprUserProductWelcomeEmail'])) {
				$email = $uemail->product->emails['MeprUserProductWelcomeEmail'];
				if ($email['enabled']) {
					// Don't resend the product welcome email if the subscription is resumed
					if ($txn->subscription_id > 0 && MeprEvent::get_count_by_event_and_evt_id_and_evt_id_type('subscription-resumed', $txn->subscription_id, 'subscriptions') > 0) {
						return false;
					}

					$uemail->send(
						$params,
						stripslashes($email['subject']),
						stripslashes($email['body']),
						$email['use_template']
					);

					$sent = true;
				} elseif ($force_global) { // Send global Welcome
					$uemail = MeprEmailFactory::fetch('MeprUserWelcomeEmail');
					$uemail->to = $user->formatted_email();
					$uemail->send($params);
					$sent = true;
				}
			}
		} catch (Exception $e) {
			// Fail silently for now
		}

		return $sent;
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

	public static function maybe_wpautop($text)
	{
		$wpautop_disabled = get_option('mepr_wpautop_disable_for_emails');

		if ($wpautop_disabled) {
			return $text;
		}

		return wpautop($text);
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

	/**
	 * Verifies that a url parameter exists and optionally that it contains a certain value.
	 */
	public static function valid_url_param($name, $value = null, $method = null)
	{
		$params = $_REQUEST;
		if (!empty($method)) {
			$method = strtoupper($method);

			if ($method == 'GET') {
				$params = $_GET;
			} elseif ($method == 'POST') {
				$params = $_POST;
			}
		}

		$verified = isset($params[$name]);

		if ($verified && !empty($value)) {
			$verified = ($params[$name] == $value);
		}

		return $verified;
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
		$delim = MeprUtils::get_delim($path);

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
	 * This returns the structure for all of the gateway notify urls.
	 * It can even account for folks unlucky enough to have to prepend
	 * their URLs with '/index.php'.
	 * NOTE: This function is only applicable if pretty permalinks are enabled.
	 */
	public static function gateway_notify_url_structure()
	{
		$pre_slug_index = '';
		if (self::pretty_permalinks_using_index()) {
			$pre_slug_index = '/index.php';
		}

		return BB_Hooks::apply_filters(
			'mepr_gateway_notify_url_structure',
			"{$pre_slug_index}/mepr/notify/%gatewayid%/%action%"
		);
	}

	/**
	 * This modifies the gateway notify url structure to be matched against a uri.
	 * By default it will generate this: /mepr/notify/([^/\?]+)/([^/\?]+)/?
	 * However, this could change depending on what gateway_notify_url_structure returns
	 */
	public static function gateway_notify_url_regex_pattern()
	{
		return preg_replace('!(%gatewayid%|%action%)!', '([^/\?]+)', self::gateway_notify_url_structure()) . '/?';
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

	/* PLUGGABLE FUNCTIONS AS TO NOT STEP ON OTHER PLUGINS' CODE */
	public static function get_currentuserinfo()
	{
		self::include_pluggables('wp_get_current_user');
		$current_user = wp_get_current_user();

		if (isset($current_user->ID) && $current_user->ID > 0) {
			return new MeprUser($current_user->ID);
		} else {
			return false;
		}
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

	// Just sends to the emails configured in the plugin settings
	public static function wp_mail_to_admin($subject, $message, $headers = '')
	{
		$mepr_options = MeprOptions::fetch();
		$recipient = $mepr_options->admin_email_addresses;
		self::wp_mail($recipient, $subject, $message, $headers);
	}

	public static function wp_mail($recipient, $subject, $message, $headers = '', $attachments = [])
	{
		self::include_pluggables('wp_mail');

		// Parse shortcodes in the message body
		$message = do_shortcode($message);

		add_filter('wp_mail_from_name', 'MeprUtils::set_mail_from_name');
		add_filter('wp_mail_from', 'MeprUtils::set_mail_from_email');
		add_action('phpmailer_init', 'MeprUtils::reset_alt_body', 5);

		// We just send individual emails
		$recipients = explode(',', $recipient);
		$recipients = BB_Hooks::apply_filters('mepr-wp-mail-recipients', $recipients, $subject, $message, $headers);
		$subject    = BB_Hooks::apply_filters('mepr-wp-mail-subject', $subject, $recipients, $message, $headers);
		$message    = BB_Hooks::apply_filters('mepr-wp-mail-message', $message, $recipients, $subject, $headers);
		$headers    = BB_Hooks::apply_filters('mepr-wp-mail-headers', $headers, $recipients, $subject, $message, $attachments);

		BB_Hooks::do_action('mepr_before_send_email', $recipients, $subject, $message, $headers, $attachments);

		foreach ($recipients as $to) {
			$to = trim($to);

			// TEMP FIX TO AVOID ALL THE SENDGRID ISSUES WE'VE BEEN SEEING IN SUPPORT LATELY
			// Let's get rid of the pretty TO's -- causing too many problems
			// mbstring?
			if (extension_loaded('mbstring')) {
				if (mb_strpos($to, '<') !== false) {
					$to = mb_substr($to, (mb_strpos($to, '<') + 1), -1);
				}
			} else {
				if (strpos($to, '<') !== false) {
					$to = substr($to, (strpos($to, '<') + 1), -1);
				}
			}

			wp_mail($to, $subject, $message, $headers, $attachments);

			// Just leaving these here as I need to debug this shiz enough, it would save me some time
			/*
				global $phpmailer;
				var_dump($phpmailer);
			*/
		}

		BB_Hooks::do_action('mepr_after_send_email', $recipients, $subject, $message, $headers, $attachments);

		remove_action('phpmailer_init', 'MeprUtils::reset_alt_body', 5);
		remove_filter('wp_mail_from', 'MeprUtils::set_mail_from_name');
		remove_filter('wp_mail_from_name', 'MeprUtils::set_mail_from_email');
	}

	public static function set_mail_from_name($name)
	{
		$mepr_options = MeprOptions::fetch();

		return $mepr_options->mail_send_from_name;
	}

	public static function set_mail_from_email($email)
	{
		$mepr_options = MeprOptions::fetch();

		return $mepr_options->mail_send_from_email;
	}

	/**
	 * Make sure to reset the AltBody or it can contain remnants of other emails already sent in the same request
	 *
	 * @param PHPMailer $phpmailer
	 */
	public static function reset_alt_body($phpmailer)
	{
		$phpmailer->AltBody = '';
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
			$account_url = MeprUtils::get_permalink($post->ID);
		} else {
			$account_url = MeprUtils::get_current_url_without_params();
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

	// Probably shouldn't use this any more to authenticate passwords - see MeprUtils::wp_check_password instead
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

	public static function login_url()
	{
		// These funcs are thin wrappers for WP funcs, no need to test.
		$mepr_options = MeprOptions::fetch();

		if ($mepr_options->login_page_id > 0) {
			return $mepr_options->login_page_url();
		} else {
			return wp_login_url($mepr_options->account_page_url());
		}
	}

	public static function logout_url()
	{
		return BB_Hooks::apply_filters('mepr-logout-url', wp_logout_url(self::login_url()));
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

	public static function get_formatted_usermeta($user_id)
	{
		$mepr_options = MeprOptions::fetch();
		$ums = get_user_meta($user_id);
		$new_ums = [];
		$return_ugly_val = BB_Hooks::apply_filters('mepr-return-ugly-usermeta-vals', false);

		if (!empty($ums)) {
			foreach ($ums as $umkey => $um) {
				// Only support first val for now and yes some of these will be serialized values
				$val = maybe_unserialize($um[0]);
				$strval = $val;

				if (is_array($val)) { // Handle array type custom fields like multi-select, checkboxes etc we'll unsanitize the vals
					if (!empty($val)) {
						foreach ($val as $i => $k) {
							if (is_int($i)) { // Multiselects (indexed array)
								if (!$return_ugly_val) {
									$k = self::unsanitize_title($k);
								}
								$strval = (is_array($strval)) ? "{$k}" : $strval . ", {$k}";
							} else { // Checkboxes (associative array)
								if (!$return_ugly_val) {
									$i = self::unsanitize_title($i);
								}
								$strval = (is_array($strval)) ? "{$i}" : $strval . ", {$i}";
							}
						}
					} else { // convert empty array to empty string
						$strval = '';
					}
				} elseif ($val == 'on') { // Single checkbox
					$strval = _x('Checked', 'ui', 'buddyboss-platform');
				} elseif ($return_ugly_val) { // Return the ugly value
					$strval = $val;
				} else { // We need to check for checkboxes and radios and match them up with MP custom fields
					$mepr_field = $mepr_options->get_custom_field($umkey);

					if (!is_null($mepr_field) && !empty($mepr_field->options)) {
						foreach ($mepr_field->options as $option) {
							if ($option->option_value == $val) {
								$strval = stripslashes($option->option_name);
								break; // Found a match, so stop here
							}
						}
					}
				}

				$new_ums["{$umkey}"] = $strval;
			}
		}

		return $new_ums;
	}

	// purely for backwards compatibility (deprecated)
	public static function send_admin_signup_notification($params)
	{
		$txn = MeprTransaction::get_one_by_trans_num($params['trans_num']);
		$txn = new MeprTransaction($txn->id);
		$params = MeprTransactionsHelper::get_email_params($txn); // Yeah, re-set these
		$usr = $txn->user();

		try {
			$aemail = MeprEmailFactory::fetch('MeprAdminSignupEmail');
			$aemail->send($params);
		} catch (Exception $e) {
			// Fail silently for now
		}
	}

	public static function send_user_signup_notification($params)
	{
		$txn = MeprTransaction::get_one_by_trans_num($params['trans_num']);
		$txn = new MeprTransaction($txn->id);
		$params = MeprTransactionsHelper::get_email_params($txn); // Yeah, re-set these
		$usr = $txn->user();

		try {
			$uemail = MeprEmailFactory::fetch('MeprUserWelcomeEmail');
			$uemail->to = $usr->formatted_email();
			$uemail->send($params);
		} catch (Exception $e) {
			// Fail silently for now
		}
	}

	public static function send_user_receipt_notification($params)
	{
		$txn = MeprTransaction::get_one_by_trans_num($params['trans_num']);
		$txn = new MeprTransaction($txn->id);
		$params = MeprTransactionsHelper::get_email_params($txn); // Yeah, re-set these
		$usr = $txn->user();

		try {
			$uemail = MeprEmailFactory::fetch('MeprUserReceiptEmail');
			$uemail->to = $usr->formatted_email();
			$uemail->send($params);

			$aemail = MeprEmailFactory::fetch('MeprAdminReceiptEmail');
			$aemail->send($params);
		} catch (Exception $e) {
			// Fail silently for now
		}
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
	 * Get the edition data from a product slug
	 *
	 * @param  string $product_slug
	 * @return array|null
	 */
	public static function get_edition($product_slug)
	{
		$editions = [
			[
				'index' => 0,
				'slug' => 'business',
				'name' => 'MemberPress Business',
			],
			[
				'index' => 1,
				'slug' => 'memberpress-basic',
				'name' => 'MemberPress Basic',
			],
			[
				'index' => 2,
				'slug' => 'memberpress-plus',
				'name' => 'MemberPress Plus',
			],
			[
				'index' => 3,
				'slug' => 'memberpress-plus-2',
				'name' => 'MemberPress Plus',
			],
			[
				'index' => 4,
				'slug' => 'developer',
				'name' => 'MemberPress Developer',
			],
			[
				'index' => 5,
				'slug' => 'memberpress-pro',
				'name' => 'MemberPress Pro',
			],
			[
				'index' => 6,
				'slug' => 'memberpress-pro-5',
				'name' => 'MemberPress Pro',
			],
			[
				'index' => 7,
				'slug' => 'memberpress-reseller',
				'name' => 'MemberPress Reseller',
			],
			[
				'index' => 8,
				'slug' => 'memberpress-oem',
				'name' => 'MemberPress OEM',
			],
			[
				'index' => 9,
				'slug' => 'memberpress-elite',
				'name' => 'MemberPress Elite',
			],
		];

		if (preg_match('/^memberpress-reseller-.+$/', $product_slug)) {
			$editions[7]['slug'] = $product_slug;
		}

		foreach ($editions as $edition) {
			if ($product_slug == $edition['slug']) {
				return $edition;
			}
		}

		return null;
	}

	/**
	 * Is the installed edition of MemberPress different from the edition in the license?
	 *
	 * @return array|false An array containing the installed edition and license edition data, false if the correct edition is installed
	 */
	public static function is_incorrect_edition_installed()
	{
		$license = get_site_transient('mepr_license_info');
		$license_product_slug = !empty($license) && !empty($license['product_slug']) ? $license['product_slug'] : '';

		if (
			empty($license_product_slug) ||
			empty(MEPR_EDITION) ||
			$license_product_slug == MEPR_EDITION ||
			!current_user_can('update_plugins') ||
			@is_dir(MEPR_PATH . '/.git')
		) {
			return false;
		}

		$installed_edition = self::get_edition(MEPR_EDITION);
		$license_edition = self::get_edition($license_product_slug);

		if (!is_array($installed_edition) || !is_array($license_edition)) {
			return false;
		}

		return [
			'installed' => $installed_edition,
			'license' => $license_edition,
		];
	}

	/**
	 * Is the given product slug a Pro edition of MemberPress?
	 *
	 * @param  string $product_slug
	 * @return boolean
	 */
	public static function is_pro_edition($product_slug)
	{
		if (empty($product_slug)) {
			return false;
		}

		return in_array($product_slug, ['memberpress-pro', 'memberpress-pro-5'], true) || MeprUtils::is_oem_edition($product_slug);
	}

	/**
	 * Is the given product slug an OEM/reseller edition of MemberPress?
	 *
	 * @param  string $product_slug
	 * @return boolean
	 */
	public static function is_oem_edition($product_slug)
	{
		if (empty($product_slug)) {
			return false;
		}

		return $product_slug == 'memberpress-oem' || preg_match('/^memberpress-reseller-.+$/', $product_slug);
	}

	/**
	 * Determines whether or not the provided gateway is connected.
	 *
	 * @param  object $gateway
	 * @return boolean
	 */
	public static function is_gateway_connected($gateway)
	{
		if (!is_object($gateway) || !isset($gateway->key)) {
			return false;
		}

		// Payment gateways such as Authorize.net and PayPal Standard require fields to be filled out,
		// so we won't worry about validating them here.
		switch ($gateway->key) {
			case 'stripe':
				return (MeprStripeGateway::is_stripe_connect($gateway->id) || MeprStripeGateway::keys_are_set($gateway->id));
			case 'paypalcommerce':
				return ($gateway->is_paypal_connected() || $gateway->is_paypal_connected_live());
			default:
				return true;
		}
	}

	/**
	 * Returns the minimum charge amount for the currently configured currency, or false if there is no minimum.
	 *
	 * We are aligning with the Stripe minimum charge amounts from:
	 * https://stripe.com/docs/currencies#minimum-and-maximum-charge-amounts
	 *
	 * @return float|integer|false
	 */
	public static function get_minimum_amount()
	{
		static $minimum_amount;

		if ($minimum_amount === null) {
			$mepr_options = MeprOptions::fetch();
			$currency_code = strtoupper(BB_Hooks::apply_filters('mepr_minimum_charge_currency', $mepr_options->currency_code));

			$minimums = require MEPR_DATA_PATH . '/minimum_charge_amounts.php';
			$minimum_amount = isset($minimums[$currency_code]) ? $minimums[$currency_code] : false;
		}

		return $minimum_amount;
	}

	/**
	 * Returns the minimum amount if the given amount is above zero and below the minimum charge amount.
	 *
	 * @param  string|float|integer $amount
	 * @return string|float|integer
	 */
	public static function maybe_round_to_minimum_amount($amount)
	{
		if ($amount > 0) {
			$minimum_amount = self::get_minimum_amount();

			if ($minimum_amount && $amount < $minimum_amount) {
				$amount = $minimum_amount;
			}
		}

		return $amount;
	}

	/**
	 * Get the HTML for the 'NEW' badge
	 *
	 * @return string
	 */
	public static function new_badge()
	{
		return sprintf('<span class="mepr-new-badge">%s</span>', esc_html__('NEW', 'buddyboss-platform'));
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
	 * Is the given product slug an Elite edition of MemberPress?
	 *
	 * @param  string $product_slug
	 * @return boolean
	 */
	public static function is_elite_edition($product_slug)
	{
		if (empty($product_slug)) {
			return false;
		}

		return in_array($product_slug, ['memberpress-elite'], true);
	}

	/**
	 * Validate a JSON request
	 *
	 * @param string $nonce_action The nonce action to verify
	 */
	public static function validate_json_request($nonce_action)
	{
		if (!self::is_post_request()) {
			wp_send_json_error(__('Bad request.', 'buddyboss-platform'));
		}

		if (!self::is_logged_in_and_an_admin()) {
			wp_send_json_error(__('Sorry, you don\'t have permission to do this.', 'buddyboss-platform'));
		}

		if (!check_ajax_referer($nonce_action, false, false)) {
			wp_send_json_error(__('Security check failed.', 'buddyboss-platform'));
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
			wp_send_json_error(__('Bad request.', 'buddyboss-platform'));
		}

		$data = json_decode(wp_unslash($_POST['data']), true);

		if (!is_array($data)) {
			wp_send_json_error(__('Bad request.', 'buddyboss-platform'));
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
