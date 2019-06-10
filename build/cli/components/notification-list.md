#	wp bp notification list

Get a list of notifications.

## OPTIONS

[--&lt;field&gt;=&lt;value&gt;]
: One or more parameters to pass.

[--fields=&lt;fields&gt;]
: Fields to display.

[--user-id=&lt;user&gt;]
: Limit results to a specific member. Accepts either a user_login or a numeric ID.

[--component=&lt;component&gt;]
: The component to fetch notifications (groups, activity, etc).

[--action=&lt;action&gt;]
: Name of the action to fetch notifications. (comment_reply, update_reply, etc).

[--count=&lt;number&gt;]
: How many notification items to list.
\---
default: 50
\---

[--format=&lt;format&gt;]
: Render output in a particular format.
\---
default: table
options:
  - table
  - ids
  - csv
  - count
  - haml
\---

## EXAMPLES

    $ wp bp notification list --format=ids
    15 25 34 37 198

    $ wp bp notification list --format=count
    10

    $ wp bp notification list --fields=id,user_id
    | id     | user_id  |
    | 66546  | 656      |
    | 54554  | 646546   |
