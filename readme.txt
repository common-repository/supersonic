=== Plugin Name ===
Contributors: kursorA
Donate link: http://www.wp-supersonic.com/donate/donate-supersonic
Tags: cloudflare, speed, cache, optimize, security, bruteforce, CDN, performance, spam, antispam
Requires at least: 3.6
Tested up to: 4.7
Stable tag: 1.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Wordpress SuperSonic with CloudFlare

== Description ==

Important information: this plugins works only with CloudFlare!

With this plugin you can speed up Wordpress to supersonic speed.

By default CloudFlare do not caches HTML content. It can be done by adding one page rule in CloudFlare. But when site content is changed (by adding, editing or deleting post, page or comment) CloudFlare do not refreshes cached content. This functionality is taken by this plugin.
When content is changed plugin purges only files previously served to CloudFlare. It saves resources and time. You can choose which files are purged on defined events.

Wordpress SuperSonic with CloudFlare integrates Wordpress with CloudFlare for more speed and security. With this plugin Wordpress pages will load as fast as 100 miliseconds!

= Major features =
* Cloudflare API v4
* support form Cloudflare Flexible SSL
* automatically purge changed pages from CloudFlare cache (posts, pages, custom post types and associates pages: categories, tags, date archives)
* country information of commenter in comments
* bruteforce protection by bannig IP address in CloudFlare
* ban, with list, clear commenter IP address in CloudFlare from comments list
* disable Wordpress login by blocking selected countries
* disable possibility to post Wordpress comments by blocking selected countries
* block Wordpress XML-RPC for selected countries
* displays CloudFlare statistics for domain
* event logging

