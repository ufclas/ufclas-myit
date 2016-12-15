UFCLAS - MyIT
=============

Gravity Forms add-on that creates tickets in MyIT (Cherwell) from WordPress form submissions. 

- [Gravity Forms Add-on Framework Documentation](https://www.gravityhelp.com/documentation/article/add-on-framework/)


Requirements
------------
- Gravity Forms
- Cherwell API Key

Installation
------------
- Make sure the Gravity Forms is installed and activated.
- Install and activate plugin for a site. (Network Activate hasn't been tested)

### API Settings

In the site's dashboard menu, go to Forms > Settings. Click the 'MyIT' tab. 

1. __Add the API URL.__ Enter the URL for creating a new ticket using POST (no trailing slash).
3. __Add your API key.__ Contact UFIT for support for your unit.

### Form Settings

1. __Edit the form and add required fields (GatorLink username, UFID, and API response).__  The API Response field must be a hidden field and will contain either the new ticket number or the error message after the form has been submitted and will be saved in the entry. Example: 
2. __Enable MyIT submissions for the form__. Go to the form settings and select the MyIT tab. Check 'Enable MyIT'. This allows you to choose which forms will create new tickets.
3. __Add the required ticket information__. For the ticket summary and the description fields, use the merge tag dropdown menus on the right to use the submitted value of form fields.

### Form Confirmation Settings (optional)

#### Displaying the ticket number

In the confirmation message, click the merge tag dropdown menu next to the message field and select your API Response hidden field. This will either display the ticket number or the Cherwell error message.

__Default message__: ```Ticket #123456789 has been submitted```

#### Hiding the API Response field when using ```{all_fields}```

You can add modifiers to merge tags like {all_fields}. To hide all hidden fields, use ```{all_fields:nohidden}```

#### Displaying list field values as an HTML table instead of plain text

By default, this plugin formats list fields as plain text because HTML isn't supported in ticket descriptions and specifics fields. The exception is when list values are displayed using ```{all_fields}```. 

To change the format back to a table, add the __:html__ modifier to the merge tag. 

__Example__: If a list's merge tag is ```{Accounts:33:}```, change it to ```{Accounts:33:html}```.


Changelog
---------

### 1.1.1

- Fixes display of quotes and apostrophes in text and paragraph text fields

### 1.1

- Adds support for merge tags in the ticket summary field

### 1.0.1

- Fixes error message display to show Cherwell and API errors
- Fixes errors if form doesn't have specifics data
- No longer prepends 'Specifics.' to each specifics field name. Must be added manually in form settings.


### 1.0.0

- Initial commit using the Addon Framework, replaces defaults file.
