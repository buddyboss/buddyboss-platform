=== BuddyBoss Platform ===
Contributors: buddyboss
Requires at least: 4.9.1
Tested up to: 6.3.2
Requires PHP: 5.6.20
Stable tag: 2.4.50
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

== Changelog ==

= 2.4.50 =
* Enhancement: Core - Background process logic for reactions updated
* Enhancement: Media - Provide mechanism to search media by description in the directory and global search
* Enhancement: Profiles - Added TikTok to Social Network Links
* Bug: Activity - Issues were found with tagging more than 1 member in activity post's comments
* Bug: Activity - When activity tabs and the relevant activity setting was turned on, users were unable to see their own posts on the activity feed.
* Bug: Core - Adding a photo was not working from the user profile, Select Photo tab in Albums
* Bug: Core - Changing passwords from the account page was not working
* Bug: Core - Remove connection confirmation pop up was showing twice one after another
* Bug: Forums - Changing a Forums privacy setting from public to private, the forum would automatically revert back to public
* Bug: Forums - If a Forums page was not set then Forums content was populating the Blog page
* Bug: Forums - When moving a discussion from group forum back to standalone forum, the discussion remained in the group
* Bug: Forums - Youtube links were not shown correctly in forum topic replies with / without additional text
* Bug: Messages - Emoji on messaging were not working properly
* Bug: Messages - When sending a group message, the recipient text box was clickable and showed the text box
* Bug: Multisite - BuddyBoss default users import is causing a Fatal error in MultiSite
* Bug: Notifications - Notification were being sent when user created a reply even after the user had then left a hidden or private group
* Bug: Platform - New notification message added to Media settings Direct access when direct access to media is blocked by Offload Media
* Bug: Profiles - shortcode: [group type=”competition”], from BuddyPress User Profile Tab Creator Pro the cover image on the profile was not displaying
* Bug: Report - Typo error in Report/Block member menu in the message sidebar for single private message
* Bug: Styling - Search Form reset button styling issue when inviting users during group creation process on the frontend
* Bug: Translations - Translation issue in Loco Translate with translations switching back to English
* Bug: Widgets - Some strings were not able to translate dashboard widget

= 2.4.41 =
* Bug: Activity - When commenting or replying to a comment on a post some users were seeing “Server error or connection is lost. Please try again later." message
* Bug: Core - When selecting to generate members unique identifier (profile url), the process was causing a crash due to a background code issue which has now bee refactored

= 2.4.40 =
* New Feature! - Allow users to Edit Activity Comments
* Bug: Activity - Activity search : Search results are getting incorrect.
* Bug: Activity - Video uploading issue on Activity feed due to missing video thumbnail
* Bug: Core - "Relaxed" Emoji is not showing correctly
* Bug: Core - Added a 'Successfully Saved' message in the plugin when saving settings
* Bug: Core - Fatal error when registering users with Magyar language selected
* Bug: Core - Profile, photos tab was showing delete and select all buttons even with no uploaded media
* Bug: Core - Text Smilies were not converting to emojis when first added in discussions reply.
* Bug: Core - Wordpress dashboard shown in mobile size, profile dropdown arrow in admin bar would position incorrectly
* Bug: Custom Development - 'groups_join_group' hook not called when member is added to BuddyBoss Group via LearnDash sync
* Bug: Forums - Changing the parent forum from backend was not working for the forums that is associated with groups
* Bug: Forums - Discussion shortcodes not returning the correct results
* Bug: Groups - Subscription emails were delayed when saving the new group feed and updating the group details in frontend
* Bug: Groups - When subgroup restrict access setting is active a new message alerting users to first request access to the parent group will appear if a user tries to access a subgroup first
* Bug: LearnDash - Undefined variable $post warning in LearnDash topics removed
* Bug: Messages - Image link was broken for Messages in Email
* Bug: Multisite - Profile search page is not working in admin area if platform is network activated
* Bug: The Events Calendar - When the private website option is enabled from the BuddyBoss platform, the filters in the Events Calendar was not working
* Bug: Widgets - Who’s Online widget was not accurately showing members online status

= 2.4.30 =
* Enhancement: Activity - database alterations made in preparations for future activity features
* Enhancement: Core - Allow options within the new blacklist/whitelist feature so that user can block the email plus aliases.
* Enhancement: Forums - Automatically reconnect an existing forum when reenabling the forum within a group
* Bug: Core - API issues addressed when activating BuddyBoss platform plugin
* Bug: Core - Design issue when posting a discussion when using Firefox browser
* Bug: Core - Pagination problem occurred on pages where the group type shortcode was used
* Bug: Core - Paging was disabled when fetching media, videos and documents based on specific ids
* Bug: Forums - Add direct link for the grouped forum instead on the Discussions links to removed the unnecessary redirect
* Bug: Forums - Discussions pagination was not working when setting the forum page as the home page
* Bug: Forums - Forum was not importing from Invision
* Bug: Groups - When Group Message feature is disbaled, existing messages associated to groups are now removed from users inbox
* Bug: Messages - Attached media in a group message sent by a group member was not viewable by other members
* Bug: Messages - Messages thread order was not staying in the correct order when paginating
* Bug: Multisite - BuddyBoss component pages were not assigned correctly across the subsites
* Bug: Profiles - Pending signup user profile were visible on the frontend in some cases
* Bug: Profiles - Removed Google+ from 'Social Networks' profile field
* Bug: Profiles - Update Twitter name to X on all Social Links
* Bug: Tools - SMF forum import tool could not keep the hierarchical relationships between forum > discussion > reply

= 2.4.20 =
* Enhancement: Core - The Background Process working when suspending and un-suspending users got stuck creating an infinite loop
* Enhancement: Custom Development - Add the missing hooks into the Activity Comments
* Bug: Activity - New Activity Post ‘Privacy’ option had white spacing above the options
* Bug: Activity - When editing a posts Giff within Discussion or Discussion reply, the Giff still remained in the Activity Feed.
* Bug: Core - Add default parameters and documentation to media, documents and videos "get" functions
* Bug: Core - Display name setting code correction
* Bug: Core - Fix typo in the core template
* Bug: Core - Hyperlink was not working in the Safari browser on Activity feed posts
* Bug: Core - Profile field "website" did not check for URL validation
* Bug: Core - Removed the unsupported URL preview that WordPress Embed shows when not supporting to just showing the link in this case
* Bug: Core - When WP DEBUG is turned on with Error Display on, this displayed warning on the "Admin" Profile page
* Bug: Forums - When adding YouTube link before text caused the preview to glitch
* Bug: Forums - Forum and discussion Image Missing in Email
* Bug: Forums - Multilevel Forums were not displaying correctly on BB Forums List widget.
* Bug: Forums - Topic in hidden/private forum is still visible within the users profile
* Bug: Forums - URL previews were not showing correctly for both topics and replies.
* Bug: Forums - When going back to a draft post then the ‘Post’ button would be greyed out
* Bug: Media - When leaving a Private or Hidden groups, users stall had permissions to upload and download media from that group
* Bug: Messages - Message inbox the mark as Unread/Read option was showing different in the message to the sidebar ellipses option
* Bug: Notifications - Deleted group posts still showed the Notification and return Not Found

= 2.4.11 =
* New Feature! - This new 'Restrict Registration' feature allows you to restrict or allow specific email or domains from registering on your site

= 2.4.10 =
* Bug: Activity - If a link contains a @user mention, the rest of the link was being cut off
* Bug: Activity - Web fall back pages were showing a warning if it contains a link preview
* Bug: Core - Identified filters refinement and bugs by taking reference from BBPress recent releases
* Bug: Core - Removed the unnecessary "!important" tags in the Theme
* Bug: Email - Unsubscribe Link not redirecting when a user logs in after clicking the link
* Bug: Groups - Group Invites were being sent even if it was restricted by the user
* Bug: Groups - Groups were not being reassigned to another user if the Organizer was deleted
* Bug: Media - Incorrect icon was shown for image files when uploaded to Documents from a user profile
* Bug: Messages - New line (Shift+Enter) was not working correctly and lists were not formatted appropriately on Notification emails
* Bug: Notifications - Web Push Notifications were not sending notifications for Group Activity feed posts.
* Bug: Profiles - Embedding a TikTok link that had the same username as another member caused the link to redirect the users profile
* Bug: Translations - Icons were not showing for File Extensions when RTL was enabled

= 2.4.00 =
* Enhancement: Core - Identified code improvements across different components by taking reference from BBPress recent releases
* Enhancement: Core - Improved the handling of data migration when switching between release versions
* Bug: Activity - Link previews were not displaying when referencing the same site
* Bug: Elementor - Profile Type shortcodes caused layout conflicts when used within columns
* Bug: Forums - Private forums were redirecting to 404 page instead of login for logged out users
* Bug: Forums - Shortcode compatibility when using multiple Forums, Topics, and Replies on a single page
* Bug: Forums - UI improvements to media upload modal for Safari browser on iOS devices
* Bug: Groups - Subgroups could not invite Organizers of the parent group
* Bug: LearnDash - Updated the ‘Topic Search > Enrolled Only’ setting to ensure search results are only returned if a member is enrolled into the course
* Bug: Messages - AM/PM in private messages list was showing different results due to timezone differences
* Bug: Widgets - Groups widget returned zero results to users who were not a member of any group

= 2.3.91 =
* Bug: Groups - Mentioning users inside a group didn’t show all members
* Bug: MemberPress - Fixed conflict for user registration where ‘username is required’ error was shown

= 2.3.90 =
* New Feature! Allow specific profiles types to send messages without being connected
* Enhancement: Core - Identified code improvements across different components by taking reference from BuddyPress recent releases
* Enhancement: Tools - New repair option to recalculate group member count for each group
* Bug: Activity - Deleting only the 'activity post' of a discussion or any post from other component was also deleting the attachment from the original discussion
* Bug: Activity - Twitter link previews UI resolved to not return a console error
* Bug: Activity - Videos on lessons were showing double in the news feed when the lesson was automatically fetched after creation
* Bug: Core - Fatal error resolved when applying "the_title" filter
* Bug: Core - Identified filters refinement and bugs by taking reference from BuddyPress most recent releases
* Bug: Core - Identified security issues by taking reference from BBPress most recent releases
* Bug: Core - Identified security Issues by taking reference from BuddyPress most recent releases
* Bug: Core - Resolved fatal error whenever bbp_db() function was called
* Bug: Core - Site notice appears twice on subgroup overview page
* Bug: Core - Unnecessary use of "!important" tag in message component
* Bug: Core - Unnecessary use of "!important" tag in theme related to social groups component
* Bug: Core - When clicking the unsubscribe link while logged in as different user, a success error message was displayed instead of alert error message
* Bug: Core - Wrong translation string for "You will no longer receive emails of new discussions in groups you are subscribed to"
* Bug: Forums - Could not add a hyperlink to text that began with italic/bold formatting
* Bug: Forums - Data was not accurate when using specific forum shortcodes on a single page
* Bug: Geodirectory - Fixed compatibility issue with a profile’s Reviews tab showing incorrect stars
* Bug: Groups - Comments were not visible to logged in users viewing a public group whilst not being a group member
* Bug: Groups - Courses tab was not working whenever the courses slug was changed into a different language
* Bug: Groups - Subgroups were not displaying whenever going to User > Profile > Groups
* Bug: Media - Documents for public groups were not showing in the app for logged out users
* Bug: Media - Wrong icon style was used for the Create album button
* Bug: MemberPress - Upload file now compatible when using custom registration or checkout forms
* Bug: Multisite - Database error for user activity on Multisite Network Installation after updating to v 2.3.1
* Bug: Multisite - Members page previously displayed 404 error whenever Profile Link's Link Format was configured to be a "Unique Identifier"
* Bug: Profiles - Profile type filter was not showing plural labels on the Members directory

= 2.3.81 =
* Bug: Core - Fixed access to public pages as a logged out user so that it no longer redirects to the login form

