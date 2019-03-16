<?php
namespace BuddyBoss\Integrations\Includes;

use BuddyBoss\Integrations\Includes\BbmsHooks;

define('BP_VIEWS_PATH', dirname(__FILE__) . '/../views/');

class BbmsView {

	public static function file($slug, $paths = array()) {
		$paths = (empty($paths) ? self::paths() : $paths);

		$find = $slug . '.php';

		if (!preg_match('#^/#', $find)) {$find = '/' . $find;}

		// Since we're including our default path, the file will be
		// found so we're not doing any validation for that here
		foreach ($paths as $path) {
			if (file_exists($path . $find)) {
				return $path . $find;
			}
		}

		return false;
	}

	/** Used to get a string of a view. We can use this when calling a file to
	 * pass all the locally defined variables as the args variable:
	 *
	 * BbmsView::get_string('mycoolstuff/what', get_defined_vars());
	 *
	 */
	public static function get_string($slug, $vars = array(), $paths = array()) {
		$paths = BbmsHooks::apply_filters('bbms_view_paths_get_string_' . $slug, $paths, $slug, $vars);
		$paths = BbmsHooks::apply_filters('bbms_view_paths_get_string', $paths, $slug, $vars);

		// $template_part_slug = 'buddyboss-memberships/' . dirname($slug);
		// $template_part_name = basename($slug);

		// do_action("get_template_part_{$template_part_slug}", $template_part_slug, $template_part_name); // bypass MeprHooks for this one

		extract($vars, EXTR_SKIP);

		$file = self::file($slug, $paths);
		error_log(print_r($paths, true));

		if (!$file) {return;}

		ob_start();
		require $file;
		$view = ob_get_clean();

		$view = BbmsHooks::apply_filters('bbms_view_get_string_' . $slug, $view, $vars); // Slug specific filter
		$view = BbmsHooks::apply_filters('bbms_view_get_string', $view, $slug, $vars); // General filter

		return $view;
	}

	/** Used to render a view. We can use this when calling a file to
	 * pass all the locally defined variables as the args variable:
	 *
	 * MeprView::render('mycoolstuff/what', get_defined_vars());
	 *
	 */
	public static function render($slug, $vars = array(), $paths = array()) {
		$view = self::get_string($slug, $vars, $paths);
		echo $view;

		return $view;
	}

	public static function paths() {
		$paths = array();

		$paths[] = BP_VIEWS_PATH;

		return BbmsHooks::apply_filters('bbms_view_paths', $paths);
	}}
