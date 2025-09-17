#	wp bp group invite list

Get a list of invitations from a group.

## OPTIONS

--group-id=&lt;group&gt;
: Identifier for the group. Accepts either a slug or a numeric ID.

--user-id=&lt;user&gt;
: Identifier for the user. Accepts either a user_login or a numeric ID.

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

    $ wp bp group invite list --user-id=30 --group-id=56
