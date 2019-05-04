#Blank Pages

A blank page is a common issue with installation of any WordPress plugin. The best place to figure out what is wrong is to view your error\_log. To find the error log you will either need an [bp_docs_link text="FTP program" slug="getting-started/installation.md" anchors="manual-installation"] or contact your hosting provider for additional details.

Memory Limits are the most likely culprit for blank pages. Here are some things to try:

*   php.ini - if possible, increase the memory limit within the php.ini file. Some hosting providers have an easy to use interface to change this yourself
*   wp-config.php - try adding the following code to your wp-config.php file

    define ('WP_MEMORY_LIMIT', '128M');

*   .htaccess - try adding the following code to your .htaccess file

    php_value memory_limit 256M

*   Contact you hosting provider - If the previous suggestions were unable to resolve your issue contact your hosting provider the see if they are able to increase memory limits for you.

After changing your server memory make sure you clear your browser cache. You can also try another web browser and logging into your site as a non-admin member.