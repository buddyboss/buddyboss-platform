<?php
//BPObjectCache Version: 1.0
// Stop direct access
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Adds data to the cache, if the cache key does not already exist.
 *
 * @param int|string $key    The cache key to use for retrieval later
 * @param mixed      $data   The data to add to the cache store
 * @param string     $group  The group to add the cache to
 * @param int        $expire When the cache data should be expired
 *
 * @return bool False if cache key and group already exist, true on success
 */
function wp_cache_add($key, $data, $group = 'default', $expire = 0)
{
	return WP_Object_Cache::instance()->add($key, $data, $group, $expire);
}


/**
 * Closes the cache.
 *
 * This function has ceased to do anything since WordPress 2.5. The
 * functionality was removed along with the rest of the persistent cache. This
 * does not mean that plugins can't implement this function when they need to
 * make sure that the cache is cleaned up after WordPress no longer needs it.
 *
 * @return bool Always returns True
 */
function wp_cache_close()
{
	return true;
}


/**
 * Decrement numeric cache item's value
 *
 * @param int|string $key    The cache key to increment
 * @param int        $offset The amount by which to decrement the item's value. Default is 1.
 * @param string     $group  The group the key is in.
 *
 * @return false|int False on failure, the item's new value on success.
 */
function wp_cache_decr($key, $offset = 1, $group = 'default')
{
	return WP_Object_Cache::instance()->decr($key, $offset, $group);
}


/**
 * Removes the cache contents matching key and group.
 *
 * @param int|string $key   What the contents in the cache are called
 * @param string     $group Where the cache contents are grouped
 *
 * @return bool True on successful removal, false on failure
 */
function wp_cache_delete($key, $group = 'default')
{
	return WP_Object_Cache::instance()->delete($key, $group);
}


/**
 * Removes all cache items.
 *
 * @return bool False on failure, true on success
 */
function wp_cache_flush()
{
	return WP_Object_Cache::instance()->flush();
}


/**
 * Retrieves the cache contents from the cache by key and group.
 *
 * @param int|string $key    What the contents in the cache are called
 * @param string     $group  Where the cache contents are grouped
 * @param bool       $force  Does nothing with OPcache object cache
 * @param bool       &$found Whether key was found in the cache. Disambiguates a return of false, a storable value.
 *
 * @return bool|mixed False on failure to retrieve contents or the cache contents on success
 */
function wp_cache_get($key, $group = 'default', $force = false, &$found = null)
{
	return WP_Object_Cache::instance()->get($key, $group, $force, $found);
}


/**
 * Retrieve multiple values from cache.
 *
 * Gets multiple values from cache, including across multiple groups
 *
 * Usage: array( 'group0' => array( 'key0', 'key1', 'key2', ), 'group1' => array( 'key0' ) )
 *
 * @param array $groups Array of groups and keys to retrieve
 *
 * @return array Array of cached values as
 *    array( 'group0' => array( 'key0' => 'value0', 'key1' => 'value1', 'key2' => 'value2', ) )
 *    Non-existent keys are not returned.
 */
function wp_cache_get_multi($groups)
{
	return WP_Object_Cache::instance()->get_multi($groups);
}


/**
 * Increment numeric cache item's value
 *
 * @param int|string $key    The cache key to increment
 * @param int        $offset The amount by which to increment the item's value. Default is 1.
 * @param string     $group  The group the key is in.
 *
 * @return false|int False on failure, the item's new value on success.
 */
function wp_cache_incr($key, $offset = 1, $group = 'default')
{
	return WP_Object_Cache::instance()->incr($key, $offset, $group);
}


/**
 * Sets up Object Cache Global and assigns it.
 *
 * @global WP_Object_Cache $wp_object_cache WordPress Object Cache
 */
function wp_cache_init()
{
	$GLOBALS['wp_object_cache'] = WP_Object_Cache::instance();
}


/**
 * Replaces the contents of the cache with new data.
 *
 * @param int|string $key    What to call the contents in the cache
 * @param mixed      $data   The contents to store in the cache
 * @param string     $group  Where to group the cache contents
 * @param int        $expire When to expire the cache contents
 *
 * @return bool False if not exists, true if contents were replaced
 */
