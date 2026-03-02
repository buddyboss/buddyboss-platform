#	wp bp component list

Get a list of components.

## OPTIONS

[--type=&lt;type&gt;]
: Type of the component (all, optional, retired, required).
\---
default: all
\---

[--status=&lt;status&gt;]
: Status of the component (all, active, inactive).
\---
default: all
\---

[--fields=&lt;fields&gt;]
: Fields to display (id, title, description).

[--format=&lt;format&gt;]
: Render output in a particular format.
\---
default: table
\---

options:
  - table
  - count
  - csv
  - haml


## EXAMPLES

    $ wp bp component list --format=count
    10

    $ wp bp component list --status=inactive --format=count
    4