= 2.3.80 =
* Bug: Activity - Post button was inconsistent when trying to reply to activity post.
* Bug: Activity - Unable to click the activity comments from group search when a member was tagged using "@" function
* Bug: Activity - While posting a link directly from the app with www, no link preview is shown
* Bug: Core - Node version issue on `npm install` script after a repo was cloned
* Bug: Core - Platform main components page showed incorrect count for "All" and "Active" components
* Bug: Email Invites - Revoking the email invite from admin was redirecting the user to post list page
* Bug: Forums - Private forum were redirecting to 404 instead of login screen for logged out users
* Bug: Groups - Child group was displaying parent group description from subgroup page
* Bug: Groups - Super Sticky was incorrectly showing on the admin panel for discussions within a Group
* Bug: Gutenberg - Discussions on editor added extreme spacing between lines
* Bug: LifterLMS - Deleting an user who is a Course Participant did not remove the user from Participants list
* Bug: Media - Next/Previous navigation on media modal accessible even while commenting
* Bug: Media - "Create new album" translation was missing on the photos page
* Bug: Media - Document folder meta was not being saved in the database using bp_document_folder_add_meta function
* Bug: Media - Move file tooltip not clear and inconsistent
* Bug: Media - Unable to download folders due to size constraints
* Bug: Media - When uploading media, we will now handle the error messaging dynamically
* Bug: Messages - Error in POST API endpoint when creating a message
* Bug: Messages - Mark as unread was not working when object cache is enabled
* Bug: Moderation - Clear API cache when member blocked/unblocked
* Bug: Notifications - Follow notification still shows in dropdown after being unfollowed
* Bug: Notifications - Group subscriptions page was broken on iOS Safari mobile browser
* Bug: Notifications - Replied with GIF Notification were showing as empty
* Bug: Profiles - Deleting a field in the fieldset was not reflected and when refreshing the page an error was displayed
* Bug: The Events Calendar - Events page was showing blank instead of the login screen when visited from the guest user on a private site

= 2.3.70 =
* Enhancement: Network Search - Extended search results to only show LearnDash lessons from a course that a user has enrolled into. This is configured in the LearnDash Settings > Lesson Search
* Enhancement: Performance improvements to the followers and following feature by adding pagination to the background process when notifications are being sent
* Bug: Blog - Logged out users on private networks could had an unresponsive ‘Load More’ when attempting to load additional articles
* Bug: Core - Code refactoring of the Activity and Forums by removing unnecessary !important tags
* Bug: Forums - Pagination loading issue when forum was set to homepage
* Bug: Forums - Using forum shortcodes whilst having a forum widget on the sidebar removed the ability to subscribe or create new discussions
* Bug: Forums - Resolved performance issue where discussions took longer to load as users subscribed to many discussions
* Bug: Groups - Cleaned PHP notice when multiple groups were selected as part of the 'groups_get_group_members' function
* Bug: Media - New albums created from the Videos page were not displaying due to Redis Cache compatibility
* Bug: Media - Images and video thumbnails were not being cache when not using Symlinks
* Bug: Media - Image previews of documents after being renamed were not available
* Bug: Media - Updated edit buttons to use correct case sensitive
* Bug: Members - Search results returned incorrect values if a standard profile field was switched to a repeater field
* Bug: Messages - Search terms were being removed from the search box when a search returned multiple results and was scrolled down
* Bug: Multisite - Database error related to an unknown column ‘meta_id’
* Bug: Network Search - Improved compatibility with Object Caching when searching for Groups
* Bug: Notifications - Updated dropdown labels to use correct case sensitive
* Bug: Profile - Improved support for uploading Profile photos using the built in Safari browser webcam capture
* Bug: Widgets - Recent Topics and Recent Replies widgets for Forums were not loading content if custom prefixes were applied to the database table
* Bug: Widgets - Sell All link on the Recent Active Members widget was unresponsive
* Bug: Widgets - Group widget not showing hidden members of the same group when applied to Members Page
* Bug: Elementor - Improved compatibility with the Elementor Maintenance mode
* Bug: Elementor - Activity block content and excerpt conflicted with replies whenever multiple mentions were added
* Bug: Geodirectory - Dropdown menu on Favorites and Listings tab was unresponsive
* Bug: MemberPress - New error message if a user attempts to use an email address as a username/nickname, this resolves issues where @mentions cannot be sent due to invalid character
* Bug: Rankmath - Fixed an issue where titles and meta details were not being applied to forum or discussions

= 2.3.60 =
* Notifications - Optimized the performance of the default notification preference workflow for members
* Groups - Addressed the member count issue in groups when one of the members is suspended
* Forums - Introduced link preview support for Forums discussions and replies
* Forums - Resolved the issue where the shortcode for hidden group discussions would throw permission errors for group members
* Forums - Fixed the issue with the discussion reply 'post' button not being enabled in the responsive view on Android
* Forums - Resolved the layout issue with the 'Recent Replies' widget when using third-party Themes
* Forums - Fixed the layout issue with discussion replies pagination when the page number is out of range
* Activity - Handled the issue where the 'post' button was not accessible in the responsive view when the post content is significant
* Media - Updated the media database table schema by adding indexing
* Media - Addressed a UI issue with the search form in the documents sub-folder screen
* Messages - Enhanced the upload and send media workflow performance in private messages. This improvement optimizes the way media data is stored and deleted, resulting in a faster and smoother messaging experience
* Moderation - Resolved the issue of suspended member group activity single screen throwing a 404 error
* Network Search - Refactored the code to allow for easy extension of the feature
* Core - Provided an option to retain link previews and embeds even after deleting the associated links in activity, forum discussions, and replies
* Core - Improved the user experience by displaying a loading icon when a search form is submitted or reset
* Core - Improved the user experience of the GIPHY option in the dashboard by hiding sensitive text. Users can now toggle the visibility of sensitive content with an eye icon
* Core - Handled a critical issue with the Presence PHP file, ensuring correct detection of active and inactive member status even on non-English language sites
* Core - Handled a critical conflict with the 'BuddyBoss App' plugin build screen
* REST API - Added API support for link previews in forums discussions and replies
* REST API - Updated API support to align with the messages media workflow improvement
* REST API - Handled the issue where the photos and videos reply option was disabled in the API
* Learndash - Addressed the issue with social group members roles when sync is enabled for the Learndash group and social group

= 2.3.50 =
* Profiles - Addressed the issue where the password updated email was triggered when the account email is updated in the profile
* Profiles - Resolved the incorrect last active stats issue on Members and profile pages
* Notifications - Introduced new notification type to trigger when members create a reply to a blog post comment
* Notifications - Improved the business logic for notification preferences, now loading from admin default if not updated
* Notifications - Improved to send @mention email for blog post comments
* Groups - Resolved the hidden group not showing issue for WP CLI commands
* Groups - Resolved the activity not getting created issue for WP CLI commands
* Groups - Handled the forum option not showing issue while creating a group when the forums slug is updated
* Forums - Handled create discussion UX issue when selecting auto-generated tags
* Activity - Improved the @mention dropdown by providing load more pagination
* Activity - Fixed the multiple @mention option not working issue
* Activity - Resolved the post form GIF search option UX issue
* Media - Resolved the video thumbnail issue for videos with portrait orientation or a small-sized
* Media - Fixed the documents search not working issue when the document is in the folder
* Media - Addressed the media modal issue that was not showing the option to like and comment
* Messages - Fixed the duplicate text issue when copy-pasting in the send message editor
* Moderation - Improved the logic for handling suspended and blocked members content in Social groups, Network Search, Forums, Activity, and Widgets
* Moderation - Handled the report member option UX issue, preventing multiple reports
* Moderation - Addressed a minor accessibility issue with the 'report group' option
* Network Search - Addressed the incorrect count issue when modifying search results using a hook
* Core - Improvement by loading minified JS and CSS for third-party libraries
* Core - Fixed the 'Repair user nicknames' tool that was not working as expected
* REST API - Provided API support for social groups, activity, and forums moderation related updates
* REST API - Handled the Forum discussion images URL issue in the API
* REST API - Addressed the selected profile type not returning issue in the users endpoint
* REST API - Resolved the join group action not working issue in the API for private and hidden groups when auto-approval is enabled
* REST API - Fixed the read more option not working issue in the API for activity when it contains a link

= 2.3.42 =
* Registration - Resolved invited users can't register issue when registration option is disabled for non-members

= 2.3.41 =
* Profiles - Improved members profile link by reducing the length auto-generated unique identifier

= 2.3.4 =
* Profiles - Resolved formatting issues with paragraph type fields in profile fields
* Groups - Handled incorrect group count issue on the directory screen when the sub-groups option is enabled
* Forums - Enhanced performance of forum discussion favorites by restructuring their data
* Forums - Resolved performance issues with discussions having a large number of replies through efficient pagination
* Forums - Fixed a database error that occurred when updating a forum reply with attached media
* Forums - Introduced a new parameter for the [bbp-reply-form] shortcode to pass the discussion ID
* Forums - Fixed the issue where an auto-generated discussion would have preceding empty space
* Activity - Fixed the problem with the discard draft option in the activity form
* Activity - Handled the issue with auto-generated activity for comments links on custom post types not being clickable
* Activity - Addressed the formatting issue where highlighted text did not maintain proper formatting when a link was applied
* Media - Addressed the issue where the 'move photos' option was not functioning as expected
* Media - Provided hooks to modify privacy options for media files
* Registration - Enhanced invite flow to prevent multiple registrations using the same email invite
* Core - Updated performance API MU plugin file name for improved consistency
* Core - Improved code to load templates with block themes
* REST API - Resolved the problem where the profile avatar was not returning in the notification endpoint
* REST API - Fixed the issue where the group privacy option was being removed when updated using the group settings endpoint

= 2.3.3 =
* Profiles - Resolved critical issue of duplicate unique identifiers
* Profiles - Handled 'Social Network' field with multiple types shows random modal issue when redirected to the profile
* Notifications - Handled small notification read/unread issue when newly registered members login for the first time
* Forums - Handled forum discussion uploaded text file preview issue
* Forums - Handled forum discussion and reply tags suggestions dropdown layout issue
* Forums - Handled create discussion and reply formatting issues when an option is disabled from the settings
* Forums - Handled broken layout on the single forum reply screen
* Activity - Handled activity comment form, post button disabled issue when specific steps followed
* Messages - Handled message screen and dropdown 'sent a video' label inconsistency
* Emails - Resolved UI issue in group and blog post emails when view group/post button labels were translated to non-English languages
* Core - Improved the search form across the network by adding an option to clear the search field
* Core - Provided hooks to update activities pagination and number of entries to process in the background process
* Core - Handled messages and notification dropdown specific performance issues by refactoring code
* REST API - Handled profile fields endpoint returns HTML entity name for special characters
* REST API - Handled group settings endpoint permission issue for the group organizer

= 2.3.2 =
* Profiles - Handled non-selectable profile types not showing issue in Edit Profile even when configured from the admin dashboard
* Profiles - Handled profile types field validation issue for administrator and editor role members
* Profiles - Small improvement for the 'add user' action in the admin dashboard by assigning a selected role instead of the default profile type role configured
* Profiles - Handled small performance issue for member search action with the help of cache
* Groups - Handled delete activity post permission issue in the social group for the group organizer
* Forums - Handled forums search not working issue when search by 'discussion tag' option enabled in network search
* Forums - Handled quick reply form formatting option not working issue on forum discussion auto-generated activity
* Activity - Handled empty markup getting added issue when editing activity post with @mention
* Media - Small improvements to media link by removing trailing slash when symlink is disabled
* Media - Handled uploaded video default thumbnail issue in Safari browser
* Messages - Handled error notice on messages screen when visiting triggered push notification
* Emails - Handled email template layout issue for the iOS email app
* Moderation - Handled newly registered member display name shows as 'Unknown Member' when moderation component is enabled
* Network Search - Handled network search page results content showing shortcode issue
* Registration - Handled register page validation issue when paragraph and profile type fields were added together
* Login - Handled small typo issue when only the 'Terms of Service' page is configured
* Core - Small performance and security improvement by updating a bunch of JS libraries

= 2.3.1.2 =
* Forums - Handled forum discussion and reply form disabled post button cache issue

= 2.3.1.1 =
* Profiles - Small icon fix for 'Profile Links' option in the dashboard

