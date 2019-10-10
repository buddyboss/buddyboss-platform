#Forum Shortcodes

Site admins can use shortcodes to display various types of forum content within WordPress. To use the shortcodes below, make sure the Forums component is enabled, and then copy and paste the shortcode onto any WordPress page, making sure to add an opening `[` and closing `]` bracket to each shortcode.

To get the required post ID for `$forum_id`, `$topic_id`, `$tag_id`, and `$reply_id` as required by certain shortcodes, you will need to log into the WordPress admin, and go into BuddyBoss > Forums and then find the post content you are looking to reference. Hover over the post and then click the 'Edit' link. Once the edit page loads you can copy the ID from the URL of the post, eg. `/wp-admin/post.php?post=47` where **47** is the ID.

---

##Forums

|Shortcode|Description|
|---|---|
|`bbp-forum-index`|Display all of your forums.|
|`bbp-forum-form`|Display the 'Create New Forum' form.|
|`bbp-single-forum id=$forum_id`|Display a specific forum's discussions, replacing `$forum_id` with the forum's post ID.|

##Discussions

|Shortcode|Description|
|---|---|
|`bbp-topic-index`|Display the most recent discussions across all your forums.|
|`bbp-single-view id='popular'`|Display popular discussions, ordered by number of replies|
|`bbp-single-view id='no-replies'`|Display all discussions that have no replies|
|`bbp-topic-form`|Display the 'New Discussion' form where you can choose from a dropdown the forum to associate with the discussion.|
|`bbp-topic-form forum_id=$forum_id`|Display the 'New Discussion' form for replying to a specific forum, replacing `$forum_id` with the forum's post ID.|
|`bbp-single-topic id=$topic_id`|Display a specific discussion, replacing `$topic_id` with the discussion's post ID.|

##Discussion Tags

|Shortcode|Description|
|---|---|
|`bbp-topic-tags`|Display a tag cloud of all discussion tags.|
|`bbp-single-tag id=$tag_id`|Display all discussions with a specific tag, replacing `$tag_id` with the discussion tag's post ID.|

##Replies

|Shortcode|Description|
|---|---|
|`bbp-single-reply id=$reply_id`|Display a specific discussion reply, replacing `$reply_id` with the reply's post ID.|

##Search

|Shortcode|Description|
|---|---|
|`bbp-search`|Display the 'Search Forums' input.|

##Statistics

|Shortcode|Description|
|---|---|
|`bbp-stats`|Display the forum statistics.|
