<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Support;

/**
 * Renders view templates from a configured directory with support for overrides
 * from plugins or themes.
 */
class View
{
    /**
     * The path where views are located.
     *
     * @var string
     */
    private string $path;
    /**
     * The filter prefix used to build the template path filter name.
     *
     * @var string
     */
    private string $prefix;
    /**
     * Constructor.
     *
     * @param string $path   The absolute path to the views directory.
     * @param string $prefix The filter prefix (e.g. plugin ID) for template path filters.
     */
    public function __construct(string $path, string $prefix)
    {
        $this->path = \rtrim($path, '/\\');
        $this->prefix = $prefix;
    }
    /**
     * Renders a view and returns the output.
     *
     * @param  string $view The view filename (e.g. 'license-interface.php').
     * @param  array  $vars Variables to extract into the view scope.
     * @return string The rendered output.
     */
    public function render(string $view, array $vars = []) : string
    {
        $path = $this->resolvePath($view);
        if (empty($path)) {
            return '';
        }
        \ob_start();
        \extract($vars, \EXTR_SKIP);
        include $path;
        $output = (string) \ob_get_clean();
        $slug = Str::toSnakeCase(\str_replace(['/', '\\'], '_', \preg_replace('/\\.php$/', '', $view)));
        /**
         * Filters the rendered view output (all views).
         *
         * The dynamic portion of this hook, `${this->prefix}`, refers to the $prefix property
         * passed into the class constructor.
         *
         * @param string $output The rendered output.
         * @param string $view   The view filename.
         * @param array  $vars   Variables passed to the view.
         */
        $output = apply_filters("{$this->prefix}_view_render", $output, $view, $vars);
        /**
         * Filters the rendered view output for a specific view.
         *
         * The dynamic portion of this hook, `${this->prefix}`, refers to the $prefix property
         * passed into the class constructor.
         *
         * The dynamic portion of the hook name, `$slug`, is the view path without extension, in snake_case.
         * For nested views (e.g. `subdir/view-nested.php`), the subdirectory is included (e.g. `subdir_view_nested`).
         *
         * @param string $output The rendered output.
         * @param string $view   The view filename.
         * @param array  $vars   Variables passed to the view.
         */
        $output = apply_filters("{$this->prefix}_view_render_{$slug}", $output, $view, $vars);
        return $output;
    }
    /**
     * Renders a view and echoes the output.
     *
     * @param  string $view The view filename (e.g. 'license-interface.php').
     * @param  array  $vars Variables to extract into the view scope.
     * @return void
     */
    public function output(string $view, array $vars = []) : void
    {
        echo $this->render($view, $vars);
    }
    /**
     * Resolves the view path.
     *
     * @param  string $view The view filename.
     * @return string The resolved path.
     */
    private function resolvePath(string $view) : string
    {
        /**
         * Allows plugins/themes to provide a list of additional override directories
         * where view files can be located.
         *
         * The dynamic portion of this hook, `${this->prefix}`, refers to the $prefix property
         * passed into the class constructor.
         *
         * @param string[] $directories Absolute paths to the available view directories.
         * @param string   $view        The view filename.
         */
        $overrideDirectories = apply_filters("{$this->prefix}_view_override_directories", [], $view);
        $directories = \array_merge($overrideDirectories, [$this->path]);
        foreach ($directories as $directory) {
            $realDir = \realpath($directory);
            $path = \realpath($directory . \DIRECTORY_SEPARATOR . $view);
            if ($realDir && $path && 0 === \strpos($path, $realDir . \DIRECTORY_SEPARATOR)) {
                return $path;
            }
        }
        return '';
    }
}
