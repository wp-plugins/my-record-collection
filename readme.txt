﻿=== My Record Collection ===
Contributors: volmar
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JYQDTFTHS458E
Tags: music, records, collection, discogs.com, record collection
Requires at least: 2.8
Tested up to: 3.0
Stable tag: 0.8

My Record Collection (MRC) is a plugin for WordPress that lets you display your recordcollection on Discogs.com in your blog.

== Description ==

My Record Collection (MRC) is a plugin for WordPress that lets you display your recordcollection on Discogs.com in your blog. To use it you simply export your collection on Discogs to a XML-file and imports it in the MRC plugin.

== Installation ==

1. Upload the `my-record-collection`-directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the *Plugins* menu in WordPress
1. Follow the steps under *settings* to import the data.
1. Create a new WordPress Page, name it whatever you like
1. Include the following code in **HTML mode** `<!--MyRecordCollection-->`

== Frequently Asked Questions ==

= Where do i export my collection on discogs? =

When you’re logged in to discogs you have a `Export My Data` link under `My Discogs`. You can also use [this link](http://www.discogs.com/users/export "Export your discogs collection") to reach the page. Remember to export your collection as XML.

= If i add a new record to my collection on discogs, will it automaticly be included in my blog? =

Unfortunately not, the Discogs API does not have any functinality to handle collections at this moment. It’s said to be included in the next API-update. When the API is updated i’ll include a live update function to the plugin.

== Changelog ==

= 0.8 =
* First release.
* Please report any bugs.