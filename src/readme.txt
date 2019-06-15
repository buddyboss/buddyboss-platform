=== BuddyBoss Platform ===
Contributors: buddyboss
Requires at least: 4.9.1
Tested up to: 5.2.1
Requires PHP: 5.6.20
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

BuddyBoss Platform helps site builders & developers add community features to their websites, with user profiles, activity feeds, and more!

= Documentation =

- [Tutorials](https://www.buddyboss.com/resources/docs/)
- [Developer Reference](https://www.buddyboss.com/resources/reference/)

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

= 1.0.4 =
* Multisite -- Fixed various issues
* Photos -- show photos count when viewing other member's profile
* Compatibility -- improved support for plugins that "Require BuddyPress"

= 1.0.3 =
* Multisite -- Fixed various issues
* Forums -- Tagging improvements in replies
* Documentation -- Migrating from Social Learner
* Documentation -- How to use the Resources website

= 1.0.2 =
* Forums -- allow Forums page to be set as Homepage
* Forums -- added nicer tagging interface when replying
* Messages -- inline editor fixes
* Media - fix issues with emoji not displaying
* Documentation -- Events Calendar Pro
* Documentation -- WooCommerce
* Documentation -- WP Job Manager

= 1.0.1 =
* Documentation fixes

= 1.0.0 =
* Initial Release -- fork of BuddyPress
* Forum Discussions -- merged in bbPress and re-factored as a native forum component
* Media Uploading -- Photos, Albums, Emoji, Animated GIFs
* Email Inivites -- members can invite outside users to your community
* Network Search -- search content across the entire network
* Private Network -- restrict access to all of your content with a single click
* Profiles -- new 'Repeater Fields' options
* Profiles -- new 'Profile Types' manager
* Profiles -- new 'View As' button to toggle between members
* Profiles -- new profile fields for 'Gender' and 'Social Networks'
* Profiles -- new option to set display name as First, First & Last, or Nickname
* Groups -- new Group Types manager
* Groups -- new Group Heirarchies manager
* Messages -- combined all messages into single threads, like Facebook and LinkedIn
* Messages -- significantly enhanced messaging interface
* Activity -- combined all activity into single feed, like Facebook and LinkedIn
* Activity -- new 'Likes' option
* Activity -- new 'Follow' option
* Activity -- new option to add any custom post types into activity feed
* Default Data -- new tool to easily install default data
* LearnDash -- ability to connect one or more courses with social groups
* LearnDash -- ability to run group reports on course progress
* Many more features and improvements!


