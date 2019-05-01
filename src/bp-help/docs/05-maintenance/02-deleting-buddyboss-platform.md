#Deleting BuddyBoss Platform

So you've decided to pull the plug...you want to stop using the BuddyBoss Platform. We're sorry to see you go but want to make this breakup easy on you.

### Step 1 - Disable all BuddyBoss Platform plugins.

No plugin should call any functions without first checking if BuddyBoss Platform is activate. In the event a plugin does not follow these standards it is imperative that it is disabled prior to disabling the platform or it may break your website.

### Step 2 - Disable BuddyBoss Platform and click Delete.

In the event that your website breaks because a plugin is dependent on another plugin you may need to access your site files via FTP and remove the plugin folders manually until your site is restored to a functional state.

The BuddyBoss Platform is no longer installed on your site.

### Advanced Option 1 - Remove BuddyBoss Platform Stored Database Content

WARNING: Backup your database before making any changes! One wrong click will require you to completely reinstall everything and content will NOT be recoverable.

Open up PHPMyAdmin through your web hosting service provider and open the WordPress database you set during initial setup and installation of your website. The table prefix will most likely be `wp_bp_`. Here is a list of table to be deleted:

*   wp\_bp\_activity
*   wp\_bp\_activity\_meta
*   wp\_bp\_follow
*   wp\_bp\_friends
*   wp\_bp\_groups
*   wp\_bp\_groups\_groupmeta
*   wp\_bp\_groups\_members
*   wp\_bp\_messages\_messages
*   wp\_bp\_messages\_meta
*   wp\_bp\_messages\_notices
*   wp\_bp\_messages\_recipients
*   wp\_bp\_notifications
*   wp\_bp\_notifications\_meta
*   wp\_bp\_xprofile\_data
*   wp\_bp\_xprofile\_fields
*   wp\_bp\_xprofile\_groups
*   wp\_bp\_xprofile\_meta

BuddyBoss Platform Content is now removed from your website.

### Advanced Option 2 - Remove BuddyBoss Platform Stored Database Options

Still itching to scrub all traces of BuddyBoss Platform from your site? Open up PHPMyAdmin again and this time open the WordPress table `wp_options`. Remove all values from row option\_name starting with `_bbp, _bp, bp-, bp_, widget_bbp` or `widget_bp`.

*   \_bbp\_db\_version
*   \_bbp\_hidden\_forums
*   \_bbp\_private\_forums
*   \_bp\_db\_version
*   \_bp\_enable\_akismet
*   \_bp\_enable\_heartbeat\_refresh
*   \_bp\_force\_buddybar
*   \_bp\_ignore\_deprecated\_code
*   \_bp\_retain\_bp\_default
*   \_bp\_theme\_package\_id
*   \_transient\_buddyboss\_theme\_compressed\_bp\_custom\_css
*   bp\_activity\_favorites
*   bp\_nouveau\_appearance
*   bp\_profile\_search\_main\_form
*   bp\_restrict\_group\_creation
*   bp-active-components
*   bp-blogs-first-install
*   bp-deactivated-components
*   bp-disable-account-deletion
*   bp-disable-avatar-uploads
*   bp-disable-blogforum-comments
*   bp-disable-cover-image-uploads
*   bp-disable-group-avatar-uploads
*   bp-disable-group-cover-image-uploads
*   bp-disable-group-type-creation
*   bp-disable-invite-member-email-content
*   bp-disable-invite-member-email-subject
*   bp-disable-invite-member-type
*   bp-disable-profile-sync
*   bp-display-name-format
*   bp-emails-unsubscribe-salt
*   bp-enable-group-auto-join
*   bp-enable-group-restrict-invites
*   bp-enable-member-dashboard
*   bp-enable-private-network
*   bp-enable-profile-search
*   bp-member-type-display-on-profile
*   bp-member-type-enable-disable
*   bp-member-type-import
*   bp-pages
*   bp-xprofile-base-group-id
*   bp-xprofile-base-group-name
*   bp-xprofile-firstname-field-id
*   bp-xprofile-firstname-field-name
*   bp-xprofile-fullname-field-name
*   bp-xprofile-lastname-field-id
*   bp-xprofile-lastname-field-name
*   bp-xprofile-nickname-field-id
*   bp-xprofile-nickname-field-name
*   widget\_bbp\_forums\_widget
*   widget\_bbp\_login\_widget
*   widget\_bbp\_replies\_widget
*   widget\_bbp\_search\_widget
*   widget\_bbp\_stats\_widget
*   widget\_bbp\_topics\_widget
*   widget\_bbp\_views\_widget
*   widget\_bp\_core\_friends\_widget
*   widget\_bp\_core\_login\_widget
*   widget\_bp\_core\_members\_widget
*   widget\_bp\_core\_recently\_active\_widget
*   widget\_bp\_core\_whos\_online\_widget
*   widget\_bp\_groups\_widget
*   widget\_bp\_latest\_activities
*   widget\_bp\_messages\_sitewide\_notices\_widget
*   widget\_bp\_nouveau\_sidebar\_object\_nav\_widget