<?php
namespace BuddyBoss\Integrations\Includes;

class BbmsHooks {
	public static function do_action($tag, $arg = '') {
		$args = func_get_args();
		$tags = self::tags($tag);

		foreach ($tags as $t) {
			$args[0] = $t;
			call_user_func_array('do_action', $args);
		}
	}

	public static function do_action_ref_array($tag, $args) {
		$args = func_get_args();
		$tags = self::tags($tag);

		foreach ($tags as $t) {
			$args[0] = $t;
			call_user_func_array('do_action_ref_array', $args);
		}
	}

	public static function apply_filters($tag, $value) {
		$args = func_get_args();
		$tags = self::tags($tag);

		foreach ($tags as $t) {
			$args[0] = $t;
			$args[1] = call_user_func_array('apply_filters', $args);
		}

		return $args[1];
	}

	public static function apply_filters_ref_array($tag, $args) {
		$args = func_get_args();
		$tags = self::tags($tag);

		foreach ($tags as $t) {
			$args[0] = $t;
			$args[1] = call_user_func_array('apply_filters_ref_array', $args);
		}

		return $args[1];
	}

	// We love dashes and underscores ... we just can't choose which we like better :)
	private static function tags($tag) {
		// Prepend bbms if it doesn't exist already
		if (!preg_match('/^bbms[-_]/i', $tag)) {
			$tag = 'bbms' . $tag;
		}

		$tags = array(
			'-' => preg_replace('/[-_]/', '-', $tag),
			'_' => preg_replace('/[-_]/', '_', $tag),
		);

		// in case the original tag has mixed dashes and underscores
		if (!in_array($tag, array_values($tags))) {
			$tags['*'] = $tag;
		}

		return $tags;
	}
}
