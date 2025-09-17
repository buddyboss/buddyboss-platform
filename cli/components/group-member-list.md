#	wp bp group member list

Get a list of group memberships.

This command can be used to fetch a list of a user's groups (using the --user-id
parameter) or a group's members (using the --group-id flag).

## OPTIONS

&lt;group-id&gt;
: Identifier for the group. Can be a numeric ID or the group slug.

[--fields=&lt;fields&gt;]
: Limit the output to specific signup fields.

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
\---

[--&lt;field&gt;=&lt;value&gt;]
: One or more parameters to pass. See groups_get_group_members()

## EXAMPLES

    $ wp bp group member list 3
    $ wp bp group member list my-group
