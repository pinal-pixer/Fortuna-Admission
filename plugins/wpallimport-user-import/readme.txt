=== WP All Import - User Import Add-On ===
Contributors: soflyy, wpallimport
Requires at least: 5.0
Tested up to: 7.1
Stable tag: 1.0.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Tags: import users, user import, csv import, xml import, users

Easily import users from any XML, CSV or Excel file into WordPress with the User Import Add-On for WP All Import.

== Description ==

The User Import Add-On for [WP All Import](https://wordpress.org/plugins/wp-all-import/) makes it easy to create, update, and migrate WordPress users from any XML, CSV, or Excel file.

Use WP All Import's Drag & Drop interface to map incoming data to standard WordPress user fields. Your file does not need special column names or a fixed layout. Select the data on the right, drag it to the corresponding user field on the left, and run the import.

The User Import Add-On works with both the free and paid editions of WP All Import.

= Import WordPress User Fields from CSV/XML/Excel =

Upload XML, CSV, or Excel files directly. Importing users from a public Google Sheets URL requires WP All Import Pro.

Import user logins, email addresses, passwords, names, roles, nicknames, display names, descriptions, registered dates, nicenames, and URLs.

Passwords can be imported as plaintext, or as existing WordPress password hashes when moving users from another WordPress site. Importing the existing hashes lets users continue signing in with their current passwords.

Assign one role or multiple roles to each user, separating multiple role slugs with pipes. Enter the role value directly or map it dynamically from the import file with XPath.

= Update Existing WordPress Users =

Match incoming records to existing users by login, email address, or user ID. When the import runs again, choose exactly which standard user fields should be updated and leave the other fields alone.

WP All Import Pro can skip records whose data has not changed. WP All Import can also remove previously imported, non-administrator users that are no longer present in the import file.

= Migrate WordPress Users Between Sites =

Use the [User Export Add-On for WP All Export](https://wordpress.org/plugins/export-wp-users-xml-csv/) to export users from one WordPress site. Download the Bundle from WP All Export and upload it to WP All Import on the destination site.

The Bundle automatically maps the supported user fields included in the export. With WP All Export Pro, include User Pass and User Role to carry existing WordPress password hashes and roles into the import, allowing users to continue signing in with their current passwords.

= Free Version of the User Import Add-On =

With the free version of the User Import Add-On, you can:

* Import first and last names, logins, emails, passwords, roles, nicknames, display names, descriptions, registered dates, nicenames, and URLs.
* Import plaintext passwords or secure password hashes from another WordPress site.
* Assign one or multiple pipe-separated role slugs, including values mapped with XPath.
* Match records to existing users by login, email, user ID, or custom field.
* Choose which fields to update when an import runs again.
* Delete previously imported, non-administrator users that are no longer present in the import file.

= User Import Add-On Pro =

User Import Add-On Pro extends this free add-on with the ability to:

* Create and edit custom user-meta mappings in an import.
* Block WordPress's standard new-user, password-change, and email-change notifications during User and WooCommerce Customer imports.
* Import WooCommerce customers with their billing and shipping data and match to WooCommerce orders.

**[Upgrade to a WP All Import package that includes User Import Add-On Pro.](https://www.wpallimport.com/pricing/?utm_source=wpallimport-user-import-free&utm_medium=readme&utm_campaign=user-import)**

= Premium Support =

You can submit the [support form on our website](https://www.wpallimport.com/support/). While we try to assist users of our free plugins, support is not guaranteed and depends on our capacity. WP All Import packages that include User Import Add-On Pro come with premium support.

= Related Plugins =

[Import any XML, CSV, or Excel file to WordPress](https://wordpress.org/plugins/wp-all-import/)

[Export any WordPress data to CSV, Excel, or XML](https://wordpress.org/plugins/wp-all-export/)

[Export WordPress users to CSV, Excel, or XML](https://wordpress.org/plugins/export-wp-users-xml-csv/)

[Import Advanced Custom Fields from XML or CSV](https://wordpress.org/plugins/csv-xml-import-for-acf/)

= Related Tutorials =

[How to Import WordPress Users from CSV, Excel, or XML](https://www.wpallimport.com/documentation/how-to-import-wordpress-users-from-csv-excel-or-xml/)

[How to Export WordPress Users to CSV, Excel, Google Sheets, or XML](https://www.wpallimport.com/documentation/export-wordpress-users-to-csv-or-xml/)

[How to Migrate WordPress Users](https://www.wpallimport.com/migrate-wordpress-users/)

[How to Bulk Edit WordPress Users](https://www.wpallimport.com/bulk-edit-wordpress-users/)

== Installation ==

1. Install and activate [WP All Import](https://wordpress.org/plugins/wp-all-import/) Free or Pro.
2. Install and activate this add-on.
3. Create a new import with WP All Import and choose Users as the import type.

== Frequently Asked Questions ==

= How do I import WordPress users? =

1. Go to All Import › New Import.
2. Upload the XML, CSV, or Excel file containing your users.
3. Choose Users as the import type.
4. Drag the incoming data to the corresponding WordPress user fields.
5. Configure the import settings, then run the import.

= Does this add-on work with the free version of WP All Import? =

Yes. It works with both the free and paid editions of WP All Import.

= How do I update existing WordPress users? =

Match the incoming records to existing users by login, email address, or user ID. You can then choose which standard user fields to update when the import runs again.

= Can I import existing WordPress password hashes? =

Yes. Enable the hashed password option when mapping a password hash exported from another WordPress site.

= How do I migrate WordPress users to another site? =

Export the users from the source site with WP All Export and its User Export Add-On, then download the Bundle. Upload the Bundle to WP All Import on the destination site. Its import template maps the supported user fields included in the export. With WP All Export Pro, include User Pass and User Role to preserve existing password hashes and roles.

= Can I create custom user-meta mappings? =

Creating and editing custom user-meta mappings requires WP All Import Pro and User Import Add-On Pro. Matching existing users by custom field is available in this free add-on.

= Can I control email notifications during an import? =

User Import Add-On Pro can block WordPress's standard new-user, password-change, and email-change notifications during User and WooCommerce Customer imports. The option is enabled by default.

= Can I import WooCommerce customers? =

Importing WooCommerce customers requires WP All Import Pro and User Import Add-On Pro.

== Screenshots ==

1. Map standard WordPress user fields, passwords, and roles, with Pro-only custom user-meta mapping clearly identified.
2. Match existing users by login, email, user ID, or custom field, and choose which fields to update.

== Changelog ==

= 1.0.1 =
* improvement: works with WP All Import Pro 5.1.0

= 1.0.0 =
* Initial release.
