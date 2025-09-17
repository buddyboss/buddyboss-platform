#	wp bp xprofile group get

Fetch specific XProfile field group.

## OPTIONS

&lt;field-group-id&gt;
: Identifier for the field group.

[--fields=&lt;fields&gt;]
: Limit the output to specific fields.
\---
Default: All fields.
\---

[--format=&lt;format&gt;]
: Render output in a particular format.
\---
default: table

options:
  - table
  - json
  - haml
\---

## EXAMPLES

    $ wp bp xprofile group get 500
    $ wp bp xprofile group see 56 --format=json
