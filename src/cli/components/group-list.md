#	wp bp group list

Get a list of groups.

## OPTIONS

[--<field>=<value>]
: One or more parameters to pass. See groups_get_groups()

[--fields=<fields>]
: Fields to display.

[--user-id=<user>]
: Limit results to groups of which a specific user is a member. Accepts either a user_login or a numeric ID.

[--orderby=<orderby>]
: Sort order for results.
\---
default: name

options:
  - date_created
  - last_activity
  - total_member_count
  - name

[--order=<order>]
: Whether to sort results ascending or descending.
\---
default: ASC

options:
  - ASC
  - DESC

[--format=<format>]
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

[--count=<number>]
: How many group items to list.
\---
default: 50
\---

## EXAMPLES

    $ wp bp group list --format=ids
    $ wp bp group list --format=count
    $ wp bp group list --user-id=123
    $ wp bp group list --user-id=user_login --format=ids