= 2.3.1 =
* Profiles - Provided the option to replace usernames with unique IDs to secure member profile URLs
* Profiles - Handled profile field instructions small formatting issue
* Profiles - Handled profile search issue with profile fields configured as 'only me' privacy
* Profiles - Handled members count formatting issues across the network as well as APIs
* Groups - Handled group message with unsupported media format, error message not closing issue
* Groups - Handled group documents pagination issue
* Activity - Handled activity posts liked members tooltip formatting issue
* Media - Handled media modal 'Download' button UI issue when 'BuddyBoss Theme' is not active
* Messages - Small improvement for shared socket connection update for multiple browser tabs using Pusher
* Messages - Handled selected member in compose message is not appearing when the mention name is updated and a message is composed of the profile
* Messages - Handled video embed not showing properly in messages
* Connections - Provided option to auto-follow when members connect
* Moderation - Handled critical issues in the settings API when all moderation options are disabled
* Core - Improved and optimized presence API performance for online/offline status using MU Plugin and independent PHP file
* Core - Small improvement for Activity, Activity comment, media description, forums discussion, and reply form by not allowing to submit when no text or media uploaded
* Coding Standards - Small code refactoring to fix PHP warnings for non-logged-in users
* Events Calendar Pro - Handled events page not working conflict when private site enabled
* REST API - Handled incorrect replies order in the profile issue in the API
* REST API - Handled group types in a non-English language not returning in the API
* REST API - Handled update sub-group privacy using API removes parent group settings
* REST API - Handled invited organizer returns in the API before accepting the request

= 2.3.0 =
* Profiles - Handled a small UX issue in the profile fields screen in the dashboard
* Notifications - Small improvements to not trigger multiple notifications for forum discussion and reply when members are mentioned
* Groups - Handled group header description alignment UI issues
* Groups - Handled groups directory and single group SEO title and description not rendering issue
* Forums - Handled forum discussions and replies uploaded image sequence issue
* Forums - Handled forum discussion and replies pagination not working issue on other members profiles for logged-in member
* Forums - Handled search forum critical issue when the network search component is disabled
* Forums - Handled social group associated forum and its child forums visibility issue based on group privacy
* Forums - Handled missing fields in the replies tab when a logged-in member is viewing other members profile
* Forums - Handled 'XenForo' import not working issue
* Activity - Provided option to show relevant activities in the 'Latest Activities' widget
* Messages - Handled send message editor formatting toolbar layout issue when the media component is disabled
* Messages - Handled UI issue when a new thread is created from the messages screen
* Core - Small performance update for non-logged-in users
* Core - Small improvements for selected page/tab class logic
* Core - Icon Pack updated with latest icons
* Coding Standards - Significant code refactoring to fix PHP 8 warnings and notices
* Coding Standards - Significant code refactoring to fix PHP 8.2 deprecation errors, warnings, and notices
* REST API - Code refactoring to fix PHP 8 and 8.2 warnings and notices in the API
* REST API - Handled profile type endpoint text format issue when it has special characters
* REST API - Handled forum replies endpoint not returning excerpt issue in the response
* Learndash - Handled social group privacy settings getting updated issue when the 'LearnDash Group Sync' option is enabled and the learndash group is updated
* Learndash - Handled URL query var not working issue on lesson and topic screen when 'Nested URLs' option is enabled
* Compatibility - Handled Jetpack plugin widget visibility option not showing conflict

= 2.2.9.1 =
* Notifications - Handled repeated email notification issues by removing duplicate forum subscription entries
* Notifications - Handled triggering notification when an activity post is updated in a social group
* Notifications - Handled notification broken template issue when media uploaded in a social group
* Notifications - Handled 'subscriptions' page 404 conflict with the 'WooCommerce Subscriptions' plugin
* Notifications - Handled defaults disabled issue for force enabled notifications types

= 2.2.9 =
* Profiles - Handled incorrect connection count shows in profile when any connected member is suspended
* Groups - Handled group description small formatting issue on render
* Groups - Handled update group details notification not working as expected
* Groups - Handled critical issues when updating groups from the dashboard and the forums component is disabled
* Forums - Handled forum discussion and reply tags field dropdown not working as expected
* Forums - Handled new discussion form validation UI issue on submit
* Forums - Handled discussion tags not showing issues in a group forum when it has an image
* Forums - Handled 'Recent Discussions' widget small floating content UI issue
* Forums - Handled reply editor lagging issue while typing at normal speed
* Forums - Handled discussion email notification for subscribed members, the link doesn't take to the discussion on login
* Emails - Handled group email notification template UI issue in the responsive view
* Moderation - Improved the suspended and blocked members content logic in the messaging module
* Network Search - Handled course layout issue in the search results by handling excerpt formatting
* Network Search - Handled search blog post by tag not working issue
* Network Search - Handled activity posts and comments in private/hidden groups not showing for group members
* Network Search - Handled non-members can search hidden group associated forums
* Core - Small code refactoring to pull and render the Icon Pack
* Core - Small improvement to allow translation for all icon names from the Icon Pack
* Core - Small improvement to not show specific options in the dashboard when relevant modules are disabled
* Coding Standards - Small code refactoring to fix PHP 8 warnings and notices
* REST API - Handled activity feed endpoint missing timeline filters in the API
* REST API - Handled group description small formatting issue in the API
* REST API - Handled courses related strings translation not working issue
* REST API - Small API updates for the latest moderation changes in the messaging module
* REST API - Handled learndash disable comment option not working issue in the API for relevant activity posts
* REST API - Handled profile types endpoint menu order not working issue in the API
* REST API - Handled profile types visibility settings not working issue in the API
* REST API - Handled group details endpoint critical issue in the API
* Learndash - Handled password rest link not working issue when member registered purchasing the course as a guest using Paypal
* Compatibility - Handled news feed page restriction not working issue

= 2.2.8 =
* Notifications - Provided option to subscribe to group notifications from a single group screen
* Notifications - Provided new notification types to trigger when members create new activity or discussion in a group
* Core - Handled migration for new subscription workflow for groups and associated forums
* REST API - Provided API support for new subscription workflow for groups

= 2.2.7.1 =
* Network Search - Handled network search not working critical issue for database table with custom prefix

= 2.2.7 =
* Notifications - Improved members active/inactive presence logic for Push Notifications
* Activity - Handled activity post with exact 4 media fails to follow right sequence order selected
* Login - Handled 'Terms of Service' and 'Privacy Policy' inline JS not working issue
* Messages - Improved group thread join/left notices for their members
* Emails - Handled private messages email template reply button label layout issue when a translated string is long
* Moderation - Improved the suspended and blocked members @mention logic in the network
* Moderation - Improved the suspended and blocked members notifications logic for both new and existing
* Moderation - Handled activity feed comment notification, redirection URL not working issue
* Network Search - Handled search results improvements to not show groups, forums, discussions, replies, and photos if not accessible
* Core - Handled active/inactive member presence logic returning console error issue in the background
* Core - Handled small formatting issue in the editor for multiple new lines
* Core - Handled translation issue for dropzone uploader across the network
* Core - Provided medium editor placeholder texts available to be translatable
* REST API - Improved the suspended and blocked members notification logic in the API
* REST API - Small improvement for members presence active/inactive logic for push notifications in the API
* REST API - Handled deleted and suspended members names to show 'Unknown Member' across the network in the API
* REST API - Provided join/left group thread notices small update API support
* REST API - Provided API support for improved moderation @mention logic
* REST API - Handled profile field and Album name with '&' character shows HTML entity in the API response
* REST API - Handled profile search not working issue with the '&' character in the API
* REST API - Handled photos order issue when description updated with the endpoint
* REST API - Small improvement in the API to fetch group specific files using the documents endpoint
* Elementor - Handled 'BB Forum Search form' widget alignment and UI issues on the RTL language site

= 2.2.6.1 =
* Learndash - Handled update group critical issue for php version < 8.0

= 2.2.6 =
* Profiles - Improved profile and account settings UI and layout in the responsive view
* Profiles - Handled a small UX issue by not allowing multiple selections for Profile and Cover photo upload
* Notifications - Handled 'posts from members following' email template unsubscribe link issue
* Groups - Handled group types dropdown doesn't consider the 'dropdown order' issue
* Forums - Improved subscription workflow for forums and discussion by providing a dedicated screen under notification preferences
* Activity - Handled activity with just media getting deleted issue on multiple edits
* Activity - Handled activity form gets submitted issue on selecting formatting options in the responsive view
* Activity - Small performance code refactoring for activity action buttons
* Activity - Handled single activity small layout issue with media in responsive view
* Messages - Improved send message UX in the messages sidebar when Pusher is enabled
* Messages - Improved UX to show archived message screen
* Moderation - Improved the suspended and blocked members media access logic specific to groups
* Network Search - Handled search results page pagination issue when per page count updated using hook
* REST API - Provided support for new subscription workflow for forums and discussions
* REST API - Handled activity with media getting deleted on multiple edits in the API

= 2.2.5 =
* Notifications - Provided new notification type to trigger when members on the network follow other members
* Profiles - Handled duplicate profile fieldset issue when updated from the dashboard
* Profiles - Handled fieldset saving empty fields and not getting deleted issues
* Forums - Handled 'Recent Replies' widget layout and alignment issue
* Forums - Handled edit reply option not working critical issue
* Forums - Handled auto-generated activity for discussion issues when comments were added from the dashboard
* Forums - Handled auto-generated activity for discussion backslash not showing issue when it exists
* Forums - Handled forum widget 'Parent forum ID' not working issue
* Private Network - Handled private website 'Public Website Content' not working as expected
* Media - Handled upload photos modal in album, 'select photos' load more not working issue
* Media - Handled media upload textarea not showing issue when forum component is disabled
* Messages - Small improvements to show restrict icon for members not allowed to send a message based on access control rules
* Messages - Handled exact time not showing issue next to the avatar in a single message thread
* Messages - Handled compose message search recipient small UX issue
* Messages - Handled 'blockquote' formatting UX issue on message send action
* Moderation - Improved the suspended and blocked members comments content logic on blog posts and comments widget
* Moderation - Small performance improvement by removing multiple moderation queries
* Network Search - Handled search performance issues for posts in a big network
* Network Search - Handled forum replies issue in search results not linking to the main content
* Core - Handled emoji picker modal layout issue in the responsive view
* Core - Handled plugin editor not working issue in the dashboard
* Coding Standards - Small code refactoring to handle template issues with third-party themes
* REST API - Provided API support for improved suspended and blocked members blog comments content logic

= 2.2.4 =
* Activity - Handled activity form not showing issue when media options are disabled
* Profiles - Handled profile completion widget count UI issue for the RTL language site
* Profiles - Small improvement to not show unsupported files when uploading profile and cover photo
* Profiles - Handled the activity feed edit not working issue in the profile > groups tab
* Profiles - Handled warning and notices issues when gravatar is enabled and profile updated
* Groups - Handled group member count issue in the dashboard when members get deleted from the network
* Forums - Small improvements to show pagination for sub-forums on a single forum screen
* Forums - Handled forum discussion and reply attached documents getting deleted issue after edit
* Forums - Handled invalid links issue for Forum discussion and replies post author in the profile
* Media - Small improvements to giphy picker UI and layout
* Media - Handled video thumbnail not showing issue
* Media - Handled move document action not working issue when group component is disabled
* Media - Handled small translation issue for group status on the documents directory screen
* Messages - Handled join/leave group marks the group message thread unread for all group members
* Messages - Small improvements to show the loading icon on performing any action on the thread from the messages sidebar
* Messages - Small formatting improvements for the last message in the messages sidebar and header dropdown
* Messages - Handled small layout issue when the site URL was posted in the message
* Messages - Small improvement for external links to open in a new tab
* Email Invites - Small improvement to allow all invites by email id in the dashboard
* Moderation - Improved the 'block member' logic for member's profile and directory screen
* Network Search - Handled search forum discussions by tag not working issue in the search results screen
* Core - Small improvements for logged-in member's online/offline status
* LearnDash - Small code refactoring to fix warnings and notices on group courses screen
* Compatibility - Handled email conflict issue with the 'Instructor Role for LearnDash' plugin

= 2.2.3 =
* Notifications - Provided new notification type for new activity posts by someone member is following

