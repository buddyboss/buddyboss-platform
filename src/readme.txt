=== BuddyBoss Platform ===
Contributors: buddyboss
Requires at least: 4.9.1
Tested up to: 5.4.2
Requires PHP: 5.6.20
Stable tag: 1.4.3
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

= 1.4.3 =
* Activity - Fixed some activity posts hidden from logged out users, and new members
* Activity - Fixed 'All Updates' tab not displaying updates from joined private groups
* Documents - Fixed 404 error on profile breadcrumbs when Documents directory is custom page
* Documents - Fixed download links not working when using various SEO plugins
* Documents - Fixed issues with playing and downloading MP3 file uploads
* Documents - Added media popups for documents uploaded into forums and messages
* Documents - Improved the styling of media popups when Activity component is disabled
* Documents - When clicking 'Edit Folder Privacy' we now auto-display the Edit Folder popup
* Documents - When opening the Edit Folder popup, privacy dropdown now displays correct privacy
* Documents - Improved the file upload previews in the WordPress Media Library
* Photos - In Photos directory, fixed group photos displaying when performing any search query
* Emails - Added option to 'Skip Cropping' for logo, to display any logo dimension
* Compatibility - Fixed PHP 7.4 errors when using WP Engine hosting

= 1.4.2 =
* Documents - Added 'Download' link to Image and PDF file preview popups
* Documents - Fixed search results for content set to 'Only Me' and 'My Connections'
* Documents - Fixed timeline of followers for content set to 'Only Me' and 'My Connections'
* Documents - Fixed content set to 'Only Me' being hidden from my sitewide activity feed
* Documents - Newly added Image and PDF protected files display an icon in Media Library
* Security - Added index.html into 'uploads/bb_documents' for enhanced file protection
* WC Vendors - Fixed products added from Vendor Dashboard not showing in activity feed
* WC Vendors - Fixed products displayed in activity feed not showing featured image

= 1.4.1 =
* Forums - Fixed attached images getting removed after editing forum replies
* Media - Set maximum amount of files uploaded per batch at 10 files
* Security - Preventing executable file uploads from running in browser
* Security - New file uploads go into 'uploads/bb_documents' with .htaccess protection

= 1.4.0 =
* Documents - Added documents for Activity, Profiles, Groups, Messages, Forums
* Documents - Added central Documents page for showing all site documents
* Documents - Added live previews for PDF, MP3, CSS, JS, HTML, Text, Image
* Documents - Added functionality to move and organized Documents into Folders
* Documents - Added settings page for customizable Document File Extensions
* Media - Added enhanced privacy controls for uploaded Photos and Documents
* Media - Added functionality to enter 'Description' in Photo and Document popups
* Media - Comments in single Photo and Document popups now sync with activity comments
* Activity - Added enhanced privacy controls for user-created activity posts
* REST API - New endpoints added for Activity privacy changes
* Groups - Improved performance for groups with many thousands of members
* Groups - Fixed 'Send Message' and 'Edit Group Discussion' not translatable
* Forums - Fixed pagination when using shortcode [bbp-topic-index]
* Forums - Fixed issues when using many levels of threaded replies
* Forums - Fixed empty Forums page displaying with Private Network enabled
* Forums - Fixed HTML elements displaying when pasting content as subscriber
* Forums - Merged in security patches from bbPress 2.6.5
* Profiles - Fixed 'Nickname' field privacy when set as other than 'Public'
* Network Search - Fixed results title when using apostrophe or blank entry
* Emails - Fixed email not sending for site admin changing a user's password
* Icons - Switched the default font iconset from 'dashicons' to 'bb-icons'
* Icons - Added new font icons to represent all common file types
* LearnDash - Fixed 'Hidden' social groups not appearing in backend for LD groups
* MemberPress - Fixed JavaScript conflicts with Stripe credit card field
* WPML - Fixed menu links missing when viewing Groups, Photos, Forums in profile

= 1.3.5 =
* REST API - Added official BuddyBoss REST API
* Activity - Fixed website link previews not all using the same formatting
* Activity - Fixed some websites not properly fetching link preview content
* Text Editor - Fixed text formatting buttons overlapping with media uploader
* Forums - When viewing another user's forums, fixed 'My' text displaying in subtabs
* WooCommerce - Fixed 'Lost Password' link redirecting to WooCommerce 'My Account' area
* Compatibility - Fixed deprecated code errors when using PHP 7.4
* Translations - Updated German (formal) language files

