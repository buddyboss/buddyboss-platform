<?php

function bp_learndash_path($path = '') {
    return trailingslashit( buddypress()->integrations['learndash']->path ) . trim($path, '/\\');
}

function bp_learndash_url($path = '') {
    return trailingslashit( buddypress()->integrations['learndash']->url ) . trim($path, '/\\');
}

function bp_ld_sync($component = null) {
	global $bp_ld_sync;
	return $component? $bp_ld_sync->$component : $bp_ld_sync;
}

// forward competibility
if (! function_exists('learndash_get_post_type_slug')) {
	function learndash_get_post_type_slug($type) {
		$postTypes = [
			'course'       => 'sfwd-courses',
			'lesson'       => 'sfwd-lessons',
			'topic'        => 'sfwd-topic',
			'quiz'         => 'sfwd-quiz',
			'question'     => 'sfwd-question',
			'transactions' => 'sfwd-transactions',
			'group'        => 'groups',
			'assignment'   => 'sfwd-assignment',
			'essays'       => 'sfwd-essays',
			'certificates' => 'sfwd-certificates',
		];

		return $postTypes[$type];
	}
}
