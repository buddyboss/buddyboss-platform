# wp bp activity get

Fetch specific activity.

## OPTIONS

[&lt;activity-id&gt;]
: Identifier for the activity.

[--fields=&lt;fields&gt;]
: Limit the output to specific fields.

[--format=&lt;format&gt;]
: Render output in a particular format.
\---
default: table

options:
  - table
  - json
  - yaml

## EXAMPLES

    $ wp bp activity get 500
    $ wp bp activity get 56 --format=json