= 1.3.4 =
* Activity - Fixed certain link previews displaying doubled in activity posts
* Profiles - Fixed 'Profile Type' filter not working when any Profile Type is hidden
* Widgets - Improved formatting structure of activities in '(BB) Latest Activities' widget
* Tools - Added 'Repair Community' tool for running 'Update activity favorites data'
* Performance - Optimized code when activating plugin on site with 60,000 or more users
* Translations - Updated German (formal) language files

= 1.3.3 =
* Text Editor - Fixed content formatting reset issues when switching edit buttons
* Forums - Fixed 404 error when paginating 'My Discussions' on user profile page
* Forums - Fixed URL conflict between 'Subscriptions' tab and plugin 'WooCommerce Subscriptions'
* Groups - Fixed 'New Group' and 'Delete Group' not working from admin in multisite
* Groups - Fixed organizer of sub-group unable to send invites if not organizer of parent group
* Groups - Fixed subgroups not in 'Subgroups' tab if group type is hidden from Groups Directory
* LearnDash - Fixed creating a LearnDash group with a social group with Messages disabled

= 1.3.2 =
* Text Editor - New text formatting interface in Messages, Activity, Forums
* Text Editor - New text formatting option for adding a code block
* Profiles - Added 'Telegram' and 'WhatsApp' as options in 'Social Networks' field
* Activity - Allow for commenting on activity posts from custom post types
* Activity - Improved formatting of images in activity posts from custom post types
* Activity - Fixed @mentions moving the cursor position when no results are found
* Activity - Fixed link embeds from Chinese websites displaying broken text characters
* Activity - Fixed link embeds not always fetching the best image and description
* Activity - Fixed group members without access posting into group from sitewide activity
* Notifications - Fixed notification for rejected group join request not clearing
* LearnDash - Fixed alignment of Certificate banner for group with single course
* LearnDash - Code optimization and performance improvements for Grid View ajax
* Multisite - Fixed duplicate BuddyBoss menus when enabling 'BP_ENABLE_MULTIBLOG'
* Translations - Added French language files, credits to Jean-Pierre Michaud
* Translations - Updated German (formal) language files

= 1.3.1 =
* Compatibility - Updated a file that was causing false alerts from Windows Defender

= 1.3.0 =
* Messages - New dropdown option to 'Mark unread' in inbox
* Messages - New dropdown option to 'Hide Conversation' in inbox
* Messages - New dropdown option to 'Delete Conversation' for admins only
* Messages - Display number bubble for conversations with 2+ other people
* Group Messages - Hiding bulk sent messages in inbox, until recipients reply back
* Group Messages - Improved the sent message confirmation screen
* Activity - Fixed photo popup not opening on single activity permalink
* Profiles - Added 'VK' as option in 'Social Networks' profile field
* Translations - Added German (formal) language files

= 1.2.9.1 =
* Groups - Fixed emoji in message content
* Forums - Fixed forum/topic create/edit screen issue

= 1.2.9 =
* Groups - Added new option to send messages to group members from the group
* Messages - Improved the logic for deleting your messages from a conversation
* Messages - When a member is deleted, notifications about their messages are removed
* Profiles - Added 'Twitch' as option in 'Social Networks' profile field
* Profiles - Fixed issue when saving 'Date' field to the date January 1, 1970
* Forums - Fixed minor issues when @mentioning other members in forums
* Forums - Fixed settings for 'Disallow editing after' time limit not working
* Activity - Fixed link preview image not always displaying in activity posts
* Activity - Fixed video embeds not displaying after editing the post in admin
* Connections - Display 'More' button on Connections widget when list is maxed out
* Connections - Fixed inconsistent display of Connections based on profile type 
* Widgets - Fixed the member counts showing incorrect in 'Who's Online' widget
* Email Invites - Added ability to invite between 1 to 20 members at once
* Emails - Now sending emails through the WordPress core wp_mail function
* Admin - Fixed 'Screen Options > Pagination' not saving in admin for Activity and Groups
* Developers - Added code filter to extend DropzoneJS image resize options
* Compatibility - Fixed minor code issues with PHP 7.4

