#White Screen Pages

A blank white page ocassionally happens with installation of a WordPress plugin. Usually this is due to some PHP error/conflict in the plugin, or a memory limit on the server. The best place to figure out what is wrong is to view your error\_log. To find the error log you will either need an FTP program or contact your hosting provider for additional details.

###PHP conflicts are a common culprit for blank pages. Here are some things to try:

* Disable all plugins
* Re-enable your plugins one by one to see if any plugin causes the error
* Revert to the default WordPress theme
* Re-activate your custom theme to see if it causes the error
* Stop using the theme or plugin that caused the error

###Memory Limits are the most likely culprit for blank pages. Here are some things to try:

*   **php.ini** - if possible, increase the memory limit within the php.ini file. Some hosting providers have an easy to use interface to change this yourself
*   **wp-config.php** - try adding the following code to your wp-config.php file:
   ` define ('WP_MEMORY_LIMIT', '128M');`
*   **.htaccess** - try adding the following code to your .htaccess file:
    `php_value memory_limit 256M`
*   **Contact you hosting provider** - If the previous suggestions were unable to resolve your issue contact your hosting provider to see if they are able to increase memory limits for you.

After changing your server memory make sure you clear your browser cache. You can also try another web browser and logging into your site as a non-admin member.