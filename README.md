Retext [![Build Status](https://travis-ci.org/jmashburn/retext.svg?branch=master)](https://travis-ci.org/jmashburn/retext)
===========================================

## Background ##

## Requirements ##

* PHP 5.4+
* MySQL
* Optional but recommended: Vagrant

### Credentials ###
`admin@local.dev/password`

## Getting Started ##

The easiest way to set this project up is with Vagrant.

If you're not familiar with Vagrant, please see <https://www.vagrantup.com/>.

1. Download and install Vagrant

2. Clone project and run `composer install` in project root

3. In root directory run `vagrant up`

### Twilio ###

Once you're up and running you'll need to configure the Twilio settings.

1. Login into your Twilio account (or create a demo account).

2. In *Manage Numbers* choose the number you want to use and configure the Messaging *Request URL* to point to `http://<url>/api/retext/twilio`

 *Protip: Instead of installing on a public IP use [ngrok](http://ngrok.com) and test it locally.*

## Step-By-Step ##

Here are the steps to manually setup this project.

1. Download and install the code in the root directory of your (LAMP) Webserver.

2. Install composer `curl -sS https://getcomposer.org/installer | php`

3. In the root directory run `composer install`

4. Create MySQL database and edit `config/database.ini` and add the nessecary information.

5. Login using **Credentials**.

6. Setup **Twilio**


## UnitTests ##

1. Be sure to have either sqlite (pdo) installed or edit the `tests/config/database.ini` to configure your test database

2. Run `phpunit` in the root directory.

### Project Lead ###

* @jmashburn

