# wp bp activity list


Retrieve a list of activities.

## OPTIONS

[--field=&lt;value&gt;]
: One or more parameters to pass to \BP_Activity_Activity::get()

[--user-id=&lt;user&gt;>]
: Limit activities to a specific user id. Accepts a numeric ID.

[--component=&lt;component&gt;]
: Limit activities to a specific or certain components.

[--type=&lt;type&gt;]
: Type of the activity. Ex.: activity_update, profile_updated.

[--primary-id=<primary-id>]
: Object ID to filter the activities. Ex.: group_id or forum_id or blog_id, etc.

[--secondary-id=<secondary-id>]
: Secondary object ID to filter the activities. Ex.: a post_id.

[--count=&lt;number&gt;]
: How many activities to list.
\---
default: 50
\---

[--format=&lt;format&gt;]
: Render output in a particular format.
\---
default: table

options:
  - table
  - csv
  - ids
  - json
  - count
  - yaml


## AVAILABLE FIELDS

These fields will be displayed by default for each activity:

* ID
* user_id
* component
* type
* action
* content
* item_id
* secondary_item_id
* primary_link
* date_recorded
* is_spam
* user_email
* user_nicename
* user_login
* display_name
* user_fullname

## EXAMPLES

    $ wp bp activity list --format=ids
    $ wp bp activity list --format=count
    $ wp bp activity list --per_page=5
    $ wp bp activity list --search_terms="Activity Comment"
    $ wp bp activity list --user-id=10
    $ wp bp activity list --user-id=123 --component=groups