= 2.2.2 =
* Activity - Handled draft activity UI issue in the responsive view
* Groups - Handled invalid notice shows in frontend when group updated from the dashboard
* Forums - Handled discussion tags option in the dashboard not showing compatibility issue with the 'BuddyBoss App' plugin
* Forums - Handled forum listing with image and big description UI issue in the dashboard
* Forums - Handled discussion spam option removes document critical issue
* Media - Handled add video form small draft content issue
* Messages - Handled the 'Return to send' message issue by removing the option for mobile devices
* Messages - Handled newline option not working issue after photo selected in the editor
* Messages - Handled performance issues on a big network when creating a thread for multiple recipients
* Messages - Handled 'send message' action from members directory UX issue in the responsive view
* Core - Handled 'BuddyBoss' string translation critical issue in the Dashboard theme options screen from 'BuddyBoss Theme'
* Core - Handled medium editor duplicate text issue for android devices across the network
* REST API - Handled send individual message endpoint in group API issues

= 2.2.1 =
* Activity - Handled forum reply auto-generated activity minor formatting issue
* Activity - Handled @mention name not updating issue when edited from the dashboard
* Activity - Handled minor translation issue for string 'comment'
* Profiles - Handled display name format issue in member profile screen title
* Groups - Handled group not working issue for non-English language slug
* Groups - Handled single group minor tooltip UI issue when the cover image is disabled
* Forums - Small improvement to show descriptions on a single forum screen
* Forums - Handled Forums widget not showing child forums correctly when parent forum id specified
* Forums - Handled hidden forums associated with the group not showing for group members
* Media - Handled uploaded document text file with the incorrect content issue
* Messages - Handled Group thread join/left invalid notice when members are blocked
* Moderation - Handled blocked member notifications doesn't show issue for all members on the network
* Core - Small code refactoring to fix PHP warnings and notices while saving pages in the dashboard
* REST API - Handled group photo description endpoint issue returns HTML entity for special characters
* Compatibility - Handled 'Gravity Form - User Registration' add-on activation email conflict

= 2.2 =
* Notifications - Improved Web Push Notification support based on members active status
* Core - Improved online status for members throughout the network
* REST API - Provided Pusher Integration API support from BuddyBoss Platform Pro
* REST API - Provided online status updates support in the API

= 2.1.7.2 =
* Messages - Handled messages table name prefix

= 2.1.7.1 =
* Messages - Handled SQL queries performance critical issue

= 2.1.7 =
* Activity - Handled edit activity with documents not working as expected
* Activity - Handled auto-generated forum discussion, quick reply giphy option not working issue
* Activity - Handled post activity to a specific group issue even when not allowed
* Profiles - Handled a small UI issue for the repeater fieldset in the profile edit screen
* Media - Handled video doesn't show seek forward/backward option issue in the modal
* Media - Handled media upload not working issue in group messages when option disabled for private messages only
* Tools - Handled 'clear default data' critical issue when the forums component is disabled
* Updater - Improvements to updater logic and performance
* REST API - Handled post activity to a specific group endpoint permission issue
* Compatibility - Handled group zoom meeting date & time picker broken UI conflict with TutorLMS plugin

= 2.1.6.2 =
* REST API - Handled discussion reply endpoint not working critical issue

= 2.1.6.1 =
* Messages - Handled compose message action critical issue on network with a lot of concurrent members

= 2.1.6 =
* Activity - Handled group description update auto-generated activity content overflow UI issue
* Activity - Handled activity post word break formatting issue for list style text format
* Activity - Handled group type 'hidden' auto-generated forum discussion activity, quick reply not working issue
* Activity - Handled registration pages getting self-assigned issue when the activity setting is changed in the dashboard
* Profiles - Handled Profile completion progress doesn't update issue when member updates profile type
* Profiles - Small update in profile types settings in the dashboard renaming 'Post Attributes' to 'Dropdown Order'
* Profiles - Handled 'gender' field 'is on of' specific search mode not working issue
* Groups - Handled send group message upload photos option not working consistently
* Groups - Handled group header elements option not working correctly when group cover image is disabled
* Media - Handled media upload spacing UI issue in the profile
* Media - Handled media 301 error before rendering when symlink disabled
* Media - Handled media description multi-line text not working issue
* Messages - Handled copy paste link from the web displays unwanted markup issue
* Updater - Small code refactoring for the 'Release Notes' modal
* Core - Handled slug option not showing issue for posts quick edit in the dashboard
* REST API - Small notification endpoint improvement for legacy support
* REST API - Small performance improvement to not send 'embed' additional information for media endpoint
* Events Calendar Pro - Handled plugin conflict with tooltip UI in forum discussion and reply

= 2.1.5.1 =
* Notifications - Removed 'Notification Preferences' from labs to enable notification updates for all

= 2.1.5 =
* Messages - Handled small time formatting issue
* REST API - Provided API support for updated messages UI/UX
* REST API - Provided caching support for the API updates

= 2.1.4.1 =
* Moderation - Handled critical issue when Forum component disabled

= 2.1.4 =
* Messages - Improved UI/UX for the Private Messaging screen significantly
* Messages - Provided option to mark conversation archive/unarchive by refactoring hide conversation flow
* Messages - Improved single message thread by splitting conversations by date
* Messages - Provided email digest option for messages with the option to delay
* Messages - Improved the experience of joining/leaving a group for a group messages thread
* Messages - Improved UI/UX for message dropdown in the header
* Moderation - Small improvement for blocked and suspended members names and avatars

= 2.1.3 =
* Media - Improved media uploading layout and styling
* Activity - Handled post privacy update UX issues
* Activity - Handled follower widget not showing accurate members count issue
* Connections - Handled connection request not working issue without 'BuddyBoss Theme'
* Moderation - Handled member showing as blocked issue when the member is reported and blocked by other members
* Moderation - Handled reported content screen 'Unhide' action deletes entries for the content
* Core - Handled a bunch of alignment and styling issues for the RTL language site
* Core - Handled core pages not working in conflict with WordPress themes
* Core - Small improvements for toast messages
* Core - Handled emails not showing RTL content for the RTL language site
* REST API - Handled blog post comments endpoint caching issue
* REST API - Handled create group endpoint default group privacy issue
* WooCommerce - Handled 'WooCommerce Memberships' conflict unable to restrict core videos page
* Compatibility - Handled 'Query Monitor' UI compatibility issue on the Email customizer screen

= 2.1.2 =
* Activity - Handled empty activity post issue when group description updated for the first time
* Activity - Handled create post media upload issue when member switches post visibility from public to group
* Profiles - Handled message member action issue that does not take to the relevant thread when username contains a dot character
* Groups - Handled parent group label tooltip UI issue on single group screen header
* Forums - Handled forums shortcodes medium editor toolbar styling issues
* Media - Improved handling of GIPHY API keys in the dashboard
* Media - Small GIPHY styling improvements in the frontend
* Messages - Handled send message action triggers wrong notification type issue
* Messages - Handled member name showing special character issue in the new message screen
* Emails - Handled email invites template formatting issues when it contains a single quote
* Moderation - Handled block member action not working issue when Activity component disabled
* Core - Icon Pack updated with latest icons
* Elementor - Handled view saved template not working conflict when BuddyBoss pages configured as homepage
* Elementor - Handled activity block UI issue for video modal download button
* Compatibility - Handled Affiliate WP compatibility issues

= 2.1.1.1 =
* Core - Handled updater critical issue by reverting the latest refactored code

= 2.1.1 =
* Moderation - Provided members option to report other members
* Moderation - Improvements to the moderation module settings, categories, and reports in the Dashboard
* Core - Icon Pack updated with latest icons
* REST API - Provided 'Report member' option API support
* REST API - Handled blogs 'Report comment' endpoint issue in the API

= 2.1.0 =
* Forums - Handled forum discussion and reply right click and paste action issue, adds duplicate copies from the clipboard
* Activity - Handled activity comment not showing attachment issue when read more is clicked
* Activity - Handled broken preview issue when adding a new line with the link
* Activity - Handled preview issues for URL shortener sites
* Profiles - Handled course tab not working issue for non-English language slug
* Profiles - Handled profile type and social link profile fields value not showing issue in the profile
* Core - Icon Pack updated with latest icons
* Core - Code refactoring by using transients to optimize the check updates logic for the plugin
* REST API - Handled invalid API response for report content categories with special characters
* Elementor - Handled view template not taking to the right page issue
* WPML - Handled a bunch of languages compatibility issues

= 2.0.9 =
* Notifications - Handled notification content backslash issue for specific special characters
* Notifications - Handled notification count issue after marking notifications read
* Profiles - Handled profile completion widget, profile photo status logic with gravatar
* Emails - Handled new member confirmation email not working issue in multisite
* Emails - Handled password change email notification not working issue when updated in the admin dashboard
* Core - Small improvement in @mention logic when searching for members with a common username and first name
* Core - Icon Pack updated with latest icons
* REST API - Handled group organizer permission issue in the API for performing actions on discussion replies in forums
* REST API - Handled group members endpoint missing 'Block' status issue in the API
* LearnDash - Handled LearnDash shortcode [ld_registration] registration conflict

= 2.0.8 =
* Notifications - Handled save notification preferences issue in multi-site
* Forums - Small improvement to allow creating tags with space at the time of adding discussion
* Forums - Small improvement by providing filter hook to change discussion dropdown format when moving reply to a specific discussion
* Activity - Improved link preview and embeds layout and styling
* Activity - Handled embed preview issue for forum discussion auto-generated activity
* Media - Handled group video not playing issue when members are not allowed to upload
* Core - Small improvements to plugin updates logic by reducing the number of requests to check updates
* Core - Handled draft issue for activity, forum discussion, and replies when uploaded media removed
* Coding Standards - Small code refactoring to fix PHP 8 warnings and notices
* REST API - Handled media symlink issue in the API when uploaded from the App
* REST API - Handled auto-generated forum discussion activity embed iframe issue

= 2.0.7 =
* Notifications - Handled Activity comment auto-generated notification incorrect text issue
* Forums - Handled small Forums widget issue shows wrong discussion count
* Activity - Handled activity comment @mention issue gets converted into HTML markup on post
* Media - Handled document upload double extension issue on document directory screen
* Media - Handled symlink option gets enabled issue when activity settings updated
* Media - Small improvement to fix console JS error when video popup is closed
* Messages - Handled private messages right click and paste action issue, adds duplicate copies from the clipboard
* Core - Icon Pack updated with latest icons
* REST API - Handled get activity videos API endpoint issue
* Compatibility - Handled 'GeoDirectory' broken listing layout compatibility issue

= 2.0.6 =
* Forums - Handled forum reply notification wrong pagination URL issue
* Activity - Handled activity modal @mention RTL language support issue
* Profiles - Handled other member's profile sub-tabs taking to logged-in members profile tabs issue
* Profiles - Handled activate account issue when registration is disabled
* Profiles - Small improvement to show 'See all' for the 'Recently Active Members' widget
* Groups - Handled HTML tags not working issue for group description
* Groups - Handled Group videos not showing issues for non-logged-in members
* Groups - Handled 'Enable Album in groups' option not working issue
* Media - Handled issue for moving photos into album action and not creating separate activity
* Media - Handled upload/delete photos action not updating count issue in profile and directory page
* Media - Handled edit privacy dropdown issue on documents directory screen
* Messages - Handled messages thread UI issue when switching between multiple threads quickly
* Network Search - Handled search results not showing issue even when search string exists in repeater fieldsets value
* Network Search - Handled search results pagination issue for blog posts and pages
* Core - Updated styling for toolbars and pickers across all content types editor
* Core - Handled a bunch of styling issues for Theme 2.0 updates
* Core - Small improvement to not close modal on discard draft for Activity, Forum discussion, and Forum replies
* REST API - Handled member connections cache purge not working issue
* REST API - Small notification endpoint improvement to redirect to specific reply considering pagination
* REST API - Handled wrong API response for discussion replies when discussion created in the admin dashboard
* REST API - Handled members endpoint critical issue when activity component is not active
* LearnDash - Handled js conflict on edit course screen when there are a huge number of members in the network
* LearnDash - Handled Learndash Group slug update issue on plugin activation
* WPML - Handled Social Groups tabs not working issue when switched to a different language

