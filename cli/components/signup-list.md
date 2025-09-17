#	wp bp signup list

Get a list of signups.

## OPTIONS

[--&lt;field&gt;=&lt;value&gt;]
: One or more parameters to pass. See \BP_Signup::get()

[--&lt;number&gt;=&lt;number&gt;]
: How many signups to list.
\---
default: 20
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
\---

## EXAMPLES

    $ wp bp signup list --format=ids
    $ wp bp signup list --number=100 --format=count
    $ wp bp signup list --number=5 --activation_key=ee48ec319fef3nn4
