#Changing Internal Configuration Settings

There are many internal configurations settings that can be changed by adding a definition line to your custom.php file or using a [filter](https://developer.wordpress.org/reference/functions/add_filter/).

Advanced Configurations
-----------------------

Enable support for LDAP usernames that include dots:

    define( 'BP_ENABLE_USERNAME_COMPATIBILITY_MODE', true );

Database Settings
-----------------

Set a custom user database table for BuddyPress (and WordPress to use):

    define ( 'CUSTOM_USER_TABLE', $tablename );

Set a custom usermeta database table for BuddyPress (and WordPress to use):

    define ( 'CUSTOM_USER_META_TABLE', $tablename );