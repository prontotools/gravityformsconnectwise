=== Gravity Forms ConnectWise Add-On ===
Contributors: prontotools, zkancs, sandsine, saowaluck
Tags: connectwise, gravity forms, add-on, contact form, integration, psa, lead, marketing automation
Requires at least: 4.0
Tested up to: 4.9.6
Stable tag: 1.6.0
Copyright: © 2016 Pronto Tools
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Integrates Gravity Forms with ConnectWise, allowing form submissions to be automatically sent to your ConnectWise account.

== Description ==

Hands down the best solution to integrate the forms on your website with ConnectWise

Allows you to:

* Create new Companies
* Create new Contacts
* Create new Activities
* Create new Opportunities
* Create new Service Tickets

Lookups are performed for Companies and Contacts to prevent duplicate records.

Each form can have a different configuration, allowing you to create different activities/opportunities for each unique situation.

[Check out the setup guide](https://pronto.zendesk.com/hc/en-us/articles/208460256) for detailed setup information.

== Installation ==

This plugin is a [Gravity Forms](http://www.gravityforms.com/) add on, so make sure you have that installed first.

1. Upload plugin files to your plugins folder, or install using WordPress' built-in Add New Plugin installer
2. Activate the plugin
3. Go to the plugin settings page (under Forms > Settings > ConnectWise )
4. Enter your ConnectWise credentials to authenticate
5. Open an individual form and access the ConnectWise settings to create a new feed

**Catchall Company**

If the “Company” field isn’t mapped, a new record will be created for the company with the ID “catchall”. If you don’t already have this in your system, a new company with that name will be created the first time it happens.

== Frequently Asked Questions ==

= Why aren’t records correctly showing up in ConnectWise? =

It’s best to troubleshoot this in error logs. To do this, [download the Gravity Forms Logging Add-on](https://www.gravityhelp.com/documentation/article/logging-add-on/).

Once installed, activate at Forms > Settings > Logging, then select “Log all messages” for Gravity Forms ConnectWise Add-On.

Submit your problem form again, then refresh the logging page. Click “view log” to review the log for all recent submissions. You can search the page for “400” to quickly find the errors.

Just above the response code you should find a message with the explanation of the error.

= How do the Contact and Company lookups work? =

The company name will try to be matched with any existing names in your ConnectWise account. Duplicate companies might be created if there are small differences in the name, for instance adding “inc” to the end, or a misspelling.

For contacts, first a lookup is performed for the “First Name” field. Based on those results an email lookup is performed.

You can view a [detailed flow chart of the process here](https://pronto.bypronto.com/wp-content/uploads/sites/621/2016/04/ConnectWise-Logic.png).

== Screenshots ==

1. Enter your account’s credentials to authenticate. When successful you’ll see green checkmarks.
2. Access the forms settings and navigate to ConnectWise.
3. Click “Add New” feed.
4. Configure your feed settings. First Name, Last Name and Email are required for the integration to work. Company must be set if you want new companies to be created.
5. Enable conditional logic to have a feed send data only for specific events.

== Changelog ==
= 1.6.0 =
* Support ConnectWise API version 2020.1.
* Allow users to enter the ConnectWise Client ID.

= 1.5.1 =
* Escape special characters when send data to ConnectWise.

= 1.5.0 =
* Support ConnectWise API version 2018.6.

= 1.4.1 =
* Remove unused CSS `display: table;`

= 1.4.0 =
* Change default state of company to `CA`

= 1.3.1 =
* Fix bug for set primary contact when submit existing contact with different company.

= 1.3.0 =
* Enable async feed processing. Feed processing is delayed until after the confirmation has been displayed instead of occurring just after the entry is saved.

= 1.2.13 =
* Fix every submission always change primary contact for company

= 1.2.12 =
* Update opportunity notes API

= 1.2.11 =
* Remove Accept version from headers

= 1.2.10 =
* Do not process feed when settings are invalid

= 1.2.9 =
* Fix bug for create note in opportunity

= 1.2.8 =
* Strip HTML tags when map {all_fields} in initialDescription for service ticket details

= 1.2.7 =
* Not create new contact even client fills email in case-sensitive

= 1.2.6 =
* Extend timeout when request to get data from ConnectWise API

= 1.2.5 =
* Fix bug for send error email notification

= 1.2.4 =
* Improve error email notification, should not send when have error about duplicate company ID

= 1.2.3 =
* Match contact and email before create company

= 1.2.2 =
* Create Note in Contact and Company

= 1.2.1 =

* Add company_id when create new activity and new service ticket for version 2017_1

= 1.2.0 =

* Add company_id when create new opportunity for version 2017_1
* Strip HTML tags when add note to ConnectWise

= 1.1.4 =

* Add page size = 200 when get data from connectwise

= 1.1.3 =

* Fix unsued version control path

= 1.1.2 =

* Add prefix for staging URL

= 1.1.1 =

* Retry with DefaultContact when DefaultContactId fail

= 1.1.0 =

* Service ticket type, Service ticket subtype, Service ticket item
* Have better API version control

= 1.0.2 =

* Improved banner ads path in JS script (no hardcode).

= 1.0.1 =

* Fixed banner ads path in JS script.

= 1.0.0 =

* Launched!




