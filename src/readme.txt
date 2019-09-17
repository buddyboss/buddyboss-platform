=== BuddyBoss Platform ===
Contributors: buddyboss
Requires at least: 4.9.1
Tested up to: 5.2.2
Requires PHP: 5.6.20
Stable tag: 1.1.8
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

BuddyBoss Platform helps site builders & developers add community features to their websites, with user profiles, activity feeds, and more!

= Documentation =

- [Tutorials](https://www.buddyboss.com/resources/docs/)
- [Code Reference](https://www.buddyboss.com/resources/reference/)

== Requirements ==

To run BuddyBoss Platform, we recommend your host supports:

* PHP version 7.2 or greater.
* MySQL version 5.6 or greater, or, MariaDB version 10.0 or greater.
* HTTPS support.

== Installation ==

1. Visit 'Plugins > Add New'
2. Click 'Upload Plugin'
3. Upload the file 'buddyboss-platform.zip'
4. Activate 'BuddyBoss Platform' from your Plugins page.

== Setup ==

1. Visit 'BuddyBoss > Components' and adjust the active components to match your community. (You can always toggle these later.)
2. Visit 'BuddyBoss > Pages' and setup your directories and registration pages. We create a few automatically, but suggest you customize these to fit the flow and verbiage of your site.
3. Visit 'BuddyBoss > Settings' and take a moment to match BuddyBoss Platform's settings to your expectations. We pick the most common configuration by default, but every community is different.
4. Visit 'BuddyBoss > Help' for tutorials on further configuration.

== Frequently Asked Questions ==

= Can I use my existing WordPress theme? =

Yes! BuddyBoss Platform works out-of-the-box with most generic WordPress themes.

= Will this work on WordPress multisite? =

Yes! If your WordPress installation has multisite enabled, BuddyBoss Platform will support the global tracking of blogs, posts, comments, and even custom post types with a little bit of custom code.

Furthermore, BuddyBoss Platform can be activated and operate in just about any scope you need for it to:

* Activate at the site level to only load BuddyBoss Platform on that site.
* Activate at the network level for full integration with all sites in your network. (This is the most common multisite installation type.)
* Enable multiblog mode to allow your BuddyBoss Platform content to be displayed on any site in your WordPress Multisite network, using the same central data.
* Extend BuddyBoss Platform with a third-party multi-network plugin to allow each site or network to have an isolated and dedicated community, all from the same WordPress installation.

== Changelog ==

= 1.1.8 =
* Profiles - Allow Uppercase letters in Nicknames, and auto-convert them to lowercase for Usernames
* Profiles - Fixed display of name fields that include unicode characters
* Profiles - Fixed empty results in Profile Search form for 'Date' field type
* Groups - When adding a photo, an activity post will now show in the group feed
* Groups - Fixed loading of group members, when Activity Feeds and Network Search are both disabled
* Groups - Fixed loading of 3rd party plugin options added to 'Manage > Details' group page
* Blog - Fixed conflict with WordPress 'Categories' widget on blog archive
* Tools - 'Repair Community' tool now runs in batch processes via AJAX
* Tools - 'Repair Forums' tool now runs in batch processes via AJAX
* Compatibility - Improved support for 'BuddyPress for LearnDash' plugin

= 1.1.7 =
* Profiles - Fixed issues with duplicate Name fields in some installations
* Forums - Fixed media not displaying in replies, due to Lazy Load issues
* Forums - Fixed formatting displaying as HTML when replying as non-admin
* Forums - Fixed emoji displaying too big, for new forum replies only
* Forums - Fixed GIFs not working, unless Photos was also enabled in Media settings
* Groups - When using Group Types with custom role labels, using correct label now in discussions
* Activity - When '(BB) Connections' widget was used on Activity page, it was not working correctly
* Login - Fixed layout of login page, when 'Privacy' is enabled with Privacy page used
* Messages - Fixed issues with invalid usernames while sending messages
* Registration - When using Profile Type, and hiding First Name and Last Name, fixed conditional logic
* Registration - When using Profile Type, and validation show errors, conditional fields remain now
* Registration - Added validation telling users to use lowercase or number characters for 'Nickname'
* WooCommerce - When purchasing while logged out, and creating a new account, we now send account activation email
* Compatibility - Improved support for 'Google Captcha (reCAPTCHA) Pro' plugin

= 1.1.6 =
* Performance - Lazy load images in Activity, Forums, Photos and Albums
* Profiles - Added the ability to re-order First Name, Last Name, and Nickname profile fields
* Activity - When a member changes their Nickname, the auto-suggest for mentions now uses the updated Nickname
* Activity - When a member changes their Nickname, the 'Email Preferences' page now uses the updated Nickname
* Activity - Fixed 'Link Previews' failing when pasting URLs from certain websites
* Forums - Fixed Private and Hidden forum discussion access on the Forums index, for logged out users
* Forums - Consistent styling of site notices in Forums tabs on member profiles
* Registration - New option to require Email confirmation on the register form
* Registration - New option to require Password confirmation on the register form
* Registration - Fixed conditional logic to display profile fields that depend on a Profile Type
* Toolbar - Display the correct name format in admin Toolbar, as used in frontend Toolbar
* BuddyPanel - When using the 'Email Invites' menu in the BuddyPanel, it was missing when switching users
* MemberPress - Fixed redirect issues with the MemberPress custom login page
* Compatibility - Added settings at BuddyBoss > Integrations > BuddyPress Plugins, for BuddyPress add-on options

= 1.1.5 =
* Performance - Faster loading of Gravatar images on Members directory
* Performance - Reduced number of CSS and Javascript files loaded per page
* Performance - Removed Heartbeat API from all pages besides Activity, reduces requests to server
* Performance - Improved PHP caching for Media, Albums and Activity
* Profiles - Added 'Snapchat' as option in 'Social Networks' profile field
* Profiles - Fixed profile name field syncing between WordPress admin and BuddyBoss profiles
* Profiles - Fixes issue with 'Reset' filter not always working
* Profiles - Fixed 'Profile Types' filter showing wrong label, and not displaying if type has no members
* Activity - External links added in activity posts now open in a new tab/window
* Messages - Fixed issue with message thread loading, now doing one thread fetch request at a time
* Forums - Fixed issue with editing forum posts that include media attachments
* Forums - Fixed Edit and Merge links for all forum roles
* Groups - Display group member role in singular format, if only 1 member of that role
* Media - Fixed issue with creating an album set to 'My Connections' privacy
* Registration - Added validation notice if Nickname added is less than 4 characters
* Compatibility - Improved support for 'GEO my WordPress' plugin
* Compatibility - Improved support for Keap (Infusionsoft) API in plugins
* Translations - Fixed text instances that were not available for translation
* Errors - Fixed various PHP errors in certain situations
* Documentation - Membership Plugins

= 1.1.4 =
* Profiles - Fixed issue with member profile recognizing existing Profile Type
* Forums - Improved database query performance for Forums component
* Forums - Removed non-functional Edit and Merge links from some forum roles
* Errors - Fixed various PHP errors in certain situations

= 1.1.3 =
* Profiles - Allow users to self-select 'Profile Type' via new profile field
* Activity - Fixed issue with commenting on activity after clicking 'Load More' multiple times
* Activity - Fixed media posted in activity feed not always displaying
* Activity - Fixed photos added to group feed not displaying in group photos tab
* Activity - Fixed clicking 'Read more' link hiding the media attachment
* Messages - Fixed members without First Name displaying in messages as 'Deleted User'
* Groups - Status button said "You're an Member" instead of "You're a Member"
* Compatibility - Allow 'Events Manager' and other plugins to activate properly with Platform
* Migration - Fixed 'Nickname' field not displaying after migrating from BuddyPress 
* Errors - Fixed various PHP errors in certain situations

= 1.1.2 =
* Activity - Fixed media popup showing no image with Groups component enabled
* Activity - Fixed word-wrapping when long sentences are posted in activity
* Activity - Fixed crop ratio for wide/landscape media images
* Activity - Fixed media disappearing when clicking 'Read more' in activity feed
* Activity - Show admin notice when 'Heartbeat API' is disabled, for 'auto-refresh' 
* Media - Improved the image rotation script for photos uploaded in mobile browsers
* Media - Improved experience for media migrations, and ability to re-migrate
* Profile Types - When creating new type, fixed issue when selecting 'None' as WordPress role
* Multisite - Fixed issue with names not displaying in sub-sites
* LearnDash - Fixed conflict with 'Memberium' protected content in Lesson sidebar
* LearnDash - Now using WordPress 'Date Format' for dates in LearnDash
* Compatibility - Fixed default avatar conflict with 'WP User Avatar' plugin
* Compatibility - Fixed registration field syncing with 'WooCommerce Memberships' plugin
* Errors - Fixed various PHP errors in certain situations

= 1.1.1 =
* Profiles - New option to remove First and Last Name, depending on Display Name Format
* Groups - Activity posted in private groups now displays in activity feed
* Media - Fixed image rotation issues for photos uploaded in mobile browsers
* Activity - Allow activity to load when 'Heartbeat API' is disabled, for WPEngine hosting
* Cover Photos - Display validation message when cover photo was uploaded successfully
* Date Format - Now using WordPress 'Date Format' for dates throughout the network

= 1.1.0 =
* Profiles - Fixed profile dropdown not appearing with some plugins
* Toolbar - New option to show/hide Toolbar for admin users vs members
* Messages - Nicer text preview when message contains only an image or gif
* Media - When migrating from BuddyBoss Media plugin, fixed migration issues
* Multisite - Fixed issue with Name fields duplicating in new sub-sites
* Performance - Made functions less likely to timeout on shared hosting
* Translations - Fixed text instances that were not available for translation
* Dashboard - Moved 'BuddyBoss' menu higher up for accessibility

= 1.0.9 =
* Profiles - Fixed profile dropdown not working with Toolbar disabled
* Profiles - New option for 'Last Name' field to be set as optional
* Profiles - New option for 'gravatars' to be used as profile avatars
* Private Network - Improved logic for Public URLs
* Multisite - Fixed logic for new signups registering sub-sites
* Search - Exclude HTML meta data from search results
* Documentation - LearnDash Course Grid

= 1.0.8 =
* Updater - Improvements to updater code

= 1.0.7 =
* Messages - Fixed issues with media tooltips in messages
* Translations - Fixed text instances that were not available for translation
* Updater - Fixed issues with platform not pinging for updates

= 1.0.6 =
* Forums - Fixed issues with forum role assignment to new users
* Forums - Automatically inherit forum status from group status
* Profile Fields - Fixed issues with 'Gender' field validation
* Activity - Automatically load newest posts, without clicking a button
* Compatibility - Improved support for plugin 'Rank Math SEO'
* Compatibility - Improved support for plugin 'MemberPress + BuddyPress Integration'
* Compatibility - Improved support for plugins that 'Require BuddyPress'

= 1.0.5 =
* Forums - Discussion/topic URL slugs from bbPress auto-migrate now
* Forums - Fixed issues with replying to discussions on mobile
* Activity - Fixed comment issues on new activity posts
* Activity - Display video embeds and link previews in different styles
* Media - When migrating from BuddyBoss Media plugin, add media to activity, forums, messages
* Compatibility - Fixed conflict with BadgeOS plugin
* Compatibility - Fixed conflict with BuddyPress Follow plugin
* Documentation - How to migrate from Boss Theme

= 1.0.4 =
* Multisite - Fixed various issues
* Photos - Show photos count when viewing other member's profile
* Compatibility - Improved support for plugins that 'Require bbPress'

= 1.0.3 =
* Multisite - Fixed various issues
* Forums - Tagging improvements in replies
* Documentation - How to migrate from Social Learner
* Documentation - How to use the Resources website

= 1.0.2 =
* Forums - allow Forums page to be set as Homepage
* Forums - added nicer tagging interface when replying
* Messages - inline editor fixes
* Media - fix issues with emoji not displaying
* Documentation - Events Calendar Pro
* Documentation - WooCommerce
* Documentation - WP Job Manager

= 1.0.1 =
* Documentation fixes

= 1.0.0 =
* Initial Release - fork of BuddyPress
* Forum Discussions - merged in bbPress and re-factored as a native forum component
* Media Uploading - Photos, Albums, Emoji, Animated GIFs
* Email Inivites - members can invite outside users to your community
* Network Search - search content across the entire network
* Private Network - restrict access to all of your content with a single click
* Profiles - new 'Repeater Fields' options
* Profiles - new 'Profile Types' manager
* Profiles - new 'View As' button to toggle between members
* Profiles - new profile fields for 'Gender' and 'Social Networks'
* Profiles - new option to set display name as First, First & Last, or Nickname
* Groups - new Group Types manager
* Groups - new Group Heirarchies manager
* Messages - combined all messages into single threads, like Facebook and LinkedIn
* Messages - significantly enhanced messaging interface
* Activity - combined all activity into single feed, like Facebook and LinkedIn
* Activity - new 'Likes' option
* Activity - new 'Follow' option
* Activity - new option to add any custom post types into activity feed
* Default Data - new tool to easily install default data
* LearnDash - ability to connect one or more courses with social groups
* LearnDash - ability to run group reports on course progress
* Many more features and improvements!