= 2.0.5 =
* Groups - Handled Group Parent settings removed issue when member deleted from the parent group
* Groups - Handled Group permission issue allowing members with no access to post activity
* Forums - Handled forum [bbp-search] shortcode not showing issue
* Forums - Handled Forum discussion tags getting deleted issue on reply update
* Activity - Handled Activity form text color issue when @mention added and removed
* Activity - Small improvement to show user-friendly validation message from dropzone uploader
* Activity - Small improvement to restrict media upload for post types auto-generated activity
* Activity - Handled post types auto-generated activity long comment read more issue
* Media - Handled symlink not working issue on private page excluded URL for non-logged-in member
* Media - Handled media upload not showing thumbnail issue for media size more than 10MB
* Media - Handled media popup layout issue for a specific set of device sizes
* Messages - Handled iPhone device message thread UX issue
* Network Search - Provided support to search members by email id
* Core - Handled a bunch of important styling issues for Theme 2.0 updates
* Core - Small layout improvement for popup in the admin Dashboard
* Core - Icon Pack updated with latest icons
* Core - Handled critical issue on fresh Platform plugin installation
* Coding Standards - Code Refactoring replacing wp_parse_args functions with custom
* Coding Standards - Code refactoring to support different notification types for custom development
* REST API - Group Document cannot be renamed if the same file is uploaded more then once
* REST API - Restrict media upload for post types auto-generated activity in the API
* REST API - Handled read more issue for blog posts auto-generated activity in the API

= 2.0.4.1 =
* Activity - Fixed critical issue when ‘Forum Discussions’ component is not active

= 2.0.4 =
* Notifications - Provided support for Web Push Notification from BuddyBoss Platform Pro
* Forums - Added support to save forum Discussion and replies content in the draft on accidental close or refresh
* Forums - Handled member unsubscribed automatically issue when discussion edited from admin Dashboard
* Forums - Handled discussion alignment formatting for RTL language site
* Activity - Added support to save Post activity content in the draft on accidental close or refresh
* Activity - Provided draft support for activity directory, profile timeline, members profile timeline, and groups timeline
* Activity - Small improvement to show embed preview for blog auto-generated activity
* Profiles - Handled profile repeater field issue not getting saved properly
* Groups - Handled group name with apostrophe not showing correctly across the network
* Groups - Handled group URL not working issue in the email
* Media - Handled video not playing issue in the popup on other member's timeline
* Media - Fixed issue to search emoji with uppercase string in messaging, activity, forums, etc
* Messages - Handled critical issue of sharing same thread when multiple members create thread at the same time
* Messages - Small code refactoring to not save entity code in the DB for empty messages with just media
* Coding Standards - Menu and sub-navigation CSS Code refactoring
* REST API - Provided raw group name parameter in the group API
* REST API - Handled edit activity critical issue in the activity API
* REST API - Provided create album parameter for a specific group in the group API
* REST API - Small improvement to send message thread sender details in the message thread API
* REST API - Handled emojis inconsistent response issue
* REST API - Handled critical issue of sharing same thread in the API
* Compatibility - Fixed 'WP Offload Media' plugin compatibility issues
* Documentation - Small code refactoring to stop sending resources documentation requests on every page load

= 2.0.3.1 =
* Compatibility - Fixed 'WP Offload Media' plugin critical topics issue attached with documents

= 2.0.3 =
* Profiles - Handled repeater set title issue showing dropdown value instead of text
* Profiles - Handled wrong date value for empty date Profile fields
* Groups - Small improvement in groups directory filter dropdown to show Group types in alphabetical order
* Forums - Handled single discussion display name privacy issue
* Activity - Handled @mention not working issue in activity comments for blog post activities
* Activity - Small improvement to redirect to specific activity comment from notification
* Activity - Small improvement to allow searching Unicode characters in directory screen
* Activity - Handled URL embed not working issue with the space
* Activity - Handled link preview getting deleted issue when updating privacy
* Activity - Small improvement to show a relevant error when not allowed to edit activity after a specified duration
* Activity - Handled disabled auto-refresh option not working issue
* Media - Handled document upload in a folder not showing without refresh issue in the Profile
* Media - Small improvement to show uploaded portrait media in the right size across the network
* Media - Small improvement to hide action buttons when no videos are found
* Messages - Handled gif not playing issue in the message thread
* Moderation - Small improvement to not receive an email notification from blocked members
* REST API - Handled wrong date value for empty Profile fields in the API
* REST API - Handled members directory send message action not taking to a right thread issue
* LifterLMS - Handled critical issue in the profile screen
* Events Calendar Pro - Handled past events not showing issue in search results
* Yoast SEO - Handled update profile critical conflict in the admin dashboard and API
* Compatibility - Code refactoring to fix a bunch of PHP 8 compatibility issues

= 2.0.2 =
* Notifications - Added icon support for notification avatar based on the notification type
* Profiles - Handled members profile takes to wrong recipient issue when clicking on send message
* Forums - Handled @mention dropdown not showing issue for Forum discussion and reply editor
* Forums - Handled profile subscription tab members profile link issue
* Activity - Handled activity comment empty content validation issue
* Activity - Handled activity comment enter new line lag UX issue
* Activity - Handled hidden gif issue on read more click
* Activity - Handled read more not working issue for specific server
* Activity - Handled documents performance issue when symlink disabled
* Activity - Handled gif deletion issue when post edited
* Media - Handled temporary junk files issue on the server when folder downloaded
* Messages - Handled multiple-member thread, all members not showing issue
* Moderation - Handled critical issue on block user action when connection component is disabled
* Network Search - Handled discussion search issue when media attached
* Registration - Handled small UI issues
* BuddyPanel - Handled small UI issues when BuddyPanel disabled and group type shortcode added
* BuddyPanel - Handled 'my group' menu wrong link issue
* Coding Standards - Code refactoring to update all icon images with a new icon pack in the dashboard
* Coding Standards - Small code refactoring to fix hook and added icon CSS file version query string
* REST API - Handled activity comment empty content validation issue in the API
* REST API - Handled member type issue for certain members in the API
* REST API - Handled hidden group activity media privacy issue
* REST API - Handled block member action critical issue when connections component disabled in the API
* REST API - Handled activity end-point media issue in the response

= 2.0.1.1 =
* Messages - Fixed send message critical security issue

= 2.0.1 =
* Profiles - Small code refactoring to stop triggering multiple hooks
* Profiles - Handled profile header social network links issue when Profile type not enabled
* Forums - Fixed PHBB import issues
* Moderation - Handled single forum report modal not working issue
* REST API - Handled blog comments count issue in the API
* REST API - Handled newly created Group caching issue
* REST API - Provided is_admin user parameter in the API

= 2.0.0 =
* BuddyBoss Theme - Provided Theme 2.0 style new options support
* BuddyBoss Theme - Provided Theme 2.0 overall styling support
* BuddyBoss Theme - Provided Theme 2.0 with new color support
* BuddyBoss Theme - Provided Theme 2.0 new icons pack support
* BuddyBoss Theme - Provided Theme 2.0 new header style support
* Profiles - Provided option to select a custom color for Profile type label
* Profiles - Restructured the profile data in multiple sections for each profile fieldsets
* Groups - Provided option to select a custom color for Group type label
* Forums - Provided 'BuddyBoss Theme' 2.0 style support
* Forums - Provided support for the featured image in the forum header
* Forums - Converted Forum discussion and reply post forms into modals
* Forums - Improved cover image logic for Forums directory screen
* Forums - Handled discussion reply count issue
* Activity - Handled embed link preview issue when privacy changed
* Activity - Handled 'Latest Activities' widget privacy issue
* Messages - Handled multiple recipients message performance issue
* Messages - Handled wrong message count issue for Group messages
* Network Search - Provided template and markup support for the search results screen
* Network Search - Improved search logic for Blog posts
* Widgets - Provided template and markup support for widgets
* Coding Standards - Code refactoring to handle warnings and notices
* REST API - Provided Profile type custom color API support
* REST API - Handled media rendering issue when file not directly accessible
* REST API - Handled API issue to post activity with just video
* Compatibility - Fixed 'WP Offload Media' plugin compatibility issues

= 1.9.3 =
* Notifications - Provided notification updates as a Lab feature
* Notifications - Provided options for members to manage all Notification Preferences for all devices in one place
* Notifications - Provided options in the admin to disable a specific notification type
* Notifications - Provided options in the admin to manage default notification preferences for new members
* Notifications - Provided options in the admin to hide Messages notifications from notification dropdown
* Notifications - Refactored notifications types for Lab feature enabled
* Notifications - Refactored emails for Lab feature enabled
* Notifications - Improved code to allow to register new notification types easily
* Profiles - Handled profile field type paragraph formatting issue to allow HTML target attribute
* Profiles - Small improvement to show confirm popup before deleting profile field in the admin
* Groups - Handled add user issue in admin edit group screen when username and nickname are different
* Groups - Handled my group nav issue in the menu for logged-in member
* Forums - Handled forum merge discussions and reply split issue
* Activity - Handled attached GIF issue in Activity Form
* Activity - Handled activity comment duplicate string issue on paste
* Messages - Handled last message hidden issue when new member join/left group
* Network Search - Handled sub-forums search compatibility issue with MariaDB
* Emails - Provided option to install missing emails templates
* Documentation - Handled tutorial articles images layout issue
* REST API - Provided notification updates API support
* REST API - Handled forum discussions merge and reply split issues in the APIs
* Compatibility - Handled WP 5.9 compatibility issue causing false alerts on post types edit screen

= 1.9.2 =
* Profiles - Handled critical issue in Profile when forum component not enabled
* Groups - Handled group invitation screen UI issue in profile
* Groups - Handled memory issue for a non-logged-in member for group with a large number of invitations
* Groups - Handled hidden group activity privacy issue
* Activity - Handled activity form, privacy selection issue
* Media - Handled photo and document update privacy issue in the popup
* Performance - Improved @mention performance to query for right usernames on page and post
* REST API - Small improvement in group details API endpoint
* REST API - Handled message API caching issue
* Compatibility - Handled WordPress 5.8 compatibility issue on save profile action

= 1.9.1.1 =
* Private Network - Handled private site ‘Public Website Content’ not working critical issue

= 1.9.1 =
* Profiles - Provided support to customize Profile header and directory layouts on settings enabled with BuddyBoss Platform Pro
* Profiles - Provided support to change Profile cover image sizes on settings enabled with BuddyBoss Platform Pro
* Groups - Provided support to customize Group header and directory layouts on settings enabled with BuddyBoss Platform Pro
* Groups - Provided support to change Profile cover image sizes on settings enabled with BuddyBoss Platform Pro
* Groups - Handled Group type direct URL issue when Group type and its label name is same
* Forums - Handled forum discussion js error for non-English language
* Activity - Handled activity formatting issue for auto-generated discussion replies
* Activity - Improved responsiveness of activity form
* Coding Standards - Refactored since time output across all components
* Updater - Provided 'Release Notes' modal to show information about the release
* REST API - Handled API performance table column size issue for specific server
* REST API - Handled Forum APIs wrong permissions issue
* REST API - Update to send activity with embed in the API as a separate object

= 1.9.0.1 =
* Media - Fixed critical API caching purge issue on symlink delete cron

= 1.9.0 =
* Performance - Optimized core functions, loops, and SQL queries
* Performance - Extended object caching support
* Performance - Removed duplicate SQL queries significantly
* Performance - Optimized a bunch of specific page and functionality performance
* Forums - Provided edit option for forums and discussion in the WordPress toolbar
* Activity - Show preview when link added with text
* Activity - Improved embedded video width and UI
* Activity - Improved activity form upload media UI
* Profiles - Handled profile completion widget string translation issue
* REST API - Handled caching issues in the API to improve performance
* REST API - Provided order by and include options in the API
* REST API - Provided moderation can report parameters in the API
* Compatibility - Show title properly when RankMath plugin is active

