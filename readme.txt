=== My Record Collection ===
Contributors: volmar
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JYQDTFTHS458E
Tags: music, records, collection, discogs.com, record collection
Requires at least: 2.8
Tested up to: 3.3
Stable tag: 1.0.2

My Record Collection (MRC) is a plugin for WordPress that lets you display your recordcollection on Discogs.com in your blog.

== Description ==

My Record Collection (MRC) is a plugin for WordPress that lets you display your recordcollection on Discogs.com in your blog.

== Installation ==

1. Upload the `my-record-collection`-directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the *Plugins* menu in WordPress
1. Follow the steps under *settings* to import the data.
1. Create a new WordPress Page, name it whatever you like
1. Include the following code in **HTML mode** `<!--MyRecordCollection-->`

== Frequently Asked Questions ==

= Where do i export my collection on discogs? =

Not needed in the new version (1.0+). 

if you're still using the old version: 
When you’re logged in to discogs you have a `Export My Data` link under `My Discogs`. You can also use [this link](http://www.discogs.com/users/export "Export your discogs collection") to reach the page. Remember to export your collection as XML.

= If i add a new record to my collection on discogs, will it automaticly be included in my blog? =

No but you can easily update your collection in the MRC-settings.

= How can i translate MRC to my language? =

In the `/i18n/` directory you can find `my-record-collection.pot` translate this file with Poedit (or another gettext editor) and upload the file to the same direcotory. Please email me a copy of our translation to and i will include it in the next update of MRC.

== Screenshots ==

1. Part of a record collection as seen on your blog.
2. Details about a record.
3. Image from the administration page

== Changelog ==

= 1.0.2 =
* Fix for record importing function.

= 1.0.1 =
* Small bugfixes.
* Fallback solution for users who have file_get_contents disabled.

= 1.0.0 =
* Whole plugin rewritten!
* No need to export your collection on discogs.com
* Plugin now using Discogs API 2.0
* Lot's of other changes

= 0.9.2 =
* Fixed bug that sometimes occurs when displaying the record info.

= 0.9.1 =
* Changed upload-directory to `wp-content/uploads`-directory instead of in plugin folder. This will prevent the plugin from deleting images and XML-files on update.
* Added uninstall functionality.

= 0.9 =
* Some minor bugfixes.
* Added a Swedish translation, and a .POT-file if you want to translate MRC to your language. 
* Updated the FAQ and added screenshots.

= 0.8 =
* First release.
* Please report any bugs.