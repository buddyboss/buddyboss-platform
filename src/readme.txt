=== BuddyBoss Platform ===
Contributors: buddyboss
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4.0
Stable tag: 3.4.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

BuddyBoss Platform helps site builders & developers add community features to their websites, with user profiles, activity feeds, and more!

= Documentation =

- [Tutorials](https://www.buddyboss.com/resources/docs/)
- [Code Reference](https://www.buddyboss.com/resources/reference/)
- [Github](https://github.com/buddyboss/buddyboss-platform/wiki)
- [Roadmap](https://www.buddyboss.com/roadmap/)

== Requirements ==

To run BuddyBoss Platform, we recommend your host supports:

* PHP version 7.2 or greater.
* MySQL version 5.6 or greater, or, MariaDB version 10.0 or greater.
* HTTPS support.

== Installation ==

1. Visit 'Plugins > Add New'
2. Click 'Upload Plugin'
3. Upload the file 'buddyboss-platform-plugin.zip'
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

== External services ==

BuddyBoss Platform connects to a number of external/third-party services. Each service, the data it receives, and when that happens is described below, along with the provider's Terms of Use and Privacy Policy. Several of these services are operated by BuddyBoss and are covered by the BuddyBoss Terms (https://www.buddyboss.com/terms/) and Privacy Policy (https://www.buddyboss.com/privacy/).

= Google Fonts =

The ReadyLaunch interface loads the "Inter" font family (SIL Open Font License) from the Google Fonts content-delivery network. When a page using ReadyLaunch styling is rendered, the visitor's browser requests the font stylesheet from `https://fonts.googleapis.com/` and the font files from `https://fonts.gstatic.com/`; as with any HTTP request, the visitor's IP address and user agent are sent to Google as part of the request. In addition, when generated "initials" default avatars are enabled, the web server downloads the Inter Medium TTF file once from the same Google Fonts service and caches it in the uploads directory for image rendering; that request originates from your server and sends no visitor data. No other data is transmitted. This service is operated by Google.
Terms of Service: https://policies.google.com/terms
Privacy Policy: https://policies.google.com/privacy

= BuddyBoss licensing service =

Used to activate, deactivate, and periodically validate your BuddyBoss license so that premium plugins and updates work. When you activate or deactivate a license in the admin (or during automatic re-validation), your license key and your site's activation domain are sent to the licensing endpoint at `https://licenses.caseproof.com/api/v1/`. This licensing platform is operated on BuddyBoss's behalf by Caseproof, LLC.
Terms of Use: https://www.buddyboss.com/terms/
Privacy Policy: https://www.buddyboss.com/privacy/
Processor (licensing): Caseproof, LLC — Terms: https://caseproof.com/terms-of-service/ (TODO: confirm) — Privacy: https://caseproof.com/privacy-policy/ (TODO: confirm)

= BuddyBoss free license verification =

Used to generate a free BuddyBoss license key from within the admin. When you request a free key, the first name, last name, and email address you enter are sent to `https://b6zdd3mwkj.execute-api.us-east-2.amazonaws.com/v1/verify/`. This service is operated by BuddyBoss.
Terms of Use: https://www.buddyboss.com/terms/
Privacy Policy: https://www.buddyboss.com/privacy/

= BuddyBoss Support Access =

Used by the Help & Support Center to let the BuddyBoss support team securely and temporarily access your site to troubleshoot issues. When you enable Support Access (or attach a ticket, extend, or revoke access), your site URL, the attached support ticket number(s), and a temporary access token are sent to `https://oq8tjkh4kk.execute-api.us-east-2.amazonaws.com/v1`. Access is time-limited and expires automatically. This service is operated by BuddyBoss.
Terms of Use: https://www.buddyboss.com/terms/
Privacy Policy: https://www.buddyboss.com/privacy/

= BuddyBoss usage telemetry =

Optional, anonymous usage tracking used to help improve the product. It is disabled/anonymous by default and only runs if you opt in. When active, non-personal environment data is periodically sent to `https://www.buddyboss.com/usage-tracking/` — including an anonymous site identifier, site URL, WordPress/PHP/MySQL versions, the list of installed plugins and themes with versions, and which BuddyBoss components and integrations are active. Your admin email address is included only if you explicitly allow it. This service is operated by BuddyBoss.
Terms of Use: https://www.buddyboss.com/terms/
Privacy Policy: https://www.buddyboss.com/privacy/

= BuddyBoss admin help & integrations catalog =

Used to power the in-admin Help Center article search and the Integrations browser. When you search for help articles or browse/search integrations, your search query is sent to BuddyBoss (`https://buddyboss.com/wp-json/wp/v2/ht-kb/` for knowledge-base articles, and a BuddyBoss.com integrations endpoint fetched server-side via a proxy). No personal data is required to perform a search. This service is operated by BuddyBoss.
Terms of Use: https://www.buddyboss.com/terms/
Privacy Policy: https://www.buddyboss.com/privacy/

= BuddyBoss marketing/upsell catalog =

Used to display feature previews and upgrade information in the admin dashboard. The admin periodically fetches JSON catalog files and promotional images from `https://bb-features-marketing.s3.amazonaws.com/` (for example `bb-features.json` and `bb-field-upgrades.json`). This is a server-side request from your site to retrieve marketing content; no personal data is sent. This service is operated by BuddyBoss.
Terms of Use: https://www.buddyboss.com/terms/
Privacy Policy: https://www.buddyboss.com/privacy/

= Google reCAPTCHA =

Used, when you enable the reCAPTCHA integration, to protect registration, login, and lost-password forms from spam and abuse. When reCAPTCHA is enabled, the reCAPTCHA script is loaded from `https://www.google.com/recaptcha/api.js` in the visitor's browser, and on form submission the challenge token (together with your reCAPTCHA secret key and the visitor's IP address) is verified against `https://www.google.com/recaptcha/api/siteverify`. This is provided by Google.
More about reCAPTCHA: https://www.google.com/recaptcha/about/
Terms of Use: https://policies.google.com/terms
Privacy Policy: https://policies.google.com/privacy

= Gravatar =

Used to display user avatars based on email address. When resolving an avatar, an MD5 hash of the user's email address is used to request the image from `https://www.gravatar.com/avatar/`, and the plugin may make a request to that host to check whether a Gravatar exists. Gravatar is operated by Automattic Inc.
Terms of Use: https://wordpress.com/tos/
Privacy Policy: https://automattic.com/privacy/

== Changelog ==

= 3.4.2 =
* Bug: Activity - Added two new objects to the Activity REST API
* Bug: Core - Fixed the issue where the addon plugin was not installing and activating automatically according to the active plan
* Bug: Login - Fixed a server-side verification bypass that allowed CAPTCHA validation to be skipped when the response field was missing

= 3.4.1 =
* Enhancement: Core - Updated add-on listing in settings and improvements

= 3.4.0 =
* Enhancement: Improved plan and feature management, laying the groundwork for plan options

= 3.3.0 =
* Enhancement: Profiles - Added a new "Bio" profile field mapped to the WordPress biographical info field

= 3.2.0 =
* New Feature! - Added the Blogs feature with post bookmarking and blog category subscriptions, plus support for the Member Blogging add-on

= 3.1.2 =
* Enhancement: Notification - Added notification cleanup support to remove orphaned notification metadata

= 3.1.1 =
* Bug: Activity - Fixed an issue where clicking a mention notification wouldn’t open the feed post if the Activity tab was hidden from profile navigation
* Bug: Activity - Fixed an issue where site admins could see private/hidden group activity in the activity feed but couldn’t open the individual activity post
* Bug: Core - Fixed a fatal error that could prevent members from logging in when the Imagick PHP extension isn’t installed on the server
* Bug: Core - Fixed an issue in the WordPress Dashboard where the original group creator couldn’t be removed from a group’s member list
* Bug: ReadyLaunch - Fixed an issue where renaming or saving a document folder incorrectly showed a “special characters not supported” error and prevented saving

= 3.1.0 =
* New Feature! - Introduced a new Help & Support Center page in the BuddyBoss admin, providing site administrators with quick access to documentation and the ability to grant site access to the support team by attaching support tickets
* New Feature! - Introduced a new PeepSo Migration Tool to simplify the migration process from PeepSo to BuddyBoss platform and redesigned the Tools page in the BuddyBoss admin for improved usability
* New Feature! - The Integrations page in BuddyBoss admin has been redesigned with a new grid layout, making it easier to browse, search, and install available third-party add-ons directly from your admin settings
* Bug: Activity - Fixed an issue in the BuddyBoss App where the ‘Report Comment’ option was appearing multiple times for the same blog post comment
* Bug: Activity - Fixed an issue where post and comment reactions were not working correctly
* Bug: Activity - Fixed an issue where the emoji picker was not opening in the activity comment form when using ReadyLaunch
* Bug: Core - Added backward compatibility so third-party plugin metaboxes now properly display and save in the new BuddyBoss backend settings modals
* Bug: Core - Fixed a security vulnerability in the member search functionality by properly sanitizing search input to prevent potential SQL injection
* Bug: Core - Fixed a UI display issue with the New Settings side panel that was affecting users on Mac Safari browser
* Bug: Core - Fixed an issue where drag-and-drop reordering of profile fields between sections was not working in the BuddyBoss admin
* Bug: Core - Fixed an issue where long usernames were not wrapping correctly on mobile, causing the last character to appear on a separate line
* Bug: Core - Fixed an issue where reCAPTCHA could not be disabled through the BuddyBoss Settings and would re-enable itself after an update
* Bug: Core - Fixed multiple UI alignment and display issues in the New Settings interface, including the search loader, search result icons, and reaction button icon picker
* Bug: Forums - Fixed the Forum Feature Image picker to now include the WordPress Media Library, allowing you to select existing images instead of always uploading a new one
* Bug: Groups - Fixed an issue where clearing a group activity title would automatically restore the previously saved title instead of leaving it empty
* Bug: Groups - Fixed an issue where moving files between folders in the Group Documents section was not functioning correctly
* Bug: Groups - Fixed an issue where the ‘Move’ button in the Group Documents section was not working and caused a JavaScript error
* Bug: Login - Fixed a JavaScript error that occurred on the registration page when the email confirmation field was disabled in settings
* Bug: Messages - Fixed a regression where subscribers were unable to send private messages to other subscribers even when the messaging access settings allowed it
* Bug: Messages - Fixed an issue where the private messages dropdown was not showing messages when accessed from the activity detail page
* Bug: Profiles - Fixed a security vulnerability in Advanced Profile Search where a search filter value was not properly sanitized before use in a database query
* Bug: ReadyLaunch - Fixed a styling issue with the profile photo upload section on the mobile Edit Profile page in ReadyLaunch
* Bug: ReadyLaunch - Fixed a UI issue in ReadyLaunch where navigation items with sub-menus were not displaying correctly when placed under the ‘More’ menu
* Bug: ReadyLaunch - Fixed an issue in ReadyLaunch where the mentions dropdown in the activity post form was hidden behind the modal and not accessible
* Bug: ReadyLaunch - Fixed an issue in ReadyLaunch where the privacy option was not properly disabled and the group avatar was not displaying in the group activity post form
* Bug: ReadyLaunch - Fixed an issue where WooCommerce profile tabs were not visible on member profiles when ReadyLaunch was enabled
* Bug: ReadyLaunch - Fixed the display of deleted messages in ReadyLaunch so they now appear visually distinct from normal message text
* Bug: ReadyLaunch - Fixed the notifications dropdown in the admin bar to display correctly when ReadyLaunch is enabled
* Bug: ReadyLaunch - Resolved ReadyLaunch issues including navigation flow, icons, console errors on the comment, and various UI issues
