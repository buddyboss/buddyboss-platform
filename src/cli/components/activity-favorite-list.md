# wp bp activity favorite list

Get a user's favorite activity items.

## OPTIONS

&lt;user&gt;
: Identifier for the user	. Accepts either a user_login or a numeric ID.

[--&lt;field&gt;=&lt;value&gt;]
: One or more parameters to pass to \BP_Activity_Activity::get()

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

[--count=&lt;number&gt;]
: How many activity favorites to list.
\---
default: 50
\---

## EXAMPLES

    $ wp bp activity favorite list 315
