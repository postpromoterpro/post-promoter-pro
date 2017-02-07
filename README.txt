=== Post Promoter Pro ===
Contributors: cklosows
Tags: post promoter pro, twitter, linkedin, facebook, bitly, social media
Requires at least: 4.0
Tested up to: 4.7
Stable tag: 2.3.9
Donate link: https://postpromoterpro.com
License: GPLv2

Content promotion for WordPress, made easy

== Description ==

You write great content, but it can get lost in the fast-moving world of social media. Post Promoter Pro makes sure your content is seen - it enables you to schedule repeat posts to social media once it has been published.

Engage followers who may have missed the original post; Post Promoter Pro allows you to customize the text that sits before the link to your content. Experiment with hashtags, a call to action, or interesting text that starts a conversation and compels your followers to view your post.

== Changelog ==

= 2.3.9 February 6, 2017 =
* Fix: Possible PHP notice when building share message.
* Fix: Trashing posts with shares caused PHP warnings.
* Tweak: Updated plugin updater class to newest version.

= 2.3.8 November 11, 2016 =
* Fix: Existing Twitter character counts were not loaded on initial editing of a post.
* Fix: Twitter character count background colors were not correct on initial editing of a post.

= 2.3.7 November 6, 2016 =
* FIX: If local tokens file is empty, fallback to licensed tokens.
* FIX: Old upgrade routine showing on initial installation.
* New: Allow duplicating an existing Tweet.
* New: Improved meta box UI with new icons.
* New: Support pages natively.
* New: Save the share_id to the logs as post meta.

= 2.3.6 September 19, 2016 =
* FIX: Remove support for Bit.ly avatar.
* FIX: Incorrect scope for LinkedIn when using custom API Tokens.
* FIX: Update Twitter character counts to ignore images when warning of Tweet length.
* FIX: Translation updates and corrections in some missing textdomains.
* FIX: Fixed Bit.ly authorization not showing invalid password error.

= 2.3.5 July 16, 2016 =
* FIX: Scheduled Tweets can sometimes be marked as past-share on Facebook and LinkedIn.

= 2.3.4 July 12, 2016 =
* NEW: Added a filter for UTM Tag contents to allow customization.
* FIX: Delete action from schedule removes post meta but not scheduled event.
* FIX: Scheduled Tweets can sometimes be marked as past-share on Twitter.

= 2.3.3 July 6, 2016 =
* FIX: On existing posts, removing Share on Publish and changing the post status to Publish in the same action will cause the share to be sent.
* FIX: Twitter not always allowing deleting rows.
* FIX: Remove extra word 'within' from LinkedIn and Facebook expiring authentication messages.
* FIX: First scheduled tweet inputs can be falsely marked with past-share class when using timezones and or greater than GMT.

= 2.3.2 July 1, 2016 =
* FIX: Twitter - Card description missing when no excerpt provided and fallback is used.

= 2.3.1 June 29, 2016 =
* FIX: Possible fatal error if WP_Logging class is already declared.

= 2.3 June 29, 2016 =
* FIX: Possible fatal error if WP_Logging class is already declared.

= 2.3 June 28, 2016 =
* NEW: You can now set your Facebook and LinkedIn shares to be on a schedule instead of just at publishing.
* NEW: Notices for Facebook and LinkedIn authorization expiration are now dismissable.
* NEW: Your past Tweets are now 'hidden' when editing a post, and can be toggled to show or hide, saving space in the editor screen.
* TWEAK: Updated image thumbnail sizes for Twitter, LinkedIn, and Facebook to meet the new standards.
* TWEAK: Improved the plugin self-updater to be more efficient.
* TWEAK: All API calls are now stored using the WP_Log class, allowing for easier debugging.
* FIX: Sometimes presented with an 'invalid argument' when no Tweets are configured for a post.
* FIX: Re-authentication dates keep increasing as time goes for Facebook and LinkedIn.
* FIX: If WP_Cron is missed, there is a potential for many missed Tweets to go out at once. Only send if it's within an hour.
* FIX: Twitter descritpion meta tag was depdant on the post_excerpt.
* FIX: Twitter character count colors were inconsistantly changing.
* FIX: Scheduled shares were being recreated upon update if they were previously deleted.

= 2.2.11 February 27, 2016 =
* FIX: Fix misspelling in ppp_manage_options filter

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
