#Change Member Profile Default Landing Page

When clicking to view a member profile the default setting opens the activity page. There is a small code snippet you can place in wp-content/plugins/bp-custom.php.

    /**
     * Change BuddyPress default Members landing tab.
     */
    define('BP_DEFAULT_COMPONENT', 'profile' );

You may replace profile with any of these options:

*   settings
*   activity
*   notifications
*   messages
*   friends
*   groups
*   forums
*   invites