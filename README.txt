=== Post Promoter Pro ===
Contributors: cklosows
Tags: post promoter pro, twitter, linkedin, facebook, bitly
Requires at least: 3.9.2
Tested up to: 4.4
Stable tag: 2.2.10
Donate link: https://postpromoterpro.com
License: GPLv2

The most effective way to promote your WordPress content.

== Description ==

You write great content, but it can get lost in the fast-moving world of social media. Post Promoter Pro makes sure your content is seen - it enables you to schedule repeat posts to social media once it has been published.

Engage followers who may have missed the original post; Post Promoter Pro allows you to customize the text that sits before the link to your content. Experiment with hashtags, a call to action, or interesting text that starts a conversation and compels your followers to view your post.

== Changelog ==
= 2.2.10 February 5, 2016 =
* FIX: Check that builder function exists before calling it
* FIX: Allow the ppp_manage_options filter to apply to all settings screens

= 2.2.9 January 1, 2016 =
* FIX: Conflict with The Events Calendar on scheduling Tweets

= 2.2.8 - December 18, 2015 =
* FIX: Invalid markup in Tweet metabox
* FIX: Spelling errors
* FIX: Properly detect MySQL version from $wpdb instead of deprecated function

= 2.2.7 September 22, 2015 =
* NEW: Retweet as Author
* TWEAK: Convert H2 tags to H1 for settings
* TWEAK: Account list table icon column is too wide
* FIX: Unchecking Tweet / Share boxes not respected on draft save
* FIX: Apostrophe in name makes page dropdown always show 'Me'
* FIX: Character count color indicator incorrect in some situations

= 2.2.6 July 16, 2015 =
* FIX: Upcoming Tweets Dashboard widget showing to subscirbers

= 2.2.5 June 15, 2015 =
* FIX: Facebook Post As dropdown showing 'Me' incorrectly
* FIX: Inputs with double quotes not showing correctly
* NEW: Ability to enable 'Share on Publish' by default for each network

= 2.2.4 May 9, 2015 =
* FIX: Twitter character count should be accurate on page load
* TWEAK: Account for attached images in Twitter character count warnings
* NEW: Twitter Cards now support Creator

= 2.2.3 =
* NEW: Dashboard widget to show our next X scheduled Tweets
* NEW: Added Unit tests
* NEW: Added warning when scheduling a Tweet that is within 30 minutes of an existing Tweet
* TWEAK: Re-Added and Improved the Tweet Character Counter

= 2.2.2 =
* FIX: If the expires_in comes back empty, force one
* TWEAK: Don't redirect to the about page on dot releases

= 2.2.1 =
* FIX: Fixed a bug in the Twitter Cards support with html in titles

= 2.2 =
* NEW: Free Form Tweet Scheduling
* NEW: Twitter Card Support
* NEW: Ability to change attached Twitter Images
* NEW: Allow local social media tokens
* TWEAK: Updated Schedule List View with attached image and better column widths
* TWEAK: Updated thumbnail sizes for Twitter and Facebook to new dimensions
* FIX: CSS Conflict in the Media List View
* FIX: 'Post As' getting reset to 'Me' after re-newing Facebook tokens
* FIX: Updated the plugin updater class to the most recent version

= 2.1.3 =
* FIX: Twitter "Share at time of Publish" content not replacing {post_title} and other tokens

= 2.1.2 =
* FIX: Facebook and LinkedIn token reminders could show a negative date
* FIX: Expiration notices caused PHP notices when disconnected
* FIX: Throw a notice up if cURL isn't enabled, and don't load the plugin
* FIX: Run the entities and slashes cleanup for Facebook and LinkedIn
* FIX: A schedule post that is unscheduled wouldn't delete scheduled shares.

= 2.1.1 =
* FIX: Corrected and made the save_post functions, to save the metabox content, more consistent
* FIX: LinkedIn reference on the Facebook metabox

= 2.1 =
* NEW: Facebook Support
* UPDATED: Redesigned Account management list with additional column for debugging
* UPDATED: Tweet length indicators now account for Featured Images
* UPDATED: Moved the plugin to load on plugins_loaded
* UPDATED: Welcome page…duh <img class="wp-smiley" src="https://postpromoterpro.com/wp-includes/images/smilies/icon_wink.gif" alt=";)" />
* FIX: Ignore featured image attaching, when no featured image is assigned to post
* FIX: LinkedIn Expiration times were incorrect (you may need to disconnect and reconnect LinkedIn)
* FIX: Improved Session usage, to help with overall performance
* FIX: Any already scheduled shares should be removed when you go back and check to ‘not schedule social media for this post’
* FIX: Don’t stomp on other Dashicons
* FIX: Remove the ‘autoload’ from our ppp_version option
* FIX: Stop direct access to core files

= 2.0.1 =
* FIX: Smarter starting of sessions to be friendly to caching services/layers

= 2.0 =
* NEW: LinkedIn Support
* NEW: WP.me Shortlink Support
* NEW: Featured Image Support
* TWEAK: Allow a 'None' option for link tracking
* TWEAK: Better code organization for easier debugging
* TWEAK: Fixing a slight bit of padding on the Twitter Meta Box content
* FIX: Correcting an issue with some hosting environments where HTML entities are not decoded
* FIX: Bit.ly auth AJAX not working on Network Sites
* FIX: Unscheduling already scheduled posts when post is updated to unschedule posts

= 1.3.0.3 =
* FIX: No more escape characters in strings being shared

= 1.3.0.2 =
* FIX: Correcting an issue when sharing on publish, when sharing on publish is not selected

= 1.3.0.1 =
* FIX: Correcting an issue with html character encoding/decoding

= 1.3 =
* NEW: Share to Twitter on Initial Publish
* NEW: Allow number of days to share to be filtered
* NEW: Ability to edit the default share text
* NEW: Allow days to be enabled and disabled by default
* NEW: Identify when two crons are scheduled at the same time
* NEW: Bit.ly support
* FIX: Better functions to identify when social networks were connected.
* FIX: Convert the Analytics to a radio set instead of checkboxes
* FIX: Spelling correction on default times

= 1.2.0.2 =
* FIX: Correcting an issue with the text of an override with no text.

= 1.2 =
* NEW: Ability to "Share Now" from the schedule view
* NEW: Welcome Screen with latest updates
* NEW: Added 'ppp_manage_role' filter for the role to see the menu item
* NEW: Better handling of the uninstall hook with an opt-in to remove all data
* FIX: i18n fixes
* FIX: Account for possible race condition in wp-cron

= 1.1.1 =
* FIX: i18n fixes for incorrect text domain and loading of text domain too late
* FIX: Performance improvement when retrieving with social tokens

= 1.1 =
* NEW: Delete a single scheduled share from the schedule view
* NEW: Allow disconnect account from Twitter (instead of only revoking global app access)
* FIX: Some characters being encoded when shared

= 1.0.1.1 =
* FIX: Cease use of closure when getting Google Tag Manager URL to support PHP &lt; 5.3
* FIX: Spelling corrections

= 1.0 =
* Initial Release