= 1.2.8 =
* Registration - New option to use any Custom URL as your registration form
* Forums - New feature to support @mentions in forums, with notifications
* Forums - Fixed video URL embeds not displaying as playable videos in forum replies
* Forums - Fixed forum reply popup not displaying when 'Post Formatting' is disabled
* Forums - Fixed searching for GIFs in GIPHY panel not working in forum reply popup
* Groups - Fixed groups with & symbol displaying as &amp; in activity feed dropdown
* Groups - Fixed 'a' vs 'an' logic for displaying your group role in English language sites
* Profiles - Fixed 'Dropdown' field type not saving when adding hundreds of options
* Profiles - Fixed 'Profile Type' field type not saving when WordPress role is set to (none)
* Activity - Fixed occasional double posting of Youtube videos with embed URLs
* Messages - Fixed new photos attached to messages displaying in member's Photos tab
* Messages - Fixed maintaining formatting when copying and pasting text into the editor
* Compatibility - Fixed incorrect message URLs with LearnDash and WPML both activated
* Compatibility - Fixed conflict with plugin 'LearnDash Ratings, Reviews and Feedback'
* Translations - Fixed text instances that could not be translated

= 1.2.7 =
* Activity - Fixed error on Mentions tab, when 'Activity tabs' option is enabled
* Registration - Fixed false validation on registration page, when using required fields

= 1.2.6 =
* Widgets - Added option to hide '(BB) Profile Completion' widget when progress hits 100%
* Activity - Fixed issue with deleted users leaving 'Unknown' likes in activity
* Notifications - Fixed certain notifications not clearing after clicking in Notifications dropdown
* Network Search - Added icons for search results coming from plugin LifterLMS
* Developers - Added hooks for adding your own options into all Component Settings pages

= 1.2.5 =
* Messages - Fixed message list not loading more after scrolling down on mobile devices
* Messages - Fixed the conversation start date not matching WordPress timezone settings
* Notifications - Fixed issues with toggling Bulk selection of Notifications
* Groups - Fixed links in group description set to 'open in a new tab' loading in the same tab
* Groups - Fixed buttons for Join/Leave Group not working in Group Type shortcodes
* Groups - When posting a photo from sitewide activity into a group, fixed photo not appearing in group
* Forums - Fixed issues on Forums index page when Gutenberg content is added into the page editor
* Forums - Fixed non-admins not seeing their own replies for Hidden groups, in 'My Discussions' tab
* Forums - Fixed 'Unsubscribe' link in forum subscription emails redirecting to 'Email Preferences'
* Forums - Fixed 'Description' input not displaying correctly for 'Create New Forum' shortcode
* Forums - Fixed custom 'CSS Classes' added to Forums menu not displaying on Forums link
* Forums - Fixed text in forum replies displaying as HTML markup in certain situations
* Activity - Consistent styling for default WordPress embeds and our custom preview embeds
* Activity - Fixed the setting to allow activity stream commenting on forum discussions and replies
* Profiles - Fixed assigning 'Photos' as the default profile tab resulting in 404 error
* Privacy - Fixed issue with Privacy and Terms of Service pages occasionally getting 404 errors
* Emails - Fixed formatting of BuddyBoss emails when viewed in Microsoft Outlook
* Registration - Fixed incorrect 'Mismatch' notice appearing on registration page in certain situations
* Widgets - Added new '(BB) Profile Completion' widget for showing profile completion progress
* Widgets - Fixed logic for '(BB) Members Following Me' widget when displayed on other member profiles
* Widgets - Fixed logic for '(BB) Members I'm Following' widget when displayed on other member profiles
* Widgets - Fixed logic for '(BB) My Connections' widget when displayed on other member profiles
* Multisite - Fixed PHP errors displaying while creating a new site in WordPress Multisite dashboard
* Multisite - Fixed Forum Discussions, Tags, Replies links not working in WordPress Multisite dashboard
* Compatibility - Global fix for all radio and checkbox conflicts with various plugins
* Compatibility - Added settings link at Settings > BuddyPress for third party BuddyPress add-ons
* Compatibility - Fixed membership rules for page restriction not working correctly in 'MemberPress'
* Compatibility - Fixed errors in BuddyBoss > Pages, with plugin 'WPML' (WordPress Multilingual)
* Compatibility - Fixed errors in WordPress dashboard when activating 'WP Mail Logging by MailPoet'
* Translations - Fixed text instances in JavaScript files that could not be translated

= 1.2.4 =
* Errors - Fixed PHP fatal error in certain situations

