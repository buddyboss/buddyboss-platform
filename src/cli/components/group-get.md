#	wp bp group get

Get a group.

## OPTIONS

&lt;group-id&gt;
: Identifier for the group. Can be a numeric ID or the group slug.

[--fields=&lt;fields&gt;]
: Limit the output to specific fields. Defaults to all fields.

[--format=&lt;format&gt;]
: Render output in a particular format.
\---
default: table

options:
  - table
  - json
  - haml---

## EXAMPLES

    $ wp bp group get 500
    $ wp bp group get group-slug
