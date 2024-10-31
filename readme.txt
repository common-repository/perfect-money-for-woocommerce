=== Plugin Name ===
Contributors: Sergdall
Donate link:
Tags: woocommerce, payments, perfect money, gateway
Requires at least: 3.0.1
Tested up to: 3.7.1
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Perfect Money Wordpress plugin allows accepting Perfect Money on WooCommerce based web-shop.

== Description ==

This plugin allows accepting Perfect Money as payment method for the following currencies:
USD and EUR.
This limitation is related to Perfect Money supported currencies list.
If you have US dollar or Euro as a currency in your shop you can freely accept Perfect Money
the same way you accept for example Paypal.

== Installation ==

This section describes how to install the plugin and get it working.

As this plugin developed for WooCommerce you need to have it installed and activated on your 
Wordpress in order to accept Perfect Money as payment method.

As soon as you did the step before please upload "perfectmoney" to the "/wp-content/plugins/" directory.

Then go to Wordpress admin and activate the plugin in Plugins sections.

The final steps is to setup Perfect Money settings under WooCommerce:
1. Goto WooCommerce -> Settings -> General options and make sure that you selected USD or EUR 
   as currency for your shop. Only these currencies are currently accepted by Perfect Money.
2. Goto WooCommerce -> Settings -> Payment Gateways -> Perfectmoney and fill in all the fields on this page.

After all these instructions are done you are ready to accept Perfect Money on your site!

== Changelog ==

= 1.0 =
* Initial release