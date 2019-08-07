#	wp bp friend list

Get a list of user's friends.

## OPTIONS

&lt;user&gt;
: ID of the user. Accepts either a user_login or a numeric ID.

[--fields=&lt;fields&gt;]
: Fields to display.

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

    $ wp bp friend list 65465 --format=ids
    
    $ wp bp friend list 2422 --format=count
