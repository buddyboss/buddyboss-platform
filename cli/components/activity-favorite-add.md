# wp bp activity favorite add

Add an activity item as a favorite for a user.

## OPTIONS

&lt;activity-id&gt;
: ID of the activity to add an item to.

&lt;user&gt;
: Identifier for the user. Accepts either a user_login or a numeric ID.

## EXAMPLE

     $ wp bp activity favorite add 100 500
     Success: Activity item added as a favorite for the user.

     $ wp bp activity favorite create 100 user_test
     Success: Activity item added as a favorite for the user.
