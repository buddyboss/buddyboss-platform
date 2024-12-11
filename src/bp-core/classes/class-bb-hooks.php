<?php

if (!defined('ABSPATH')) {
	die('You are not allowed to call this page directly.');
}

/**
 * Helper methods for working with hooks in MemberPress
 */
class BB_Hooks
{
	public static function do_action($tag, $arg = '')
	{
		return self::call(__FUNCTION__, $tag, func_get_args());
	}

	public static function do_action_ref_array($tag, $args)
	{
		return self::call(__FUNCTION__, $tag, func_get_args());
	}

	public static function apply_filters($tag, $value)
	{
		return self::call(__FUNCTION__, $tag, func_get_args(), 'filter');
	}

	public static function apply_filters_ref_array($tag, $args)
	{
		return self::call(__FUNCTION__, $tag, func_get_args(), 'filter');
	}

	public static function add_shortcode($tag, $callback)
	{
		return self::call(__FUNCTION__, $tag, func_get_args(), 'shortcode');
	}

	private static function call($fn, $tag, $args, $type = 'action')
	{
		$tags = self::tags($tag);

		foreach ($tags as $t) {
			$args[0] = $t;

			if ($type === 'filter') {
				$args[1] = call_user_func_array($fn, $args);
			} else {
				call_user_func_array($fn, $args);
			}
		}

		if ($type === 'filter') {
			return $args[1];
		}
	}

	// We love dashes and underscores ... we just can't choose which we like better :)
	private static function tags($tag)
	{
		// Prepend mepr if it doesn't exist already
		if (!preg_match('/^mepr[-_]/i', $tag)) {
			$tag = 'mepr_' . $tag;
		}

		$tags = [
			'-' => preg_replace('/[-_]/', '-', $tag),
			'_' => preg_replace('/[-_]/', '_', $tag),
		];

		// in case the original tag has mixed dashes and underscores
		if (!in_array($tag, array_values($tags))) {
			$tags['*'] = $tag;
		}

		return $tags;
	}
}
