#Settings

This is the heart of all the features of BuddyBoss Platform. Enable the features required for your community.

### General

*   **Toolbar** - Show the Toolbar for logged out users
*   **Account Deletion** - Allow registered members to delete their own accounts
*   **Private Network** - Block entire website from logged out users (but allow Login and Register)

![buddyboss general settings](https://www.dropbox.com/s/6lmb0du9717ova6/buddybosssettingsgeneral.jpg?raw=1)

### Toolbar

![](https://www.dropbox.com/s/k6hv9849az5mzpg/toolbar.jpg?raw=1)

### Account Deletion

Admins always have access to delete member accounts.

![](https://www.dropbox.com/s/ob2n7i31g4mcesm/accountdeletion.jpg?raw=1)

### Private Network

Anyone not logged in will be redirected to the login/register page when attempting to access any page on your website.

![](https://www.dropbox.com/s/ya5cw7fl99d2nqy/privatenetwork.jpg?raw=1)

### Profiles

*   Profile Settings
    *   [bp_docs_link text="Display Name Format" slug="components/member-profiles.md" anchors="display-name-format"] - After the format has been updated, remember to run [bp_docs_link text="Repair Community tools" slug="back-end-administration/tools/repair-community.md" anchors="import-profile-types"] (Update display name to selected format in profile setting) to update all the profiles.
        *   First Name
        *   First Name & Last Name
        *   Nickname
    *   [bp_docs_link text="Profile Photo Uploads" slug="components/member-profiles.md" anchors="profile-photo-uploads"] - Allow registered members to upload avatars
    *   [bp_docs_link text="Cover Image Uploads" slug="components/member-profiles.md" anchors="cover-image-uploads"] - Allow registered members to upload cover images
*   Profile Dashboard
    *   [bp_docs_link text="Profile Dashboard" slug="components/member-profiles.md" anchors="profile-dashboard"] - Use a WordPress page as each user's personal Profile Dashboard
    *   [bp_docs_link text="Redirect on Login" slug="components/member-profiles.md" anchors="redirect-on-login"] - Redirect users to their Profile Dashboard on login
*   Profile Search
    *   [bp_docs_link text="Profile Search" slug="components/member-profiles.md" anchors="profile-search"] - Enable advanced profile search on the members directory
*   Profile Types
    *   [bp_docs_link text="Profile Types" slug="components/member-profiles.md" anchors="profile-types"] - Enable profile types to give members unique profile fields and permissions
    *   [bp_docs_link text="Display on Profiles" slug="components/member-profiles.md" anchors="display-on-profiles"] - Display each member's profile type on their profile page
    *   [bp_docs_link text="Import Profile Types" slug="back-end-administration/tools/import-profile-types.md"] - Import previously created profile types AKA member types

![user profile buddyboss settings](https://www.dropbox.com/s/clgbjilp99i5ur8/userprofilesbuddybosssettings.jpg?raw=1)

### Activity
*	Activity Settings
	*   [bp_docs_link text="Activity auto-refresh" slug="components/activity-feeds.md" anchors="activity-auto-refresh"] – Automatically check for new activity posts 
	*	[bp_docs_link text="Activity auto-load" slug="components/activity-feeds.md" anchors="activity-auto-load"] – Automatically load more activity posts when scrolling to the bottom of the page 
	*   [bp_docs_link text="Follow" slug="components/activity-feeds.md" anchors="follow"] – Allow your users to follow the activity of each other in their timeline
	*   [bp_docs_link text="Likes" slug="components/activity-feeds.md" anchors="follow"] – Allow your users to "Like" each other's activity posts
	*   [bp_docs_link text="Link Previews" slug="components/activity-feeds.md" anchors="follow"] – When links are used in activity posts, display an image and excerpt from the site
*	Posts in Activity Feeds
	*	BuddyBoss Platform - These options are self explanitory
	*   [bp_docs_link text="Blog Posts" slug="components/activity-feeds.md" anchors="blog-posts"] – When users publish new blog posts, show them in the activity feed  
	*   [bp_docs_link text="Post Comments" slug="components/activity-feeds.md" anchors="post-comments"] – Allow activity stream commenting on blog posts, forum discussions and topics  

![activity feeds buddyboss settings](https://www.dropbox.com/s/muk2a4tpuxqc6de/activityfeedsbuddybosssettings.jpg?raw=1)

### Invites

*   [bp_docs_link text="Email Subject" slug="components/email-invites.md" anchors="buddyboss-settings"] - Allow users to customize the invite email subject.
*   [bp_docs_link text="Email Content" slug="components/email-invites.md" anchors="buddyboss-settings"] - Allow users to customize the invite email body content.

![user invites buddyboss settings](https://www.dropbox.com/s/ti3m2pf0ncse5x3/userinvitesbuddybosssettings.jpg?raw=1)

Groups
------

*   [bp_docs_link text="Group Settings" slug="components/social-groups.md" anchors="buddybosssettings"]
    *   [bp_docs_link text="Group Creation" slug="components/social-groups.md" anchors="group-creation"] - Enable group create for all users
    *   [bp_docs_link text="Group Photo Uploads" slug="components/social-groups.md" anchors="group-photo-uploads"] - Allow customizable avatars for groups
    *   [bp_docs_link text="Group Cover Image Uploads" slug="components/social-groups.md" anchors="group-cover-image-uploads"] - Allow customizable cover images for groups
*   [bp_docs_link text="Group Types" slug="components/social-groups.md" anchors="group-types"] - Enable group types to better organize your groups
    *   [bp_docs_link text="Group Auto Join" slug="components/social-groups.md" anchors="group-auto-join"] - Allow specific profile types to auto join groups
*   [bp_docs_link text="Group Hierarchies" slug="components/social-groups.md" anchors="group-hierarchies"] - Allow groups to have parent groups and subgroups
    *   Group Restrict Invite - Restrict group invites to members who exist in a parent group.

![group buddyboss settings](https://www.dropbox.com/s/n6z30zsp33ixhv9/groupbuddybosssettings-1024x905.jpg?raw=1)

### Connections

*   [bp_docs_link text="Messaging" slug="components/member-connections.md" anchors="connection-settings"] \- Require users to be connected before they can message each other.

![user messages buddyboss settings](https://www.dropbox.com/s/h9zoija4g3pu0ip/userconnectionsbuddybosssettings.jpg?raw=1)

### Forums

*   Forum User Settings - Setting time limits and other user posting capabilities
    *   Disallow editing after X minutes
    *   Throttle posting every X seconds
    *   Anonymous posting - Allow guest users without accounts to create discussions and replies
    *   Auto role - Automatically give registered members a forum role
        *   Keymaster - Can read and edit any post
        *   Moderator - Can read and edit any post except keymaster
        *   Participant - Can read and only edit owned post
        *   Spectator - Can only read
        *   Blocked - No access
*   Forum Features
    *   [bp_docs_link text="Revisions" slug="components/forum-discussions.md" anchors="revisions"] - Allow discussion and reply revision logging
    *   [bp_docs_link text="Likes" slug="components/forum-discussions.md" anchors="likes"] - Allow users to mark discussions as liked
    *   [bp_docs_link text="Subscriptions" slug="components/forum-discussions.md" anchors="subscriptions"] - Allow users to subscribe to forums and discussions
    *   [bp_docs_link text="Discussion Tags" slug="components/forum-discussions.md" anchors="discussion-tags"] - Allow discussions to have tags
    *   [bp_docs_link text="Search" slug="components/forum-discussions.md" anchors="search"] - Allow forum wide search
    *   [bp_docs_link text="Post Formatting" slug="components/forum-discussions.md" anchors="post-formatting"]
    *   [bp_docs_link text="Auto-embed links" slug="components/forum-discussions.md" anchors="auto-embed-links"] - Embed media (YouTube, Twitter, Vimeo, etc...) directly into discussions and replies    
    *   [bp_docs_link text="Reply Threading" slug="components/forum-discussions.md" anchors="reply-threading"] - Enable threaded (nested) replies \[2-10\] levels deep    
*   Discussions and Replies Per Page - How many discussions and replies to show per page
    *   Discussions X per page
    *   Replies X per page
*   Forums Directory - Customize your Forums directory.
    *   Forums Directory slug
    *   Forums Prefix - Prefix all forum content with the Forums Directory slug (Recommended)
    *   Forums Directory Shows
        *   Forum Index
        *   Discussions by Last Post
*   Single Forum Slugs - Custom slugs for single forums, discussions, replies, tags, views, and search.
    *   Forum
    *   Discussion
    *   Discussion Tag
    *   Discussion View
    *   Reply
    *   Search
*   Group Forums - Forum settings for social groups.
    *   Enable Group Forums - Allow Social Groups to have their own forums
    *   Group Forums Parent - Select a forum as the parent for all group forums or use the Forums Directory as the parent.

![buddyboss forum settings](https://www.dropbox.com/s/y3mwpfx52wh6qbc/forumsettings-1.jpg?raw=1)

### Network Search

BuddyBoss Platform allows you to unify the searching of all content within your site with Network Search. You may select some or all search items to include within your search results.

*   Network Search - Search the following BuddyBoss components:
    *   Members Account
        *   User Meta
        *   Display Name
        *   User Email
        *   Username
    *   Members Details
        *   First Name
        *   Last Name
        *   Nickname
    *   Forums
        *   Discussions
        *   Replies
    *   Groups
    *   Activity
        *   Activity Comments
*   Pages and Post Types - Search the following WordPress content and post types:
    *   Blog Posts
        *   Post Category
        *   Posts Tag
        *   Posts Format
        *   Posts Meta Data
    *   Pages
        *   Pages Meta Data
    *   Media
*   Enable Autocomplete - Enable autocomplete drop-down when typing into search inputs
*   Number of Results - Limit the number of results

![buddyboss search settings](https://www.dropbox.com/s/87hwk7an0xivg88/buddybosssettingssearch.jpg?raw=1)

### Credits

*   Meet the BuddyBoss Team
*   Special thanks to the BuddyPress contributors
*   Special thanks to open source projects

![buddyboss settings credits](https://www.dropbox.com/s/99n9e1xobqtamcm/buddybosssettingscredits.jpg?raw=1)