= 1.8.7 =
* Groups - Fixed default avatar issue in messages and notifications screen when group avatar is disabled
* Groups - Fixed group title and description critical security issue
* Activity - Fixed critical issue to restrict photos and videos privacy update when uploaded to a specific album
* Activity - Fixed small UI issue showing text close to ellipsis icon
* Activity - Fixed @mention issue with space when user created from the dashboard
* Activity - Fixed activity form upload UX issue with preview
* Activity - Fixed activity form preview URL issue not rendered on paste
* Media - Fixed documents directory screen small UI issues
* Media - Fixed videos directory screen small UI issues
* Media - Fixed edit action not working issue in media popup
* Media - small improvement for video upload progress logic
* Moderation - Fixed moderation markup issue in email customizer
* REST API - Fixed group message thread permission issue in the API
* REST API - Fixed media edit issue in the API
* REST API - Fixed default avatar issue in the API when group avatar is disabled
* Compatibility - Fixed 'WP Offload Media' plugin compatibility issues

= 1.8.6 =
* Profiles - Provided WordPress, BuddyBoss, and Custom option to change default profile avatar image
* Profiles - Provided BuddyBoss and Custom option to change default profile cover image
* Groups - Provided BuddyBoss and Custom option to change default group avatar and cover image
* Activity - significantly enhanced activity form interface with modal layout
* Private Network - Provided option to restrict REST APIs and RSS feed public access
* Media - Fixed generating thumbnail infinite loading issue for uploaded video
* Media - Fixed portrait video thumbnail wrong size issues
* Network Search - Fixed search issue with ampersand character in group and activities
* REST API - Provided default cover and avatar image API support for profile and group
* REST API - Provided caching support in the APIs for restrict Rest API and RSS feed option
* REST API - Fixed activity like and comment parameter issue in the API
* REST API - Fixed forum reply permission issue in the API

= 1.8.5 =
* Profiles - Fixed profile type search settings to show when the Network Search component is active
* Profiles - Fixed exported data to show profile type field value to name instead of id
* Groups - Fixed changing group photo updates user avatar temporarily in the header
* Forums - Fixed discussion reply notification issue showing wrong member name
* Activity - Fixed public URL link preview issue for private network
* Media - Fixed bunch of non-translatable strings related to the media component
* Media - Fixed missing 'video-js-rtl.min.css' file error when a site using RTL language
* Text Editor - Fixed HTML copy paste issue
* LearnDash - Code refactoring to fix warnings and notices
* REST API - Fixed sign up user exists endpoint error message status code in the API
* REST API - Fixed activity uploaded media order in the API

= 1.8.4 =
* Groups - Fixed group private messages screen, members load more issue on scroll
* Groups - Fixed sub-group issue where parent get unassigned on description update
* Groups - Fixed hidden group email invite issue takes to 404
* Groups - Fixed @mention issue not working when user mention name updated
* Groups - Fixed @mention issue using wrong user mention name
* Forums - Fixed discussion title tag issue to be consistent with or without group
* Forums - Fixed discussion reply formatting issue in the email
* Forums - Fixed activity 'Read more' link issue not taking to a specific reply
* Forums - Fixed discussion notification issue on reply
* Forums - Fixed discussion email notification invalid links issue
* Activity - Fixed @mention double space issue
* Activity - Fixed @mention duplicate queries performance issues
* Activity - Fixed inconsistent video preview issue for a forum reply
* Profiles - Fixed members count format issue on load
* Media - Fixed undefined png image issue with emojis dropdown
* Messages - Fixed critical issue on messages screen when connection component disabled
* Notifications - Fixed invalid notification title issue on the discussion page
* REST API - Added component pages details in the API
* REST API - Fixed group type with special character issue in the API
* REST API - Fixed custom profile tab link issue in the API

= 1.8.3 =
* Messages - Improved logic to send group messages by processing members and notifications in the background
* Moderation - Improved moderation module core logic to hide content in the background batches on Report, Block, and Suspend
* Media - Fixed video playing issue on upload in Safari browser
* Media - Fixed Album feature option avail even when disabled in the settings
* Coding Standards - Code refactoring to handle when PHP system function disabled
* Coding Standards - Code refactoring to fix warnings and deprecated functions
* Elementor - Fixed templates builder popup settings compatibility issue with Forums discussion and replies
* REST API - Added Group messages improvements in the APIs
* REST API - Fixed cache issue on password change to fix a critical issue

= 1.8.2 =
* Forums - Fixed Forums directory page search issue
* Profiles - Fixed WordPress role sync issue on Members Profile type update
* Media - Improved symlink auto-detection logic for server compatibility
* REST API - Fixed member default avatar issue in the API
* REST API - Fixed wrong content issue in the message thread API

= 1.8.1 =
* Groups - Added support to Send emails in Batches in the Background to Group members on details update
* Forums - Added support to Send emails in Batches in the Background to Forum and Topics subscribers
* Forums - Fixed Forum discussion apostrophe slash issue on validation error
* Forums - Fixed PHBB import issues
* Profiles - Cross-browser compatibility added for profile picture image quality
* Profiles - Fixed Profile field apostrophe slash issue on save
* Media - Fixed video popup unable to close the issue in profile
* Messages - Added support to Send emails in Batches in the Background for Group and Private Messages thread
* Moderation - Fixed background process infinite loop critical issue
* Notifications - Fixed notification read/unread status issue when multiple group access request notifications were received and followed
* Emails - Small compatibility fix to show avatar in Outlook
* Elementor - Minor fix to load right size profile picture on the dashboard template
* REST API - Added API support to Send emails in Batches in the Background
* REST API - Fixed delete custom thumbnail API issue on selecting auto-generated video thumbnail
* REST API - Fixed API performance issue when many profile fields data added

= 1.8.0 =
* Profiles - Fixed Cross-Site Scripting vulnerability issue on edit and view profile
* Groups - Fixed group type roles label 'an' and 'a' prefix issue
* Groups - Code improvements to stop direct URL access when group tab removed using hooks
* Forums - Fixed error on forum replies listing screen in the admin when reply moved to draft
* Forums - Fixed forum search issue with the empty string
* Media - Fixed symlink error on plugin activation when symlink not supported on the server
* Media - Fixed Document popup download and edit description not showing issue
* Messages - Fixed messages members list to not show suspended members
* LearnDash - Fixed Learndash sub-groups sync issue with Social sub-groups
* REST API - Fixed Media Privacy and Move option permission issue in the API
* Compatibility - Fixed 'WP Offload Media' plugin compatibility issue to show both local and offloaded media properly
* Compatibility - Fixed media access notice in admin to not show when Media offloaded fully using 'WP Offload Media' plugin
* Compatibility - Fixed 'GeoDirectory' plugin compatibility issue with Messages screen
* Compatibility - Fixed 'TranslatePress' compatibility UI issue with News Feed
* Translations - Updated German (formal) language files

= 1.7.9 =
* Profiles - Provided option to hide specific profile type members in search results
* Profiles - Fixed profile type issue to not hide members in Group manage members screen
* Groups - Fixed 'Visit Group' button UI issue in the Group invite email
* Forums - Fixed group organizer permission issue to allow adding a discussion and reply tags
* Forums - Fixed Forum discussion and reply editor formatting issue
* Forums - Fixed Forum reply widget wrong date issue
* Forums - Fixed text formatting issue for Email tokens
* Activity - Fixed URL preview issue when creating Activity post
* Activity - Fixed Activity feed comment issue when popup opened in a specific order
* Activity - Fixed issue with browser performance when @mention used many times in activity post
* Messages - Fixed Email notification preference settings for Group messages
* Network Search - Fixed search issue when all Forums deleted
* Coding Standards - Added Security patch for SQL injections vulnerability
* Elementor - Provided WordPress Widgets support in Elementor pages
* WooCommerce - Fixed critical registration conflict on Private network
* REST API - Provided endpoint to send hidden group details
* REST API - Fixed mention API endpoint to return a response for empty string
* REST API - Profile type new option support added in the API
* REST API - Video thumbnail endpoint improvements in the API
* REST API - Fixed videos count issue in Group details endpoint
* Compatibility - Fixed SEOPress media compatibility issue

= 1.7.8 =
* Forums - Fixed forums logic by not allowing multiple Forum to be associated with a single Group
* Forums - Fixed Forums and it's sub-forums to have the same privacy as Group when associated
* Forums - Fixed Forums and it's sub-forums to link to Group URL when associated
* Forums - Fixed forum discussion tag conflict with same term slug
* Forums - Fixed forum discussion documents not showing on edit
* Forums - Fixed forum discussion js error with the Korean language
* Forums - Fixed forum reply with gif media duplicate error on submit
* Activity - Fixed issue to stop showing Activity comment Photos and Documents anywhere else on the network
* Activity - Fixed issue to stop further activity on Photos and Documents added in activity comments
* Activity - Fixed Activity Feed connection tab to not show logged-in member posts
* Profiles - Fixed profile field line break not working for Paragraph field
* Media - Fixed Media download security issue
* Media - Fixed Video thumbnail UI issue
* Messages - Fixed big multiple members thread load more UI issue
* Messages - Fixed Block member listing popup to not show blocked members
* Messages - Fixed single quote issue in display name not showing correctly
* Messages - Fixed invalid messages count issue on hiding message thread action
* Network Search - Fixed Forum replies issue not showing in the search results page
* Network Search - Fixed search dropdown UI issue on the search results page
* Network Search - Fixed Cross-Site Scripting issue with search query string
* Coding Standards - Fixed bunch of non-translatable strings
* Coding Standards - Fixed bunch of notices and warnings
* Elementor - Fixed [bbp-topic-form] shortcode compatibility issue
* REST API - Forums and Groups association logic API changes
* REST API - Activity comment media logic API changes
* REST API - Forum discussion logic updates API changes
* REST API - Fixed signup endpoint issue to not return activation code in response
* REST API - Fixed signup endpoint to activate member using activation key only
* REST API - Fixed hidden/private group Activity post critical issue on update
* Compatibility - Fixed 'WishList Member' plugin compatibility issue on exporting personal data
* Compatibility - Fixed login 'Privacy Policy' popup compatibility issue with custom js script
* Compatibility - Fixed network search issue with PHP 8.0

= 1.7.7.1 =
* Activity - Fixed @mention not working critical issue

= 1.7.7 =
* Profiles - Fixed wrong 'Nickname' after migrating from BuddyPress
* Forums - Fixed discussion pagination issue on Forums directory
* Forums - Fixed forum discussion invalid count to make it consistent
* Activity - Fixed post form GIF highlight UI issue on close
* Activity - Fixed URL preview empty markup issue
* Activity - Fixed duplicate queries issue to improve performance
* Media - Fixed .htaccess Apache 2.4 and OpenLiteSpeed compatibility issue
* Moderation - Improved reporting based on the content in the Activity feed
* Notifications - Fixed duplicate notifications issue on blog post reply on a comment
* Text Editor - Fixed HTML copy-paste issue
* Elementor - Fixed media preview compatibility issue with Elementor Pro
* REST API - Provided endpoint to move Photos/Videos into Album
* REST API - Fixed activity comment invalid data in response
* REST API - Added API support for moderation improvements

= 1.7.6 =
* Profiles - Fixed change password critical issue
* Forums - Fixed issue with discussion tags not getting saved on update
* Activity - Fixed forum discussion activity > quick reply > upload media access control issue
* Activity - Fixed activity permission issue when group privacy changes from Public to Private and vice versa
* Activity - Fixed URL preview attachment deletion issue on deleting activity
* Activity - Fixed activity comment more options dropdown UI issue
* Media - Fixed video issue on specific servers by handling symbolic link file extension
* Media - Fixed photos directory create album popup UI issue
* Media - Fixed profile upload photos minor UI issue
* Media - Fixed video thumbnail issue for iPhone device
* Media - Small code enhancement to check if symlink function disabled on the server
* Messages - Fixed message thread performance issue by not loading all members at once
* Connections - Fixed members widget issue when connection component disabled
* Member Access Controls - Fixed notification email issue getting sent to the suspended members
* Network Search - Fixed members count in search results when profile type set to hidden
* Registration - Fixed forgot password issue when Group component disabled
* Widgets - Fixed 'Members I am Following' and 'Members Following Me' widget settings issue
* Coding Standards - Improved SQL queries
* Coding Standards - Small improvement to escape attribute in the network search template
* Elementor - Fixed Elementor conflict with forums parent option in admin
* REST API - Fixed long thread performance issue in messages endpoint
* REST API - Fixed messages thread members performance issue in API
* Compatibility - Fixed 'WP Offload Media' plugin PDF document preview not generated issue
* Compatibility - Fixed 'WP Offload Media' plugin general compatibility issues
* Compatibility - Fixed 'Events Manager' plugin conflict
* Compatibility - Fixed 'ACF Frontend Pro' plugin critical issue

