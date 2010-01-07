=== WP Rails Authenticate ===
Contributors: jystewart
Tags: authentication, users
Requires at least: 2.8.0
Tested up to: 2.9.1
Stable tag: trunk
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=10973638

Log users in against their accounts in a Ruby on Rails application.

== Description ==

Many people use wordpress to run blogs alongside their Ruby on Rails applications. And many 
Ruby on Rails applications use very similar login systems, derived from the restful_authentication 
or clearance plugins. This plugin provides hooks so that your wordpress blog can read your rails 
app's database.yml file, connect to that database and authenticate the user against your rails app.

This plugin requires the syck PHP extension for parsing yaml. Instructions for installing syck are available at
http://trac.symfony-project.org/wiki/InstallingSyck

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `wp-rails-authenticate` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the WP Rails Authenticate option under Settings and enter the path to your database.yml file

== Frequently Asked Questions ==

= I use a different method of encryption to the default. How do I change it? =

To change encryption methods you will need to edit the plugin code. Override the function 
WP_Rails_Authentication#apply_encryption with your preferred encryption method.

= My blog is on the same domain as my rails app? Can I share sessions between them (single sign-on)? =

Due to ruby and PHP using different serialisation approaches we've not (yet) got an easy way to share 
sessions.

= My blog runs on a different server from my rails app. How do I share accounts between them? =

Providing your blog server can access the database, you simply need an appropriate database.yml file.

= Are there other approaches to this problem?

Since this plugin was developed, the [Rails Integration API](http://wordpress.org/extend/plugins/rails-integration-api/) 
plugin has been released. That requires you to add some API code to your rails app, but 
may be easier if you want to keep all the encryption logic solely in rails or can't add an extra
module to PHP.

== Upgrade Notice ==

This is the first version so I'm the only one who'll be doing any upgrading.

== To Do ==
* Add automated tests
* Solicit user feedback on further options

== Changelog ==

= 1.0 =
* Consolidated code in use in various projects
* Prepared for first release