function wp_cache_replace($key, $data, $group = 'default', $expire = 0)
{
	return WP_Object_Cache::instance()->replace($key, $data, $group, $expire);
}


/**
 * Saves the data to the cache.
 *
 * @param int|string $key    What to call the contents in the cache
 * @param mixed      $data   The contents to store in the cache
 * @param string     $group  Where to group the cache contents
 * @param int        $expire When to expire the cache contents
 *
 * @return bool False on failure, true on success
 */
function wp_cache_set($key, $data, $group = 'default', $expire = 0)
{
	return WP_Object_Cache::instance()->set($key, $data, $group, $expire);
}


/**
 * Switch the internal blog id.
 *
 * This changes the blog id used to create keys in blog specific groups.
 *
 * @param int $blog_id Blog ID
 */
function wp_cache_switch_to_blog($blog_id)
{
	WP_Object_Cache::instance()->switch_to_blog($blog_id);
}


/**
 * Adds a group or set of groups to the list of global groups.
 *
 * @param string|array $groups A group or an array of groups to add
 */
function wp_cache_add_global_groups($groups)
{
	WP_Object_Cache::instance()->add_global_groups($groups);
}


/**
 * Pass thru to wp_cache_add_global_groups.
 *
 * @param string|array $groups A group or an array of groups to add
 */
function wp_cache_add_non_persistent_groups($groups)
{
	wp_cache_add_global_groups($groups);
}


/**
 * Function was depreciated and now does nothing
 *
 * @return bool Always returns false
 */
function wp_cache_reset()
{
	_deprecated_function(__FUNCTION__, '3.5', 'wp_cache_switch_to_blog()');
	return false;
}


/**
 * Invalidate a site's object cache
 *
 * @param mixed $sites Sites ID's that want flushing.
 *                     Don't pass a site to flush current site
 *
 * @return bool
 */
function wp_cache_flush_site($sites = null)
{
	return WP_Object_Cache::instance()->flush_sites($sites);
}


/**
 * Invalidate a groups object cache
 *
 * @param mixed $groups A group or an array of groups to invalidate
 *
 * @return bool
 */
function wp_cache_flush_group($groups = 'default')
{
	return WP_Object_Cache::instance()->flush_groups($groups);
}


/**
 * WordPress OPcache Object Cache Driver
 *
 * The WordPress Object Cache is used to save on trips to the database.
 * The OPcache Cache stores all of the cache data to PHP OPcache and makes
 * the cache contents available by using a key, which is used to name and
 * later retrieve the cache contents.
 *
 * @author Elco Brouwer von Gonzenbach <elco.brouwer@gmail.com>
 */
class WP_Object_Cache
{

	/**
	 * Holds the cached objects.
	 *
	 * @since 2.0.0
	 * @var array
	 */
	private $cache = array();

	/**
	 * @var string The file cache directory.
	 */
	protected $directory;

	/**
	 * @var string Slug of the current blog name
	 */
	private $base_name = '';


	/**
	 * @var bool Stores if OPcache is available.
	 */
	private $enabled = false;


	/**
	 * @var int The sites current blog ID. This only
	 *    differs if running a multi-site installations
	 */
	private $blog_prefix = 1;


	/**
	 * @var int Keeps count of how many times the
	 *    cache was successfully received from OPcache
	 */
	private $cache_hits = 0;


	/**
	 * @var int Keeps count of how many times the
	 *    cache was not successfully received from OPcache
	 */
	private $cache_misses = 0;


	/**
	 * @var array Holds a list of cache groups that are
	 *    shared across all sites in a multi-site installation
	 */
	private $global_groups = array();


	/**
	 * @var bool True if the current installation is a multi-site
	 */
	private $multi_site = false;

	protected $start_time;


	/**
	 * Singleton. Return instance of WP_Object_Cache
	 *
	 * @return WP_Object_Cache
	 */
	public static function instance()
	{
		static $inst = null;

		if ($inst === null) {
			$inst = new WP_Object_Cache();
		}

		return $inst;
	}


	/**
	 * __clone not allowed
	 */
	private function __clone()
	{
	}