= 1.7.5 =
* Moderation - Added moderation support for the Video module
* Moderation - Improvements to report media instead of reporting activity post internally
* Moderation - Fixed issue showing hidden media on edit activity post
* Profiles - Fixed edit profile repeater field issue not allowing to add new instance
* Profiles - Fixed duplicate fields issue in the admin when adding too many at once from the edit profile screen
* Groups - Fixed issue to not show My Group tab in Videos and Photos directory page when Group component disabled
* Forums - Fixed discussion pagination issue when 'Discussions by Last Post' configured
* Media - Fixed minor issue with .m4v video support
* GamiPress - Fixed gamipress badges UI issue in the activity feed
* Compatibility - Fixed moderation conflict with Avada Builder Plugin
* REST API - Fixed Repeater field issue in the API endpoint
* REST API - Fixed API issue when Push Notification component disabled
* REST API - Provided embeddable report link for media, document, and video in the API endpoint
* REST API - Fixed Group type issue in the Groups endpoints showing HTML markup
* REST API - Fixed API compatibility issue also with Avada Builder Plugin

= 1.7.4 =
* Member Access Controls - Added support to restrict Video upload based on Access Control settings provided in BuddyBoss Platform Pro
* Profiles - Fixed issue with Repeater date field value on changing the order
* Groups - Fixed critical parse syntax error in the template
* Activity - Small improvement to show activity blog post excerpt in a wrapper
* Media - Fixed video popup JS error on edit
* Media - Fixed unwanted mystery-man files getting created issue
* Media - Fixed JS error for .flv format videos uploaded
* Media - Fixed critical issue for photos not showing
* Network Search - Fixed search result order for members listing
* REST API - Added API Support for Video Access Control
* REST API - Added Report text API related changes
* REST API - Fixed Profile field description formatting issue in the API
* REST API - Fixed videos API param issue
* REST API - Small API improvements in Moderation
* REST API - Fixed Profile Nickname update issue in the API

= 1.7.3 =
* Moderation - Improvement to show Report button less prominent in the dropdown for all content types
* Groups - Fixed group description HTML formatting issue
* Activity - Improvement to hide Forum activities when Forum component disabled
* Media - Fixed minor issue with .mov video support
* REST API - Fixed activity endpoint group related issue
* REST API - Fixed Video thumbnail generate endpoint issue
* LearnDash - Fixed avatar issue on Courses, Lessons, and Topics
* Translations - Updated German (formal) language files

= 1.7.2.2 =
* Activity - Fixed critical error on plugin update

= 1.7.2.1 =
* Activity - Fixed critical error when Forums component disabled

= 1.7.2 =
* Activity - Provided option to disable comments in the activity feed for blog posts and custom post types
* Activity - Improved forum discussion and replies activity workflow to handle redundant comments data
* Activity - Improved blog posts and custom post types layout in the activity feed
* Activity - Moved activities action button other than Like and Comment to ellipsis dropdown
* Activity - Fixed comment JS issue when Edit Activity disabled
* Media - Fixed media preview fallback logic by using WP URL instead of the PHP file
* Media - Fixed documents mp3 file issue when symbolic link settings enabled
* Media - Fixed video playing issue when symbolic link settings disabled
* Media - Fixed video playing issue in Safari browser
* Groups - Fixed group navigation tabs order issue in the customizer
* Text Editor - Fixed text copy-paste issue
* REST API - Added comments options support in the activity feed for blog posts and custom post types
* REST API - Added activity feed forum discussion quick reply support
* Compatibility - Fixed WP Offload Media plugin compatibility issue when 'Remove files from server' enabled
* Compatibility - Fixed WordPress 8.0 compatibility issues
* Translations - Updated German (formal) language files

= 1.7.1 =
* Videos - Fixed video tab issue not allowing to re-order in Groups
* Videos - Fixed video player controls styling to handle white background videos
* Groups - Fixed issue with Group type on Group settings update
* Text Editor - Copy Paste image restricted for all frontend text editor

= 1.7.0.1 =
* Media - Added settings to enable/disable symbolic link for medias
* Media - Fixed document PDF file preview issue
* Media - Fixed photos size issue to improve quality in the activity feed
* Videos - Fixed video not playing issue in Safari browser
* Videos - Fixed video symbolic signed URL download issue
* Moderation - Fixed issue to hide activity photos and documents on report
* REST API - Fixed symbolic link issue in the APIs

= 1.7.0 =
* Videos - Added Videos support for Activity, Profiles, Groups, Messages, Forums
* Videos - Added central Videos page for showing all site Videos
* Videos - Added functionality to move and organized Videos into Albums
* Videos - Added settings page for customizable Video File Extensions
* Videos - Added support to auto-generate Video cover images
* Videos - Added Video Player support
* Videos - Added Video support for Documents
* Notifications - Added On-Screen Notifications support
* Notifications - Added On-Screen Notifications controls
* Media - Major security & performance improvements for Photos, Documents, and Videos preview
* Groups - Media permission improvements
* REST API - Added Videos API support
* REST API - Media permission and Media performance Code refactoring

= 1.6.4 =
* Profiles - Fixed profile photo not showing issue when 'Avatar display' not enabled in discussion WordPress settings
* Profiles - Fixed profile photo cropping issue for RTL languages
* Activity - Code refactoring and API improvements specific to activity avatar
* Text Editor - Fixed numbered list formatting issue
* REST API - API endpoints code refactoring to improve security and fix vulnerabilities
* REST API - Small improvement for custom profile and group tabs endpoints

= 1.6.3 =
* Profiles - Fixed issue to handle special character in profile field type - dropdown
* Profiles - Fixed irrelevant members name issue in private message search
* Forums - Provided forum discussion first level replies pagination support
* Forums - Fixed bug by escaping Html in forum discussion and reply
* Forums - Fixed hidden reply issue on restore
* Forums - Fixed Organizer and Moderator sync issue with social groups
* Network Search - Fixed issue with sub-forum discussion not showing in search results
* REST API - Provided API support for forum replies pagination
* Compatibility - small improvement for profile and group custom tab screen

= 1.6.2 =
* Moderation - Improved module to optimize performance
* Icons - Added new font icons for TikTok, Telegram, and ClubHouse

= 1.6.1 =
* Activity - Small improvement to stop saving zero width space html entity with post update
* Activity - Fixed minor button text for edit activity popup
* Media - Fixed media link embed issue in Activity, Comments, Private message, Forums, etc
* Messages - Fixed issue to stop triggering multiple messages on frequent clicks
* REST API - Code refactoring

= 1.6.0 =
* Profiles - Added validation to restrict duplicate Nicknames in the dashboard
* Profiles - Fixed issue when trying to search members using courses
* Media - Fixed media preview issue for specific server configuration
* Notifications - Fixed notification issue with blog post comments and replies
* Registration - Small improvement to show relevant validation message on the account activation
* REST API - Provided customer profile/group tab details in relevant endpoints
* REST API - Improved caching for menu and settings
* REST API - Fixed issue with hiding subgroups option not working on groups endpoint
* REST API - Fixed issue with forum screen in settings endpoint when forum slug updated
* REST API - Fixed messages issue by providing media objects in the bulk URL details endpoint
* REST API - Fixed empty profile type issue in the member endpoint
* REST API - Fixed notification wrong 'from' field parameter issue
* Translations - Updated German (formal) language files

= 1.5.9 =
* Profiles - Improvements to reflect uploaded profile and cover photo instantly
* Profiles - Small bug fix to show only published profile type and group type in the dashboard options
* Groups - Small improvement to show 404 screen when group type is invalid
* Groups - Fixed issue with group members logic to improve performance
* Groups - Fixed issue with a hidden group create a discussion to redirect to the discussion
* Forums - Provided Email Preference option to stop receiving forum replies and discussion email
* Forums - Fixed issue with duplicate reply notification generated
* Activity - Fixed activity comment box scroll issue
* Media - Fixed issue with Redis cache when photos moved into an album
* Messages - Fixed iPhone bug when sending Private Messages
* Messages - Fixed infinite loading issue on delete messages action
* Network Search - Small code improvement
* Moderation - Small bug fix to improve performance
* REST API - Fixed issue with notification marked read/unread
* REST API - Fixed notification endpoint invalid data issue
* REST API - Email Preference forum options support added
* REST API - Fixed registration label special character issue
* REST API - Cache improvements to fix group issues
* REST API - Fixed activity feed issue when activity tab disabled
* REST API - Fixed activity endpoint edit issue
* LearnDash - Fixed Courses slug compatibility issue
* Translations - Updated German (formal) language files

= 1.5.8.3 =
* Registration - Provided Legal Agreement checkbox option
* Login - Showing 'Terms of Service' and 'Privacy Policy' on WordPress Login page
* REST API - Provided Legal Agreement option in settings endpoint

= 1.5.8.2 =
* REST API - Fixed course access and course count caching issue
* REST API - Fixed emoji size issue in private message
* REST API - Fixed Profile Social Network field update issue
* REST API - Small Performance Improvements

= 1.5.8.1 =
* REST API - Provided 'placeholder' parameter in settings endpoint
* REST API - Provided 'success' parameter on account settings update
* REST API - Fixed favorite activity endpoint critical issue
* REST API - Fixed add/edit discussion endpoint invalid response
* REST API - Fixed deep linking issue by purging cache on component activate/deactivate
* REST API - Fixed photo count issue in the media endpoint
* REST API - Fixed group activity feed not showing anything
* REST API - Fixed API issue in group activity screen
* REST API - Fixed date field not getting saved on registration
* REST API - Fixed messages issue to delete a conversation
* REST API - Fixed settings parameter for Advance Search
* REST API - Fixed Activity comment content issue
* REST API - Fixed empty subgroups issue in the endpoint

= 1.5.8 =
* Profiles - Fixed issue with the hidden groups showing in other members profile
* Profiles - Fixed issue with the delete account button
* Profiles - Small improvements in profile photo and cover photo dropzone style
* Profiles - Small improvements in privacy visibility settings to sync with repeater fields
* Groups - Fixed send messages screen, members not loading on scroll for big resolution screen
* Groups - Fixed issues with the group type pages
* Activity - Fixed activity comments read more issue
* Activity - Fixed a bunch of issues with @mention
* Activity - Fixed Youtube video embed issue in activity comments on edit activity
* Media - Small improvements in documents query
* RTL - Fixed select2 library rtl issue
* REST API - Cache improvements to fix BuddyBoss App deep linking and Web fallback bugs
* REST API - Fixed many API issues related to member connection, message media upload, search recipients in a message, etc
* REST API - Fixed API Vulnerability
* REST API - Fixed mu-plugin download issue to provide caching support
* LearnDash - Fixed compatibility issues with LearnDash 3.4
* WooCommerce - Fixed 'WooCommerce Memberships' plugin restrictions rules not working with components pages
* Elementor - Fixed maintenance mode settings compatibility issue
* Compatibility - Fixed document privacy option for MySQL 8.0.22
* Compatibility - Fixed 'Yoast SEO' plugin title and description meta tag not working with components pages
* Compatibility - Fixed critical issue with 'WPMU DEV' plugin on deleting members from the dashboard

= 1.5.7.3 =
* Groups - Fixed redirection issue when member leave the hidden social group
* REST API - Performance Improvements

= 1.5.7.2 =
* Messages - Provided 'select all' option in Group Send Private Message screen
* Messages - Improved and Fixed UI issues related to Group Message and Private Message screen
* REST API - Code refactoring
* Translations - Updates to refactor Hungarian, French, German, and German (formal) language files

= 1.5.7.1 =
* Messages - Fixed critical issue with sending a message when Social Groups component is not active
* REST API - Code refactoring