= 1.2.3 =
* Groups - Updated the 'Send Invites' interface to be more intuitive
* Groups - Added proper formatting for ordered and bulleted lists added into group description
* Groups - Added pagination to Manage Members screen when there are more than 15 group members
* Groups - Fixed 'Screen Options' in admin area not toggling 'Description' option properly
* Groups - Fixed 'Restrict Invitations' logic for invites into subgroups before joining the parent group
* Groups - Fixed issues with not being able to select a group type when editing a group
* Activity - Fixed issue with certain links displaying two embeds; fancy embed and fallback embed
* Activity - Improved the slide down animation when clicking 'Read more' on long activity posts
* Activity - Fixed clicking 'Comment' or 'Reply' on an activity post not scrolling down to the comment box
* Profiles - Fixed issue when trying to follow multiple members in a row on Members directory
* Profiles - Fixed extra space when adding a profile type shortcode to a page while BuddyPanel is disabled
* Profiles - Fixed drag and drop issues with field sets, when there are many field sets with long names
* Profiles - Fixed incorrect date ranges when 'Date' profile field type is added to Profile Search form
* Profiles - Fixed issues with selecting 'Profile Type' profile field and getting incorrect error notices
* Profiles - Fixed 'First Name' and 'Last Name' appearing in Account Privacy settings when they are disabled
* Profiles - Fixed 'Last Name' visibility options when Display Name Format is set to 'First Name & Last Name'
* Profiles - Added proper formatting for lists and underline when using 'Paragraph Text' profile field type
* Forums - Fixed issues when adding multiple forum shortcodes onto the same WordPress page
* Forums - When receiving a notification about a forum reply, the link now scrolls down to the specific reply
* Messages - Fixed the Back button not working right after composing a message, on mobile devices
* Media - Fixed GIF not working when replying to a single activity post on its permalink screen
* Network Search - Added an option to search forums based on their Discussion Tags
* Email Invites - Fixed shortcode {{{inviter.url}}} not working in invitation email template
* Email Invites - Fixed 404 error when trying to log out right after sending email invites
* Widgets - Fixed '(BB) Connections' widget sorting members incorrectly when added to Activity Feed
* Privacy - Fixed issue with extra 'Privacy Policy' pages being created when activating components
* LearnDash - Fixed scheduled courses, lessons and topics incorrectly posting into Activity Feed
* LearnDash - Fixed emails not sending to users registering to the site via enrollment into a Free course
* Compatibility - Fixed conflict when enabling 'Private Network' while allowing account creation in 'WooCommerce'
* Compatibility - Fixed conflict with Email Invites email link when using plugin 'Paid Memberships Pro'
* Compatibility - Fixed Network Search displaying results that are restricted via plugin 'Paid Memberships Pro'
* Compatibility - Fixed verified badge not showing in forums with plugin 'Verified Member for BuddyPress'
* Compatibility - Fixed redirect issues for Forums, Photos and Groups tabs with plugin 'WPML'
* Compatibility - Fixed conficts with plugin 'WordPress SEO Plugin - Rank Math'
* Compatibility - Fixed conficts with plugin 'Hide My WP'
* Translations - Fixed text instances that could not be translated

= 1.2.2 =
* Activity - Fixed certain link embeds URLs not rendering proper results
* Profiles - Fixed member type not displaying in profile cards in 'My Connections' tab
* Profiles - Fixed the 'Mutual Connections' tab incorrectly showing all members
* Profiles - Fixed pagination of members when using [profile type=""] shortcode
* Groups - Fixed pagination of groups when using [group type=""] shortcode
* Groups - Fixed 'Read more' in group activity feed not working when Media component is disabled
* Groups - Added ability to re-order Photos and Albums tabs in customizer
* Forums - Fixed issue when posting a forum reply using only a GIF, with no text
* Forums - Fixed issue with pagination through forums in Forums index
* Notices - Fixed site notices not clearing after closing them, for non-admin members
* Registration - Fixed data not saving on register form when validating incomplete fields
* Widgets - Changed 'More' link in 'Users I'm Following' widget to redirect to 'Following' tab
* Compatibility - Fixed conflict with WP Ultimo while Network Search is enabled
* Compatibility - Added support for 'bp_embed_oembed_html' filter in code
* Documentation - Now syncing all docs in real time from BuddyBoss Resources website

= 1.2.1 =
* Profiles - Fixed field sets not editable on some profile types when Repeater Set is enabled
* Profiles - Fixed pagination of members directory not scrolling back to top of page in mobile
* Groups - Fixed pagination of groups directory not scrolling back to top of page in mobile
* Groups - Fixed option to 'Restrict Invites' into sub-groups, to only members of the parent group
* Forums - Fixed marking discussion as Favorite not always saving after page refresh
* Forums - New settings section to customize 'Forum Profile Slugs'
* Media - Fixed photos added into newly created group album not saving

