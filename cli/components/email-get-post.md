#	wp bp email get-post

Get details for a post connected to an email type.

## OPTIONS

&lt;type&gt;
: The email type to fetch the post details for.

[--field=&lt;field&gt;]
: Instead of returning the whole post, returns the value of a single field.

[--fields=&lt;fields&gt;]
: Limit the output to specific fields. Defaults to all fields.

[--format=&lt;format&gt;]
: Render output in a particular format.
\---
default: table

options:
  - table
  - csv
  - json
  - yaml
\---

## EXAMPLE

    # Output the post ID for the 'activity-at-message' email type
    $ wp bp email get-post activity-at-message --fields=ID