= 1.5.7 =
* Member Access Controls - Added support to restrict list of social interaction based on Access Control settings provided in BuddyBoss Platform Pro
* Activity - Fixed blog post activity showing Gutenberg block code issue
* Activity - Fixed issue with comments disappearing when blog post updated
* Messages - Fixed message threads listing scroll issue in the private message screen
* Media - Fixed 'select album' option shows even when it is disabled in the settings
* Media - Fixed media dropdown and popup layout issues
* Groups - Fixed send invite members listing scroll issue at the time of creating and managing group
* Groups - Fixed cover image layout issue at the time of creating a group
* Groups - Fixed group status not translatable issue in the email
* Forums - Fixed issue with Forum and Discussion non-English slug
* Profiles - Fixed issue with radio and checkbox fields in Profile Search
* LearnDash - Fixed issue with group name not synced on update
* Moderation - Fixed Reporting Category taxonomy showing in nav menu issue
* Compatibility - Fixed group roles label not working with 'Instructor Role for LearnDash' plugin
* Compatibility - Fixed issue registration issue with 'Google Captcha Pro' plugin
* Compatibility - Fixes for WordPress 5.6 and PHP 8.0
* REST API - Provided support and new endpoints added for the 'Access Control' feature
* REST API - Added caching support for all REST APIs

= 1.5.6 =
* Moderation - New Moderation Component added
* Moderation - Added option to allow members Block each other
* Moderation - Added option to allow members Report other members content
* Groups - Fixed activity access for hidden social groups
* Groups - Fixed send message members listing scroll issue
* Forums - Fixed forum reply copy-paste content formatting
* Forums - Fixed Forum directory search option not working as expected
* Forums - Fixed discussion and reply, photos documents attachment issue
* Activity - Fixed member profile, friend activity scope
* Media - Improved photos directory page
* Media - Provided option to Create Album and Move Photos while uploading
* Media - Provided option to Create Folder and Move Document while uploading
* Media - Provided option to Move Photos into Album from Activity
* Media - Provided option to limit the number of Photos/Documents uploaded per batch
* Messages - Code refactoring
* REST API - New endpoints added for 'Moderation' feature
* REST API - New endpoints added for 'Relevant Activity' feature

= 1.5.5.1 =
* Messages - Fixed message threads critical security bug
* Messages - code refactoring

= 1.5.5 =
* Groups - Fixed group message media shows in the photos tab
* Groups - Fixed group message private bcc thread bug
* Forums - Fixed GIPHY play button alignment issue in discussions
* Activity - Added 'Relevant Activity' support
* Activity - Improved Edit Activity popup layout
* Activity - Improved 'read more' link logic for blog posts activity
* Media - Fixed create album popup, title becomes empty on a validation error message
* Media - Improved GIPHY media logic to render from GIPHY server
* Media - Fixed download issue with signed media
* Network Search - Fixed Media search results layout issues
* Email Invites - Fixed recipients profile type not assigned issue
* REST API - Fixed media API privacy issue
* REST API - Fixed API issues in media and activity
* WPML - Fixed private network enabled public content issue

= 1.5.4 =
* Profiles - Improved Profile completion module caching logic
* Notifications - Fixed custom notification blank text issue
* Notifications - Fixed forum reply notification incorrect timestamp
* Notifications - Fixed notification issue when a user comments on a blog post
* Groups - Private groups documents security issue fixed
* Groups - Fixed forums permission issue for non-members
* Forums - Fixed forum reply image repost issue
* Activity - Fixed invalid avatar on the activity post form
* Activity - Fixed blog post activity video embed issue
* Messages - code refactoring
* Network Search - Provided option to search for photos and albums
* REST API - Fixed many API issues in activity, media, messages, members, and social groups
* Compatibility - Fixed password reset email layout issue with 'Paid Memberships Pro' plugin

= 1.5.3 =
* Profiles - Improved profile and Groups cover photo upload
* Profiles - Fixed fieldset caching issue based on profile types
* Groups - Fixed issue to show hidden groups in the group directory page
* Groups - Fixed group tooltip UI issue when cover photo settings disabled
* Groups - Fixed group invite message do not show
* Groups - Fixed groups parent setting issue in the dashboard
* Forums - Fixed forums tab in profile when configured Forums directory as a child page
* Forums - Fixed forums reply editor formatting issue
* Activity - Fixed issue to show 'read more' link for blog posts activity
* Media - Updated Dropzone 5.7.2 library
* Media - Improved security
* Photos - Fixed photo description editing issue in the popup
* Messages - Fixed messages thread formatting
* Network Search - Fixed minor network search issue
* BuddyPanel - Fixed selected profile tab issue
* Performance - Improved DB queries
* Translations - Updated German (formal) language files

= 1.5.2 =
* Profiles - Provided option to re-order profile action buttons, visible when viewing other member's profiles
* Profiles - Fixed profile completion widget issue when first or last name field disabled
* Profiles - Fixed registration duplicate field issue when profile type enabled
* Forums - Fixed forums 404 issue when configured as a child page
* Activity - Fixed URL preview for Cloudflare sites not allowing default 'user-agent'
* Activity - Fixed activity editor, formatting toolbar focus issue
* Activity - Fixed activity update in other member's profile
* Photos - Fixed add photos popup layout issues
* Documents - Fixed document sub-folder download issue
* Messages - Fixed invalid gravatar issue in private messages module
* Messages - Allowing admin always to send private messages to all members on the site
* Compatibility - Fixed profile cropping compatibility issue with Jetpack plugin
* Translations - Updated German (formal) language files

= 1.5.1.1 =
* Photos - Fixed private messages photos and documents attachments security issue

= 1.5.1 =
* Profiles - Provided 'Cover Photo Repositioning' support in Profiles and Groups
* Profiles - Fixed profile completion widget profile photo issue to consider gravatar
* Profiles - Fixed profile completion widget social network field count issue
* Profiles - Fixed display name format, field visibility, and field requirement logical issue
* Groups - Provided options to Hide subgroups from Groups Directory & Group Type Shortcode
* Groups - Fixed invites module to allow sending invites to group members in bulk
* Forums - Fixed forums page not working when configured as a child page
* Forums - Fixed reply editor HTML formatting issue
* Activity - Fixed 'Edit Activity' module related critical bugs
* Activity - Fixed activity form focus issue on click
* Activity - Fixed activity form @mention delete issue
* Activity - Fixed 404 Page issue when activity settings are updated
* Media - Fixed issue to allow sending just emoji in messaging, activity, forums, etc
* REST API - Small API fixes and code enhancement in media
* REST API - New endpoints added for 'Edit Activity' feature

= 1.5.0 =
* Activity - Provided 'Edit Activity' support
* Activity - Provided settings to allow members to edit their activity posts for a duration specified
* Activity - Provided 'Edit Activity' compatibility with photos, documents, permissions, emoji, giphy, etc
* Profiles - Provided option to setup Custom Profile dropdown
* Profiles - Provided option to Hide Profile navigation
* Profiles - Fixed profile completion issue with Repeater Field Set
* Forums - Fixed discussion nested replies notification issue
* Forums - Fixed discussion reply editor formatting issue
* Media - Fixed album edit/save/cancel button layout issue
* Documents - Fixed document download link privacy issue
* Documents - Fixed document sub-folder download issue
* Messages - Improved message thread delete logic
* Messages - Fixed deleted message thread count issue
* Templates - Profile navigation template enhancement for new profile navigation hide feature
* REST API - Fixed API issues in profile, media, and social groups
* LearnDash - Fixed Social groups and LearnDash groups sync conflict
* Compatibility - Fixed frontend page conflict with Elementor plugin
* Compatibility - Fixed activity date conflict with TranslatePress plugin
* Compatibility - Fixed document layout issues with Wallstreet Theme

= 1.4.9 =
* Profiles - Fixed profile completion logic to reuse the same feature in Elementor
* Media - Fixed logic to delete GIF attachments when entry deleted
* Documents - Improved the logic to support BuddyBoss REST API
* Compatibility - Fixes for WordPress 5.5 and PHP 7.*

= 1.4.8 =
* Media - New settings to set maximum file upload size for Photos and Documents
* Media - Improved media popup code to work inside our new Elementor 'Activity' widget
* Media - Improved the logic to support BuddyBoss REST API

= 1.4.7 =
* Profiles - Fixed profile field type Gender default options unable to re-order
* Profiles - Fixed profile completion widget does not update with object caching ON
* Profiles - Fixed profile repeater fieldset, delete repeater field not working
* Groups - Fixed documents and folders add/edit/delete/move permissions for group organizer/moderator/member
* Groups - Fixed performance issue when group members increases
* Groups - Fixed hidden groups not showing for group type shortcode
* Forums - Fixed discussion reply text formatting validation
* Activity - Fixed photo comment email notification on the activity feed
* Activity - Fixed Privacy for activity attached photo
* Activity - Fixed activity preview to allow German characters
* Activity - Fixed activity post update formatting issues
* Documents - Fixed document rename does not allow non-English characters
* Messages - Improved the avatar display for multi-user message threads
* Network Search - Fixed search members based on members profile field-specific privacy
* Multisite - Fixed repair and import tools inaccessible/broken in multi-site
* REST API - Fixed API issues in activity, private messages, connection, and site permissions
* REST API - New endpoints added for Email invites profile types changes
* Compatibility - Fixed register and activate page title when RankMath plugin is active
* Compatibility - Fixed social groups and LearnDash group slug conflict
* Compatibility - Fixed courses access based on membership expiry from MemberPress plugin
* Translations - Fixed 'TB, GB, MB, KB' text not translatable for memory unit
* Translations - Fixed text for document file extension descriptions not available for translation
* Translations - Fixed admin dashboard text not translatable
* Translations - Added Hungarian language files
* Translations - Updated German (formal) language files

= 1.4.6 =
* Groups - Fixed 'Pending Invites' page unable to display more than 20 invites
* Media - Fixed photo attachments in messages not always displaying for recipients
* Messages - Fixed unable to delete a conversation if Notifications is disabled
* Activity - Fixed very long URLs unable to be entered into activity feed posts
* Private Network - Fixed homepage being visible when using 'Custom URL' for Registration
* REST API - Fixed conflict on some servers, with creating and editing posts and pages
* LearnDash - When courses are connected to groups, fixed incorrect 'Lessons' count
* LearnDash - Fixed error notices on group 'Courses' tab when 'WP_DEBUG' is enabled
* Translations - Fixed 'his, her, their' text not translatable for 'Gender' profile field
* Translations - Fixed layout issues on profile page when language set to Hungarian

= 1.4.5 =
* Profiles - Now allowing underscores in 'Nickname' field for registration and @mentions
* Activity - Fixed small images in activity feed getting scaled up and distorted
* Media - Fixed certain document types not always uploading into Forum replies
* Media - Improved the formatting of media popups in Forum replies
* REST API - Fixed API issues in private messages, activity privacy, and site permissions
* Compatibility - Fixed conflicts with Private Network settings on Memcached servers

= 1.4.4 =
* Profiles - Improved the user experience when adding a new Repeater field on frontend
* Profiles - Fixed certain profile fields types preventing Repeater fields from saving
* Forums - Fixed disabling 'Subscriptions' should remove Subscriptions from member profile
* Forums - Fixed disabling 'Favorites' should remove Favorites from member profile
* Forums - Restrict @mentions dropdown to list only members who have access to the forum
* Groups - Restrict @mentions dropdown to list only members who have joined the group
* Groups - When viewing group invitations page, fixed invitations showing incorrect dates
* Groups - Improved the experience for accepting an invite into a private group
* Media - Added media popups for all Documents and Photos posted into Forums and Messages
* Media - Added 'Download' link to media popups for all Document types and Photos
* Media - Fixed private group Documents visibility, when uploading to profiles is disabled
* Activity - Fixed comments not displaying in activity feed for blog posts and custom post types
* Activity - Fixed media attachments not displaying when commenting on a group update
* Compatibility - Fixed default Profile Type not being assigned when registering via MemberPress
* Compatibility - Fixed text icon conflict with plugin 'BuddyPress User Blog' in Forum replies
* Compatibility - Fixed missing locator icon when using plugin 'BP xProfile Location'
* Compatibility - Fixed conflicts between Documents feature and plugin 'BP Group Documents'
* Compatibility - Fixed notices adding a + symbol between words in PHP 7.4.2 and higher
* Translations - Fixed text 'Invited by' for group invites not available for translation

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