	/**
	 * Direct access to __construct not allowed.
	 */
	public function __construct()
	{
		global $blog_id;

		if (!defined('WP_OPCACHE_KEY_SALT')) {
			/**
			 * Set in config if you are using some sort of shared
			 * config where base_name is the same on all sites
			 */
			define('WP_OPCACHE_KEY_SALT', 'wp-opcache');
		}

		$this->directory    = WP_CONTENT_DIR . '/cache';
		$this->base_name    = basename(ABSPATH);
		$this->enabled      = function_exists('opcache_invalidate')
		                      && ('cli' !== \PHP_SAPI || filter_var(ini_get('opcache.enable_cli'), FILTER_VALIDATE_BOOLEAN))
		                      && filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN);
		$this->multi_site   = is_multisite();
		$this->blog_prefix  = $this->multi_site ? (int) $blog_id : 1;
		$this->start_time   = isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();

		if (!file_exists($this->directory)) {
			mkdir($this->directory, 0755, true);
		}
	}


	/**
	 * Adds data to the cache, if the cache key does not already exist.
	 *
	 * @param int|string $key   The cache key to use for retrieval later
	 * @param mixed      $var   The data to add to the cache store
	 * @param string     $group The group to add the cache to
	 * @param int        $ttl   When the cache data should be expired
	 *
	 * @return bool False if cache key and group already exist, true on success
	 */
	public function add($key, $var, $group = 'default', $ttl = 0)
	{
		if (wp_suspend_cache_addition() || $this->exists($key, $group)) {
			return false;
		}
		if ($this->multi_site && ! isset($this->global_groups[ $group ])) {
			$key = $this->blog_prefix . $key;
		}

		return $this->set($key, $var, $group, $ttl);
	}


	/**
	 * Sets the list of global groups.
	 *
	 * @param string|array $groups List of groups that are global.
	 */
	public function add_global_groups($groups)
	{
		$groups = (array) $groups;

		$groups = array_fill_keys($groups, true);

		$this->global_groups = array_merge($this->global_groups, $groups);
	}


	/**
	 * Decrement numeric cache item's value
	 *
	 * @param int|string $key    The cache key to decrement
	 * @param int        $offset The amount by which to decrement the item's value. Default is 1.
	 * @param string     $group  The group the key is in.
	 *
	 * @return false|int False on failure, the item's new value on success.
	 */
	public function decr($key, $offset = 1, $group = 'default')
	{
		return $this->incr($key, $offset * -1, $group);
	}


	/**
	 * Remove the contents of the cache key in the group
	 *
	 * If the cache key does not exist in the group, then nothing will happen.
	 *
	 * @param int|string $key        What the contents in the cache are called
	 * @param string     $group      Where the cache contents are grouped
	 * @param bool       $deprecated Deprecated.
	 *
	 * @return bool False if the contents weren't deleted and true on success
	 */
	public function delete($key, $group = 'default', $deprecated = false)
	{
		unset($deprecated);

		$key = $this->buildKey($key, $group);

		if ($this->enabled) {
			opcache_invalidate($this->filePath($key), true);
		}
		return @unlink($this->filePath($key));
	}


	/**
	 * Checks if the cached OPcache key exists
	 *
	 * @param string $key What the contents in the cache are called
	 * @param string $group Where the cache contents are grouped
	 *
	 * @return bool True if cache key exists else false
	 */
	private function exists($key, $group)
	{
		if (isset($this->cache[ $group ]) && ( isset($this->cache[ $group ][ $key ]) || array_key_exists($key, $this->cache[ $group ]))) {
			return true;
		}

		$key = $this->buildKey($key, $group);

		return $this->enabled && opcache_is_script_cached($this->filePath($key))
		       || file_exists($this->filePath($key));
	}


	/**
	 * Clears the object cache of all data
	 *
	 * @return bool Always returns true
	 */
	public function flush()
	{
		$files = glob($this->directory . '/*');

		if ($this->enabled) {
			array_map('opcache_invalidate', $files);
		}
		return (bool) array_map('unlink', $files);
	}


	/**
	 * Invalidate a groups object cache
	 *
	 * @param mixed $groups A group or an array of groups to invalidate
	 *
	 * @return bool
	 */
	public function flush_groups($groups)
	{
		$groups = (array) $groups;

		if (empty($groups)) {
			return false;
		}

		foreach ($groups as $group) {
			// TODO: unset groups
		}

		return true;
	}


	/**
	 * Invalidate a site's object cache
	 *
	 * @param mixed $sites Sites ID's that want flushing.
	 *                     Don't pass a site to flush current site
	 *
	 * @return bool
	 */
	public function flush_sites($sites)
	{
		$sites = (array) $sites;

		if (empty($sites)) {
			$sites = array( $this->blog_prefix );
		}

		// Add global groups (site 0) to be flushed.
		if (!in_array(0, $sites)) {
			$sites[] = 0;
		}

		foreach ($sites as $site) {
			// TODO: unset groups
		}

		return true;
	}


	/**
	 * Retrieves the cache contents, if it exists
	 *
	 * The contents will be first attempted to be retrieved by searching by the
	 * key in the cache key. If the cache is hit (success) then the contents
	 * are returned.
	 *
	 * On failure, the number of cache misses will be incremented.
	 *
	 * @param int|string $key   What the contents in the cache are called
	 * @param string     $group Where the cache contents are grouped
	 * @param bool       $force Not used.
	 * @param bool       &$success
	 *
	 * @return bool|mixed False on failure to retrieve contents or the cache contents on success
	 */
	public function get($key, $group = 'default', $force = false, &$success = null)
	{
		if (! $key) {
			$success = false;
			return false;
		}
		if (empty($group)) {
			$group = 'default';
		}

		if ($this->multi_site && ! isset($this->global_groups[ $group ])) {
			$key = $this->blog_prefix . $key;
		}

		if (isset($this->cache[ $group ]) && ( isset($this->cache[ $group ][ $key ]) || array_key_exists($key, $this->cache[ $group ]) )) {
			$success             = true;
			$this->cache_hits += 1;
			if (is_object($this->cache[ $group ][ $key ])) {
				return clone $this->cache[ $group ][ $key ];
			} else {
				return $this->cache[ $group ][ $key ];
			}
		}

		$result = @include $this->filePath($this->buildKey($key, $group));
		if (false === $result) {
			// file did not exist.
			$success = false;
			$var =  false;
		} else {
			$success = true;
			list( $exp, $var ) = $result;

			if (is_object($var)) {
				$this->cache[ $group ][ $key ] = clone $var;
			} else {
				$this->cache[ $group ][ $key ] = $var;
			}

			if ($exp < time()) {
				// cache expired.
				$var = false;
				$success = false;
			}
		}

		if ($success) {
			$this->cache_hits++;
		} else {
			$this->cache_misses++;
		}

		return $var;
	}


	/**
	 * Retrieve multiple values from cache.
	 *
	 * Gets multiple values from cache, including across multiple groups
	 *
	 * Usage: array( 'group0' => array( 'key0', 'key1', 'key2', ), 'group1' => array( 'key0' ) )
	 *
	 * @param array $groups Array of groups and keys to retrieve
	 *
	 * @return array Array of cached values as
	 *    array( 'group0' => array( 'key0' => 'value0', 'key1' => 'value1', 'key2' => 'value2', ) )
	 *    Non-existent keys are not returned.
	 */
	public function get_multi($groups)
	{
		if (empty($groups) || !is_array($groups)) {
			return array();
		}

		$vars    = array();
		$success = false;

		foreach ($groups as $group => $keys) {
			$vars[$group] = array();

			foreach ($keys as $key) {
				$var = $this->get($key, $group, false, $success);

				if ($success) {
					$vars[$group][$key] = $var;
				}
			}
		}

		return $vars;
	}


	/**
	 * Increment numeric cache item's value
	 *
	 * @param int|string $key    The cache key to increment
	 * @param int        $offset The amount by which to increment the item's value. Default is 1.
	 * @param string     $group  The group the key is in.
	 *
	 * @return false|int False on failure, the item's new value on success.
	 */
	public function incr($key, $offset = 1, $group = 'default')
	{
		if (!$this->exists($key, $group)) {
			return false;
		}

		$var = (int) $this->get($key, $group) + $offset;
		return $this->set($key, $var, $group) ? $var : false;
	}


	/**
	 * Works out a cache key based on a given key and group
	 *
	 * @param int|string $key   The key
	 * @param string     $group The group
	 *
	 * @return string Returns the calculated cache key
	 */
	public function buildKey($key, $group = 'default')
	{
		$prefix = 0;

		if (!isset($this->global_groups[$group])) {
			$prefix = $this->blog_prefix;
		}

		return $this->base_name . ':' . $prefix . ':' . $group . ':' . $key;
	}


	/**
	 * Replace the contents in the cache, if contents already exist
	 *
	 * @param int|string $key   What to call the contents in the cache
	 * @param mixed      $var   The contents to store in the cache
	 * @param string     $group Where to group the cache contents
	 * @param int        $ttl   When to expire the cache contents
	 *
	 * @return bool False if not exists, true if contents were replaced
	 */
	public function replace($key, $var, $group = 'default', $ttl = 0)
	{
		if (!$this->exists($key, $group)) {
			return false;
		}

		return $this->set($key, $var, $group, $ttl);
	}


	/**
	 * Sets the data contents into the cache
	 *
	 * @param int|string $key   What to call the contents in the cache
	 * @param mixed      $var   The contents to store in the cache
	 * @param string     $group Where to group the cache contents
	 * @param int        $ttl   When the cache data should be expired
	 *
	 * @return bool True if cache set successfully else false
	 */
	public function set($key, $var, $group = 'default', $ttl = 0)
	{
		if (empty($group)) {
			$group = 'default';
		}

		if (is_object($var)) {
			$var = clone $var;
		}

		$this->cache[ $group ][ $key ] = $var;

		$key = $this->buildKey($key, $group);

		$ttl = max(intval($ttl), 0);

		if (is_object($var) && ! method_exists($var, '__set_state') && ! $var instanceof stdClass) {
			$var = serialize($var);
			$var = var_export($var, true);
			$var = 'unserialize('.$var.')';
		} else {
			$var = var_export($var, true);
		}

		// HHVM fails at __set_state, so just use object cast for now
		$var = str_replace('stdClass::__set_state', '(object)', $var);

		return $this->writeFile($key, $this->expiration($ttl), $var);
	}


	/**
	 * Switch the internal blog id.
	 *
	 * This changes the blog id used to create keys in blog specific groups.
	 *
	 * @param int $blog_id Blog ID
	 */
	public function switch_to_blog($blog_id)
	{
		$blog_id           = (int) $blog_id;
		$this->blog_prefix = $this->multi_site ? $blog_id : 1;
	}


	/**
	 * Get fully qualified file path
	 *
	 * @param  string  $key
	 * @return string
	 */
	protected function filePath($key)
	{
		return $this->directory . '/' . WP_OPCACHE_KEY_SALT . '-' . md5($key) . '.php';
	}


	/**
	 * Write the cache file to disk
	 *
	 * @param   string $key
	 * @param   int    $exp
	 * @param   mixed  $var
	 * @return  bool
	 */
	protected function writeFile($key, $exp, $var)
	{
		// Write to temp file first to ensure atomicity. Use crc32 for speed
		$tmp = $this->directory . '/' . crc32($key) . '-' . uniqid('', true) . '.tmp';
		file_put_contents($tmp, '<?php return array('. $exp . ',' . $var . ');', LOCK_EX);
		$file_path = $this->filePath($key);
		touch($file_path, $this->start_time - 10);
		$return = rename($tmp, $file_path);

		if ($this->enabled) {
			@opcache_invalidate($file_path, true);
			@opcache_compile_file($file_path);
		}
		return $return;
	}


	/**
	 * Get the expiration time based on the given seconds.
	 *
	 * @param  float|int  $seconds
	 * @return int
	 */
	protected function expiration($seconds)
	{
		return $seconds === 0 ? 9999999999 : strtotime('+' . $seconds . ' seconds');
	}

	/**
	 * @return boolean
	 */
	public function getOpcacheEnabled()
	{
		return $this->enabled;
	}


	/**
	 * @return int
	 */
	public function getBlogPrefix()
	{
		return $this->blog_prefix;
	}


	/**
	 * @return int
	 */
	public function getCacheHits()
	{
		return $this->cache_hits;
	}


	/**
	 * @return int
	 */
	public function getCacheMisses()
	{
		return $this->cache_misses;
	}


	/**
	 * @return array
	 */
	public function getGlobalGroups()
	{
		return $this->global_groups;
	}


	/**
	 * @return boolean
	 */
	public function get_multiSite()
	{
		return $this->multi_site;
	}
}