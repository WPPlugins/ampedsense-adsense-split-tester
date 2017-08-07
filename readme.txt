=== AmpedSense - AdSense Split Tester ===
Contributors: ezoic
Tags: adsense, ads, advertising, google
Requires at least: 3.6
Tested up to: 4.6.1
Stable tag: 4.69
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Optimize your Google AdSense earnings by split testing ad sizes, locations, types, and more. Inject ads within posts, pages, widgets, or shortcodes

== Description ==

How do you know if your blog has the best AdSense configuration?

The AmpedSense plugin allows you to split test ads to find the most profitable AdSense setup.

*   **Optimize ad placement** - Do you know if it's better to place your ads above or below your content? In the sidebar? What about embedded within your articles? Test various standard positions as well as custom positions with our widget.
*   **Optimize ad size** - There are 18 different ad sizes (that's a lot!). Does bigger get you more revenue?
*   **Optimize ad type** - Display ads vs text ads vs link units? Which give you the most earnings?
*   **Optimize ad colors** - Stick with Google's default, or try to match the color scheme of your own site?
*   **Test custom ad snippets** - Want to test other ad networks compared to AdSense? You can paste any custom HTML or Javascript snippet and test head to head.
*   **Easy to use** - No need to insert code or modify any html files. Just upload the plugin and everything's done through a simple settings panel in wordpress.
*   **Integrated reporting** - Easily view stats within wordpress (we'll pull the essential analytics from AdSense automatically), or go direct to AdSense and run custom reports.
*   **Test unproven AdSense ads** - Google just released a new ad type? Test beta ad units (such as their new 'responsive ads') before they're proven.
*   **Multiple tests for different content** - Want to run some ads on pages, and another set on posts? No problem. Want to place specific ads on your homepage vs the rest of your site? Simple.
*   **Exclude specific pages/posts** - No one wants ads on their contact page. Easily choose which pages or posts to exclude from receiving ads.
*   **Desktop vs Mobile** - Do responsive ads work better on mobile than on desktop? Segment different ads to run on your mobile traffic.

Learn more at http://www.ampedsense.com/

== Installation ==

AmpedSense follows standard plugin procedures:

1. Install automatically through the wordpress plugin directory, or manually by uploading amped-sense.zip to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Click on the AmpedSense menu item in your left sidebar menu
4. Before anything can be done, AmpedSense must know about your AdSense account. It will ask you to authenticate using the Google account AdSense is set up under. This grants read-only permission so that ads can be credited to your account and click, rpm, and earnings stats can be shown for your split tests.
5. A walkthrough of creating your first split test can be found here: http://www.ampedsense.com/creating-adsense-split-test-in-wordpress/

AmpedSense is compatible with almost all themes and plugins. A few exceptions noted:

* Placing ads within content does not work when a theme is using its own editor, such as with Thrive Visual Editor and OptimizePress.

== Frequently Asked Questions ==

= Who is this for? =

Anyone who wants better earnings from their wordpress site with Google AdSense.

Many people are disappointed by how little they earn when they first place Adsense on their blog. I felt the same way. But if you're willing to try a few configurations and have a little bit of patience, I promise you'll be surprised.

Have a blog or wordpress site? Check. Have an AdSense account? You're in.

= Do I have to be technical? =

No! AmpedSense requires no code or html experience. If you can use WordPress and run your blog, you can run AmpedSense.

= How long does it take to find the optimal AdSense configuration? =

Test length depends on your traffic and number of variants you're testing. A simple test between 2 positions can be run in a matter of days. Larger tests may take weeks, but the more traffic you have the quicker you'll complete the split test.

= Will it make my site ugly by adding lots of ads? =

Unlike other ad testing platforms, you are in complete control of what you choose to test. You decide what you want to test, and you can easily preview every configuration before they go live. Only add as many ads as you're comfortable with (In fact, you'll probably find out that after a certain number additional ads don't earn you more).

== Screenshots ==

1. Just a few of the features of AmpedSense
2. Linking your AdSense account
3. Creating an ad recipe to test
4. Ad locations you can try
5. More ad customizations
6. Create different segments to seperate ads tests for device types, pages, posts, and categories
7. Results of a split test

== Changelog ==

= 4.68 =
* Fixing calendar bug (year 2022)

= 4.67 =
* Minor bugfix for Shortcode D/E

= 4.66 =
* Support for HTTPS

= 4.65 =
* Fixed Shortcode D, E, F rendering

= 4.64 =
* Cleared up Publisher ID confusion
* Compatible with Wordpress 4.6

= 4.63 =
* Increased from 6 to 10 ad units per recipe (premium users)

= 4.62 =
* Fix color bug in Admin area

= 4.61 =
* Added ability to notate recipes
* Increased support for PHP 5.2

= 4.60 =
* Fixed bug in category detection (Thanks Dan!)

= 4.59 =
* New AmpedSense forums
* Media query mobile detection

= 4.58 =
* Fix 'ADEND' bug on empty articles

= 4.57 =
* PHP tag bug fix

= 4.56 =
* Better mobile detection again

= 4.55 =
* Support for older versions of PHP (<5.3)

= 4.54 =
* More accurate mobile detection

= 4.53 =
* Default date for reporting adjusted
* Better explanation of ad recipe

= 4.52 =
* Minor bug fix for cloning recipes

= 4.51 =
* Minor bug fix when no recipes in segment in client-mode

= 4.50 =
* Cache friendly mode officially stable (with mobile detection based on screen resolution instead of browser)
* More consistent injection within content
* 3 more widget positions
* 3 more shortcode positions
* Cache friendly hints
* Fixed rare login issue on settings page

= 4.08 =
* Better reporting on extremely large sites
* More efficient Google API calls
* Fixed error when creating segments on sites with more than 5,000 posts

= 4.07 =
* Client side render mode option (BETA) - Cache friendly!
* New segment types - Category Lists and Specific Category List
* Bug fixes: segment naming, date picker

= 4.06 =
* Premium features unlocked

= 4.05 =
* Added better Google login handling

= 4.04 =
* Fixed issue with editing segments creates a new one
* See updated status of your actions

= 4.03 =
* Added 'every url' as segment option
* switched to wp's internal wp_get_file_contents
* fixed 'segment deleted' message when toggling segment
* updated status message background color

= 4.0 =
* Clone recipe (into other segments as well)
* Shortcode without additional shortcode plugin
* Both Padding and Margin can be specified (previously just padding)
* 6 new ad sizes (including responsive!)
* Better mobile detection (phone vs tablet), plus more mobile segments
* Hide segments not being used
* Various bug and security fixes

= 3.0 =
* Introduced segments

= 2.0 =
* Easier authentication

= 1.0 =
* Initial version

== Additional Info ==

Take a [tour](http://www.ampedsense.com/tour/)

Read some [success stories](http://www.ampedsense.com/success-stories/)

Optimization tips on the [AmpedSense blog](http://www.ampedsense.com/blog/)
