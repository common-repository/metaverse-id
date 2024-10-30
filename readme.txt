=== Metaverse ID ===
Contributors: signpostmarv
Tags: widget, mv-id, MV-ID, Metaverse, ID, hCard, vCard, hResume, hCalendar, vEvent, iCal, identity, profile, SL, Second Life, WoW, World of Warcraft, LotRO, Metaplace, EVE, EVE Online, Progress Quest, EverQuest, EQ, EverQuest II, EQ2, Star Trek Online, STO, Champions Online, CO
Requires at least: 2.8
Tested up to: 3.6
Stable tag: 1.2.8

Display your identity from around the metaverse!

== Description ==

"Metaverse ID" for WordPress is based on the work of the currently retired swslr project. The plugin aims to allow its users to place widgets into the sidebars of their WordPress blogs that let them show of their profiles around the Metaverse.

Supported Metaverses
--------------------
* Champions Online
* EVE Online
* Everquest, Everquest II
* Free Realms
* Lord of the Rings Online
* Metaplace
* Progress Quest
* Second Life (Agni/Main Grid, Teen Second Life)
* Star Trek Online
* World of Warcraft (Europe, US)

Metaverse Configuration
--------------------
Some Metaverses (such as EVE) require some extra info in order for Metaverse ID to access the data. A menu for Metaverse ID will be added to the *Settings* menu if one of the supported Metaverses requires configuration.

If you try to update a Metaverse ID and you repeatedly get a message to the effect that the update failed, check to make sure that the Metaverse has been correctly configured!

== Installation ==

1. Ensure your server is using PHP5, this plugin does not support PHP4.
1. Upload the `metaverse-id` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. The `Metaverse ID` plugin page before any profiles have been added.
2. Adding multiple profiles in one go.
3. Profiles haven't been cached yet! Better force an update to get the profiles cached.
4. Freshly cached profiles. Ticking the box in the `Update` column can be used to force an update of the profile cache. Shortcodes for each profile are displayed.
5. Individual widgets for each ID! Inactive widgets displayed, Second Life widget in screenshot has the [MV-ID WP Avatar](http://wordpress.org/extend/plugins/mv-id-wp-avatar/) plugin active.
6. How the widgets look in the default WP theme.
7. By ommitting the id parameter in the mv-id shortcode, the plugin will attempt to display all cached IDs.
8. With the h parameter set to a number between 1 and 6, the shortcode output will include the name of the metaverse wrapped in the appropriate HTML tag.

== Requirements ==

* PHP5 (I'm using features not present in PHP4, WordPress runs fine on PHP5, so upgrade already!)
* DOMDocument (required for Second Life, LOTRO, Champions Online)
* SimpleXML (required for WoW, Metaplace, EVE)
* JSON decode support (required for Free Realms)

== ChangeLog ==

1.2.8
--------------------
* Checking the delete and update checkboxes now forces the plugin to ignore the previously cached copy of the data.

1.2.7
--------------------
* Correcting how WoW plugin handles guilds

1.2.6
--------------------
* Updating to use Blizzard's current API

1.2.5
--------------------
* Fixed the bug with rezdays coming back as unix epoch
* Added filter for display names


1.2.4
--------------------
* Fixing some issues with unserializing API keys
* Tweaking the code that handles the EVE API responses

1.2.3
--------------------
* Checking for EVE API error code 201

1.2.2
--------------------
* Checking for EVE API error code 106


1.2.1
--------------------
* Added error feedback to EVE Online plugin
* Fixing a bug preventing admin fields from appearing (which is required for EVE Online).


1.2
--------------------
* Fixed bugs with the widget JavaScript
* Updated screenshots, showing off the widgets & shortcode content in the Twenty Ten theme.

1.1.1
--------------------
* Disabled last-modified check for Second Life identities
* Verified compatibility with WordPress 3.0
* Trimming all names (cleaner HTML source, negates any potential issues with css white-space property)
* Made sure html output was correctly escaped

1.1.0
--------------------
* Fixed Champions Online biographies
* Fixed Second Life profiles (Linden Lab are actually using valid HTML now)
* Changed mv_id_stats to use Countable and Iterator interfaces
* Added support for Star Trek Online

1.0.3
--------------------
* Updated location of WoW images

1.0.2
--------------------
* Fixed a bug in the Free Realms plugin.
* Removed Metaplace plugin

1.0.1
--------------------
* Removed create_function in favour of static function.

1.0
--------------------
* Gave admin-level users ability to delete/update all IDs.
* Decided to remove the hCard/hResume parser from the to-do list since the chances of an MMO developer having the sense to use hcard/hResume in the web profiles for player characters is slim to none.
* Refactored & cleaned up some code.

0.15.1
--------------------
* Fixed bug with shortcode output

0.15.0
--------------------
* Added EverQuest & EverQuest II support.

0.14.1
--------------------
* Added shortcode parameter to display metaverse name as html h1-6 tag.

0.14.0
--------------------
* Added shortcode support
* Switched all instances of "global $wpdb" inside the mv_id_plugin class to use mv_id_plugin::wpdb() static method.

0.13.2
--------------------
* Fixed bug with Champions Online character images.

0.13.1
--------------------
* Fixed bug with Champions Online character biographies.

0.13.0
--------------------
* Added support for problem reporting.
* Added support for Champions Online.
* Changed "Progress Quest" skill to stat.
* Added support for displaying stats.

0.12.0
--------------------
* Added an option to switch between the WordPress HTTP API and the custom one used in previous versions of MV-ID.
* Fixed a bug with parsing the WoW Armory.
* Fixed a bug that caused the incorrect character image to be displayed.
* Added display support for guild/group insignias.

0.11.0
--------------------
* Switched to the HTTP API found in WP 2.8 (no more requirement for curl to be installed)

0.10.0
--------------------
* Added select boxes to widget options, allowing one widget per ID.
* Fixed some minor bugs
* Converted Denis de Bernardy & Semilogic's "Autolink URI" plugin to a filter in order to implement a feature suggested by Will Norris
* Converted some logic to WP's Actions & Filters, added a filter for plugins to modify widget output (post_output_mv_id_vcard)
* Converted widgets to use WP 2.8's Widget facilities

0.9.5
--------------------
* Cleaned up class name left over from using hListing
* Changed semantics of character creation dates after discussion with Tantek Çelik
 * We agreed that using the bday property wasn't quite right, so I suggested using an hCalendar event block

0.9.4
--------------------
* Added support for EVE Online
* Added support for Metaverses that require API configuration in order to use.

0.9.2/3
--------------------
* Fixed a bug with PHP safe mode/open\_basedir interfering with CURLOPT\_FOLLOWLOCATION

0.9.1
--------------------
* Delete IDs when user is demoted to subscriber or deleted
 * partially implemented, demoting a user from the batch-edit screen doesn't delete the IDs.

0.9
--------------------
* Moved Metaverse ID page from *Settings* to *Users* section
* Users above subscriber-level get seperate IDs
* Widget output strips duplicate IDs

0.8
--------------------
* Added [Skills](http://microformats.org/wiki/hresume#Skills) & Stats support.
* Stats are currently only used to supply account creation dates via the [hCard bday property](http://microformats.org/wiki/hcard).

0.7
--------------------
* Switched from hListing with self-review to [hResume](http://microformats.org/wiki/hresume)
 * adding guilds/groups as "[affiliations](http://microformats.org/wiki/hresume#Affiliations)".

0.6
--------------------
* Optimised the UI by using javascript to dynamically add more fields instead of using a fixed list of fields (which would take up more and more space with every metaverse that was added).