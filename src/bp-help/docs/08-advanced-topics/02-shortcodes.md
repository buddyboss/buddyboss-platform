#Shortcodes

Shortcodes are small pieces of WordPress-specifc code, enclosed by brackets `[ ]`, that inserts content into a page, post or widget by calling a function not normally accessible in those locations. Before we dive in please check out these WordPress references and resources all about shortcodes.

*   [List of WordPress shortcodes](https://en.support.wordpress.com/shortcodes/)
*   [Learn more about shortcodes](https://codex.wordpress.org/Shortcode)
*   [Developer shortcode API](https://codex.wordpress.org/Shortcode_API)
*   [Shortcode Generator](https://generatewp.com/shortcodes/)

BuddyBoss Platform Available Shortcodes<a name="buddyboss-platform-available-shortcodes"></a>
---------------------------------------


### Forums<a name="forums"></a>
*   `[bbp-forum-index]` displays your entire forum index.
*   `[bbp-forum-form]` displays the ‘New Forum' form.
*   `[bbp-single-forum id=$forum\_id]` displays a single forums topics. eg. `[bbp-single-forum id=32]`
*   `[bbp-topic-index]` – Display the most recent 15 topics across all your forums with pagination.
*   `[bbp-topic-form]` – Display the ‘New Topic' form where you can choose from a drop down menu the forum that this topic is to be associated with.
*   `[bbp-topic-form forum\_id=$forum\_id]` – Display the ‘New Topic Form' for a specific forum ID.
*   `[bbp-single-topic id=$topic\_id]` – Display a single topic. eg. `[bbp-single-topic id=4096]`
*   `[bbp-reply-form]` – Display the ‘New Reply' form.
*   `[bbp-single-reply id=$reply\_id]` – Display a single reply eg. `[bbp-single-reply id=32768]`
*   `[bbp-topic-tags]` – Display a tag cloud of all topic tags.
*   `[bbp-single-tag id=$tag\_id]` – Display a list of all topics associated with a specific tag. eg. `[bbp-single-tag id=64]`
*   `[bbp-single-view]` – Single view – Display topics associated with a specific view. Current included ‘views' with bbPress are “popular” `[bbp-single-view id='popular']` and “No Replies” `[bbp-single-view id='no-replies']`
*   `[bbp-search]` – Display the search input form.
*   `[bbp-search-form]` – Display the search form template.
*   `[bbp-login]` – Display the login screen.
*   `[bbp-register]` – Display the register screen.
*   `[bbp-lost-pass]` – Display the lost password screen.
*   `[bbp-stats]` – Display the forum statistics.


### Member Types<a name="member-types"></a>
*   `[member type=teacher]` - Display all members with set profile type


### Group Types<a name="group-types"></a>
*   `[group type=band]` - Display all groups with set group type.