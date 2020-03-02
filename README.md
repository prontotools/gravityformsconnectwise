# Gravity Forms ConnectWise Add-On

Integrates Gravity Forms with ConnectWise, allowing form submissions to be automatically sent to your ConnectWise account.

Developer Guide
---------------

To run, test, and develop the Multisite Login Logos plugin with Docker container, please simply follow these steps:

1. Build the container:

  `$ docker build -t wptest .`

2. Test running the PHPUnit on this plugin:

  `$ docker run -it -v $(pwd):/app wptest /bin/bash -c "service mysql start && phpunit"`

Changelog
----------
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

= 1.0 =

* Launched!
