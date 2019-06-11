# wp bp activity favorite remove

Remove an activity item as a favorite for a user.

## OPTIONS

&lt;activity-id&gt;
: ID of the activity to remove a item to.

&lt;user&gt;
: Identifier for the user. Accepts either a user_login or a numeric ID.

[--yes]
: Answer yes to the confirmation message.

## EXAMPLES

     $ wp bp activity favorite remove 100 500
     Success: Activity item removed as a favorite for the user.

     $ wp bp activity favorite delete 100 user_test --yes
     Success: Activity item removed as a favorite for the user.
