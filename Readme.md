UFCLAS - MyIT
=============

Gravity Forms add-on that creates tickets in MyIT (Cherwell) from WordPress form submissions.

Add-on Framework Documentation: https://www.gravityhelp.com/documentation/article/add-on-framework/

Requirements
------------
- Gravity Forms
- Cherwell API Key

Installation
------------
- Install and activate for a site.
- Forms > Settings > MyIT: Add your API settings
- Form > Edit: Add a hidden field named 'api_response', for example. This field will contain the the API response message in the entry.
- Form > Settings > MyIT: Check 'Enable MyIT' and add required ticket information for the form.
- Form > Settings > Confirmations (optional): Add your 'api_response' field to the confirmation message to display either the ticket number or the API error message.

Changelog
---------

- 1.0.0 - Initial commit using the Addon Framework, replaces defaults file.