= 1.2.0 =
* Groups - When logged out users visit a private group, now redirects to Login instead of 404 error
* Groups - New option to display group directories in Grid View, List View, or both with a toggle
* Profiles - New option to display profile directories in Grid View, List View, or both with a toggle
* Profiles - Fixed display of 'Phone' profile field type when added into a Repeater set
* Profiles - Fixed issue with saving international numbers in 'Phone' profile field type
* Profiles - Fixed issue with saving Visibility when editing 'Profile Type' profile field type
* Forums - Added full support for all bbPress forum shortcodes
* Forums - Fixed issue with restoring trashed discussions in WordPress admin
* Forums - Fixed pagination issues when viewing the last page of discussions
* Forums - Fixed displaying original text formatting when editing a forum post
* Media - Now displaying the number of photos in social group 'Photos' tabs
* Media - Fixed syncing of photo deletion between frontend and admin Media Library
* Media - Fixed photo lazy loading when selecting from existing photos to add into an album
* Notices - Now displaying site notices on all WordPress pages
* Notices - Added support for entering shortcodes into site notices
* Email Invites - Removed the avatar and name from email template sent to invited recipients
* Widgets - Added tooltips to display the name of each member in both 'Following' widgets
* Akismet - Improved styling for 'Spam' icon in activity feed when Akismet is configured
* LearnDash - New option to display 'My Courses' menu for logged in members
* LearnDash - Fixed 'Courses' tab on groups not applying custom label for 'Courses' text
* Compatibility - Improved support for many plugins that 'Require bbPress' and 'Require BuddyPress'
* Coding Standards - General code refactoring, validated through PHP_CodeSniffer
* Translations - Added Hungarian language files, credits to Tamas Prepost
* Documentation - Profile Grid vs List View
* Documentation - Group Grid vs List View
* Documentation - Forum Shortcodes
* Documentation - New Forum Shortcode
* Documentation - My Courses Profile Menu

= 1.1.9 =
* Performance - Lazy load iframes (video embeds) in Activity
* Activity - New option to display activity in separate tabs based on activity type
* Activity - New widget '(BB) Users Following Me' to display all members following the logged-in user
* Activity - When embedding links with no additional text, show the content preview and hide link URL
* Activity - When embedding a Youtube link that cannot fetch a video preview, fall back to image preview
* Activity - Fixed link previews when embedding links from a Facebook url
* Activity - Fixed link previews when embedding links from an AMP url
* Profiles - When hiding members of a profile type from Members Directory, hide from '(BB) Members' widget
* Groups - Fixed issue with deleting members from a group, when LearnDash is enabled
* Groups - Fixed filtering group types set to be hidden from Groups directory, in all scenarios 
* Groups - When a group member changes their Name, now updates their name in previous group activity feeds
* Groups - When editing groups from backend, fixed dropdown list of available Group Types
* Groups - When auto-creating a group from a LearnDash group, the group members now show correct join date
* Forums - When adding tags to a discussion or reply, now showing suggested tags as you type
* Forums - When replying to a sub-forum in a group, now displaying an activity post in the group feed
* Media - Fixed issue with deleting albums from groups
* Media - Fixed displaying photos uploaded into groups in the global Photos page, based on group privacy
* Media - When deleting an image from activity, fixed auto-deleting the image from photos tab
* Email Invites - If an invited email is already a member, providing proper validation now
* Email Invites - Fixed 'Email Invites' tab showing on other member profiles, when viewing as Admin
* Email Invites - Fixed WordPress error when sending invites, on some servers
* Registration - Reduced character minimum for 'Nickname' to 3 characters, previously was 4
* Registration - Added validation telling users that underscores are not allowed for 'Nickname'
* Registration - Fixed validation when 'Profile Type' field is required and in a non-signup field set
* Registration - Nicer 'Mismatch' validation when Email and Confirm Email do not match
* Registration - Fixed activation email not sending in multisite, in some servers
* MemberPress + BuddyPress Integration - Fixed Name fields when registering to a member level
* MemberPress + BuddyPress Integration - Added the 'Membership' links into profile dropdown
* Compatibility - Improved support for plugins that 'Require bbPress' and 'Require BuddyPress'
* Documentation - Forum Settings
* Documentation - Activity Tabs
* Documentation - Registration Confirm Email/Password
* Documentation - Theme Header (Mobile)

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


