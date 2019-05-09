#	wp bp message list

Get a list of messages for a specific user.

## OPTIONS

--user-id=&lt;user&gt;
: Identifier for the user. Accepts either a user_login or a numeric ID.

[--&lt;field&gt;=&lt;value&gt;]
: One or more parameters to pass. See \BP_Messages_Box_Template()

[--fields=&lt;fields&gt;]
: Fields to display.

[--count=&lt;number&gt;]
: How many messages to list.
\---
default: 10
\---

[--format=&lt;format&gt;]
: Render output in a particular format.
\---
default: table

options:
  - table
  - ids
  - count
  - csv
  - json
  - haml
\---

## EXAMPLES

    $ wp bp message list --user-id=544 --format=count
    10

    $ wp bp message list --user-id=user_login --count=3 --format=ids
    5454 45454 4545 465465