= Example sites with SuperSonic plugin - check how fast they loading =
* [Site 1](http://www.wp-supersonic.com/ "www.wp-supersonic.com")
* [Site 2](http://www.zespoldowna.info/ "www.zespoldowna.info")

== Installation ==

1. Upload zip archive content to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Administration area and choose SuperSonic from menu.
4. Configure CloudFlare credintials.

== Frequently Asked Questions ==

= CloudFlare is required? =

**Yes**. Without CloudFlare SuperSonic functions will not works.

= Which Page Rules should You define in CloudFlare? =

To properly working Supersonic requires **at least 3 Page Rules** in CloudFlare.

1. URL pattern: /\*.php\*
   Custom caching: Bypass cache
2. URL pattern: /\*wp-admin\*
   Custom caching: Bypass cache
3. URL pattern: /\*
   Custom caching: Cache everything
   Edge cache expire TTL: 1 week
   Browser cache expire TTL: 30 minutes

**Rules order is very important!**

= How to configure Supersonic to serve non cached pages with Admin Bar for logged in users? =

Supersonic serves front end pages as for non logged in users (except pages that are defined in options).
But for users with specified roles there is posibility to serve non cached pages. It is done by adding parameter (supersonic=wp-admin) to all frontend URLs.

CloudFlare should not cache pages with this parameter. There must be PageRule with Custom caching: Bypass cache.
If you have Page Rule with URL pattern /wp-admin/* or /wp-admin* siply change URL pattern to /\*wp-admin\*


== Screenshots ==

1. CloudFlare configuration
2. Options
3. Tools
4. Cache purge configuration
5. Security
6. Log
7. Statistics
8. Comment list

== Changelog ==

= 1.9 =
* Added feature to disable "Do not logout" functionality in wp_head - **this feature should fix social sharing plugins problems**
* Added actions and shortcodes to disable/enable "Do not logout" functionality in URLs when needed.

Actions:
`
<?php do_action( 'wpss_disable_supersonic_url' ); ?> // disables "Do not logout"
<?php do_action( 'wpss_enable_supersonic_url' ); ?> // enables "Do not logout"
`
Shortcodes:
`
[disable_supersonic_url] <!-- disables "Do not logout" ->
[enable_supersonic_url] <!-- enables "Do not logout" ->
`
* Added feature to remove supersonic=wp-admin parameter when "Do not logout" functionality is enabled - little trick in JavaScript ;)

= 1.8.2 =
* Few notices fixed

= 1.8.1 =
* New functionality in do not logout special parameter ?supersonic=wp-admin. In wp-admin area only home page link in admin bar is changed. It should fix all problems with social shares, xml sitemaps and others.

= 1.8 =
* New actions related to comments - new tab in Configuration page Comments
* Comments spammers IPs now can be banned on chellenged (CAPTCHA pagae) - works with other plugins like Aksmet
* Fixed notice: https://wordpress.org/support/topic/cron-warning/

= 1.7 =
* Bruteforce protection: added action type when blocking IP - ban IP or Cloudflare challenge page
* Bruteforce protection: added action scope - zone or all zones from user account
* Fixed notice: https://wordpress.org/support/topic/another-error-on-all-pages/

= 1.6.2 =
* Changed http:// to https:// in images linked in Documentation tab

= 1.6.1 =
* Support for Cloudflare Flexible SSL

= 1.6.0 =
* Moved to ClouFlare API v4
* Fixed warnings in security section
* Faster purging URLs from cache - 30 URLs per request (API v 4)

= 1.5.9 =
* Respect settings for admin email on bruteforce protection

= 1.5.7 =
* Added option for robots.txt (Options Tab)
* Added option for HTTP headers (Options Tab)

= 1.5.6 =
* Added action action hook wpss_update_post - for programmers

= 1.5.4 =
* Fixed bug in CF settings tab

= 1.5.2 =
* Fixed "Do you really want to log out?"

= 1.5.1 =
* Fixed notices

= 1.5 =
* Added Supersonic to Admin Bar
* Added option to enable/disable CloudFlare development mode

= 1.4 =
* Optimizations in purging with wp-cron

= 1.3.8 =
* Fixed PHP notices

= 1.3.7 =
* Fixed bug in purging function for Comments/Additional pages


= 1.3.6 =
* Optimizations in wp-cron scheduling
* Optimizations in comments - purging is depended on comment status

= 1.3.4 =
* Fixed notices in debug mode

= 1.3.2 =
* Fixed Admin Bar visibility for logged-in users
* Fixed bug in deleting comments

= 1.3.1 =
* Fixed bug in purge procedure

= 1.3.0 =
* Fixed ban/whitelist/nul IP from CF Tools tab

= 1.2.9 =
* Changed functionality in CF Settings tab - removed button Test Cloudflare Connection, changed functionality of Update Settings button, now it also tests CloudFlare connection (see screenshots)
* fixed warning in comment editor
* added link to CloudFlare Firewall in CF Tools tab

= 1.2.8 =
* Another fix in home page purging
* Fixes for other probles from support forums

= 1.2.7 =
* Added triling slash for home page on cache purging

= 1.2.6 =
* Few optimizations in collecting URLs to clear

= 1.2.5 =
* Optimizations in collecting URLs to clear
* Bug fixed in home page identyfication

= 1.2.4 =
* Optimization in "Additional URLs to clear"

= 1.2.3 =
* Added dirname(__FILE__) in include_once

= 1.2.1 =
* Fixed Call to undefined function is_user_logged_in

= 1.2.0 =
* Added configuration option for start purging CloudFlare cache without waiting for wp-cron (in Options tab)
* Added functionality to serve non cached pages for logged in users with specified role(s), this functionality is bit tricky - see FAQ

= 1.1.3 =
* Short opening tags (<?) changed to PHP opening tags (<?php)

= 1.1.2 =
* Added support for post preview

= 1.1.1 =
* Initiate first purge immediately without cron

= 1.1.0 =
* Wordpress 4.2 compatibility

= 1.0.15 =
* New log message for purge error in wp-cron
* Admin message in SuperSonic screen with pages count to purge from cache

= 1.0.14 =
* Fixed not working bulk delete in Log

= 1.0.13 =
* Fixed bug in "List of URLs to purge"

= 1.0.12 =
* Tabs renamed

= 1.0.11 =
* Added zone to CloudFlare connection test
* Cosmetic changes in statistics

= 1.0.10 =
* Fixed bug in configuration form

= 1.0.9 =
* Initial version

== Upgrade Notice ==
= 1.9 =
Thanks for using Wordpress Supersonic with Cloudflare! This release includes fixes for social plugins!
