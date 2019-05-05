#Forum Shortcodes

BuddyBoss Platform supports forum shortcodes. To use any of the shortcodes simply create a shortcode block within any WordPress page and insert the shortcode.

Some shortcodes requires a forum, discussion, reply or discussion tag ID. View the video below to learn how to find the ID.

[embed] https://vimeo.com/324756016 [/embed]

Forums<a name="Forums"></a>
------

`[bbp-forum-index]` - This will display your entire forum index.  
`[bbp-forum-form]` - Display the ‘New Forum' form.  
`[bbp-single-forum id={insert ID}]` - Display a single forums topics. eg. `[bbp-single-forum id=32]`

Topics<a name="Topics"></a>
------

`[bbp-topic-index]` - Display the most recent 15 topics across all your forums with pagination.  
`[bbp-topic-form]` - Display the ‘New Topic' form where you can choose from a drop down menu the forum that this topic is to be associated with.  
`[bbp-topic-form forum\_id={insert ID}]` - Display the ‘New Topic Form' for a specific forum ID.  
`[bbp-single-topic id={insert ID}]` - Display a single topic. eg. `[bbp-single-topic id=4096]`

Replies<a name="Replies"></a>
-------

`[bbp-reply-form]` – Display the ‘New Reply' form.  
`[bbp-single-reply id={insert ID}]` – Display a single reply eg. `[bbp-single-reply id=32768]`

Topic Tags<a name="Topic-Tags"></a>
----------

`[bbp-topic-tags]` - Display a tag cloud of all topic tags.  
`[bbp-single-tag id={insert ID}]` - Display a list of all topics associated with a specific tag. eg. `[bbp-single-tag id=64]`

Views<a name="Views"></a>
-----

`[bbp-single-view]` - Single view - Display topics associated with a specific view. Current included ‘views' with bbPress are “popular” `[bbp-single-view id='popular']` and “No Replies” `[bbp-single-view id='no-replies']`

Search<a name="Search"></a>
------

`[bbp-search]` - Display the search input form.  
`[bbp-search-form]` - Display the search form template.

Account<a name="Account"></a>
-------

`[bbp-login]` - Display the login screen.  
`[bbp-register]` - Display the register screen.  
`[bbp-lost-pass]` - Display the lost password screen.

Statistics<a name="Statistics"></a>
----------

`[bbp-stats]` - Display the forum statistics.