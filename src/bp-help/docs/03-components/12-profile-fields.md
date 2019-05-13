#Profile Fields

Customize your community with fully editable profile fields that allow your users to describe themselves. Many improvements have been made to profile fields, this guide will cover each topic.

*   [Add New Field](#add-new-field)
*   [Add New Field Set](#add-new-field-set)
*   [Alternate Title](http://alternate-title)
*   [Repeating Field Sets](#repeating-field-sets)
*   [Field Types](#field-types)
    *   [Gender](#gender)
    *   [Phone Number](#phone-number)

Add New Field<a name="add-new-field"></a>
-------------

To add a new field navigate to Dashboard -> Users -> Profile Fields

1.  Name (required) - This is the default label for the field
2.  Help Text
    *   Alternate Title (optional) - If you set this field then the label will show alternate title during registration
    *   Instructions (optional) - This text is displayed below the input box to assist users
3.  Type
    *   Multi Fields
        *   Checkboxes
        *   Drop Down Select Box
        *   Gender
        *   Multi Select Box
        *   Radio Buttons
    *   Single Fields
        *   Date Selector
        *   Multi-line Text Area
        *   Number
        *   Phone Number
        *   Text Box
        *   URL
4.  Submit - Save or Cancel
5.  Requirement - Optional or Required
6.  Profile Type - Select which profile types contain this field
7.  Visibility
    *   Options
        *   Public
        *   Only Me
        *   All Members
        *   My Connections
    *   Allow Members to Override visibility or Force admin selected visibility

![Add New Profile Field](https://www.dropbox.com/s/77g38o64jc3y1dt/profilefieldsaddnew.jpg?raw=1)

Add New Field Set<a name="add-new-field-set"></a>
-----------------

To add a new field set navigate to Dashboard -> Users -> Profile Fields

1.  Field Set Name (required)
2.  Field Set Description
3.  Submit - Save or Cancel
4.  Repeater Set - Allow the profile fields within this set to be repeated again and again, so the user can add multiple instances of their data.

![Add new field set](https://www.dropbox.com/s/vex6ss3jvk3j5oj/addnewfieldset.jpg?raw=1)

Alternate Title<a name="alternate-title"></a>
---------------

When creating certain profile fields you may want the title of each field differ on the registration page versus the profile page. For example, you may want to ask registering members "What is your age?" but show simply "Age" on a members' profile. The same text appears when users edit their profile information.

*   Navigate to Dashboard -> Users -> Profile Fields
*   Click Add New Field
*   Name - Age
*   Alternate Text - What is your age?
*   Instructions - Enter how old you are.
*   Type - Number
*   Save

[embed] https://vimeo.com/320529726 [/embed]

Repeating Field Sets<a name="repeating-field-sets"></a>
--------------------

Some fields may have entries that need to be repeated. For example, on a school network you may want to have School and Graduation Date together in one Field Set. Making this set repeat the member could click "Add Another" and would see the same field set repeated.

*   Dashboard -> Users -> Profile Fields
    *   Add New Field Set
        *   Name - Education
        *   Repeater Set - Enabled
        *   Save
*   Dashboard -> Users -> Profile Fields -> Education (tab)
    *   Add New Field
        *   Name - School
        *   Alternate Title - What school did you attend?
        *   Type - Text Box
        *   Save
*   Dashboard -> Users -> Profile Fields -> Education (tab)
    *   Add New Field
        *   Name - Graduation Date
        *   Alternate Title - What date did you graduate school?
        *   Type - Date Selector
        *   Save

[embed] https://vimeo.com/320530623 [/embed]

Field Types<a name="field-types"></a>
-----------

*   Multi Fields
    *   Checkboxes
    *   Drop Down Select Box
    *   Gender
    *   Multi Select Box
    *   Radio Buttons
*   Single Fields
    *   Date Selector
    *   Multi-line Text Area
    *   Number
    *   Phone Number
    *   Text Box
    *   URL

### Checkboxes<a name="checkboxes"></a>

![](https://www.dropbox.com/s/1opnb2fe0biow8e/checkboxes.gif?raw=1)

Checkboxes are used to let a member select one or more options of a limited number of choices.

### Drop Down Select Box<a name="drop-down-select-box"></a>

Drop Down select boxes are used to let members select one option of a list that rolls up into itself.

![](https://www.dropbox.com/s/d63k6ypvwwpodt3/Untitled-1-1024x240.gif?raw=1)

### Gender<a name="gender"></a>

The gender selection is different from any other option. The gender selection affects the pronoun used in the member activity feed. Selection of male will use the pronoun "his" while female will use "her", any other option will use "their". You can use the default options or add any other non-binary gender.

[embed] https://vimeo.com/320530251 [/embed]

### Multi Select Box<a name="multi-select-box"></a>

Multi Select Boxes are used to allow members to select multiple options  
from a scrollable list using the keyboard shift/ctrl key and mouse click.

![multi select box](https://www.dropbox.com/s/wvjat7ebwsp6xsf/multiselectboxes.jpg?raw=1)

### Radio Buttons<a name="radio-buttons"></a>

![](https://www.dropbox.com/s/ic7uirzjz0ry8p2/radio.gif?raw=1)

Radio buttons are used to allow members to select one option from a list.

### Date Selector<a name="date-selector"></a>

Allows users to select a date and displayed in one of several formats defined by the admin.

![](https://www.dropbox.com/s/ar32q4ad9ecnicv/dateselector.gif?raw=1)

### Multi-line Text Area<a name="multi-line-text-area"></a>

A free form text area with custom user formatting available.

![multi-line text area](https://www.dropbox.com/s/vbev8h1sovacaei/multilinetextarea.jpg?raw=1)

### Number<a name="number"></a>

Just the numbers 0-9, period, e, negative and positive symbols.

![number form field](https://www.dropbox.com/s/vbev8h1sovacaei/multilinetextarea.jpg?raw=1)

### Phone Number<a name="phone-number"></a>

Allows members to enter their phone number. Options are for US format (###) ###-#### or international.

![phone number field](https://www.dropbox.com/s/j8ys4rzoajhpq40/phonenumberfield.jpg?raw=1)

### Text Box<a name="text-box"></a>

A text box members can enter any keyboard character in to.

![](https://www.dropbox.com/s/q7jjg8uu3rgputv/textboxfield.jpg?raw=1)

### URL<a name="url"></a>

Any properly formatted URL that will be shown as a link in member profiles.

![url field](https://www.dropbox.com/s/anrnat4peyf9bm3/urlfield.jpg?raw=1)