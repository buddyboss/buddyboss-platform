<?php

class LearnDash_BuddyPress_Groups_Sync
{
    protected static $instance;
    protected $root_file;
    protected $version;

    protected function __construct($root_file, $version)
    {
        $this->root_file = $root_file;
        $this->version   = $version;

        add_action('plugins_loaded', [$this, 'load_languages']);
        add_action('plugins_loaded', [$this, 'register_hooks']);
    }

    public static function instance($root_file, $version)
    {
        if (! static::$instance) {
            static::$instance = new static($root_file, $version);
        }

        return static::$instance;
    }

    public function register_hooks() {
        $this->admin        = $this->load('includes/class-admin.php');
        $this->requirement  = $this->load('includes/class-requirement.php');
        $this->learndash    = $this->load('includes/class-learndash.php');
        $this->buddypress   = $this->load('includes/class-buddypress.php');
        $this->groups       = $this->load('includes/class-groups-courses.php');
    }

    public function load_languages()
    {
        $domain = 'ld_bp_groups_sync';
        $locale = apply_filters('plugin_locale', get_locale(), $domain);

        load_textdomain($domain, WP_LANG_DIR . "/plugins/{$domain}-{$locale}.mo");

        load_plugin_textdomain('ld_bp_groups_sync', false, $this->path('languages'));
    }

    public function file()
    {
        return $this->root_file;
    }

    public function path($path = '')
    {
        return plugin_dir_path($this->root_file) . trim($path, '/\\');
    }

    public function url($uri = '')
    {
        return plugin_dir_url($this->root_file) . trim($uri, '/\\');
    }

    public function load($file)
    {
        return require $this->path($file);
    }
}
