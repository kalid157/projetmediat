=== Latest Post Shortcode ===
Contributors: Iulia Cazan
Tags: posts grid, posts shortcode, gutenberg block, posts grid, paginated posts list, custom posts list, configurable shortcode with UI, content shortcode, custom output, infinite pagination, ajax pagination
Requires at least: 5.5.0
Tested up to: 6.4.1
Stable tag: 11.5.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate Link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JJA37EHZXWUTJ

== Description ==
The "Latest Post Shortcode" helps you display a list or grid of the posts or pages in a page/sidebar, without having to code or know PHP. You can embed as many shortcodes in a page as you need, each shortcode configured in a different way. The shortcode for displaying the latest posts is [latest-selected-content] and can be generated very easy, the plugin will add a block or a shortcode button in the editor area.
The "Latest Post Shortcode" is configurable and allows you to create a dynamic content selection from your posts, pages and custom post types by combining, limiting and filtering what you need. The output parameters are extremely flexible, allowing you to choose the way your selected content will be displayed.

You can write your own "read more" replacement, choose wether to show/hide featured images, you can even sort the items by a number of options, paginate the output (also AJAX pagination).
This plugin should work with any modern theme.

When used with WordPress >= 5.0 + Gutenberg, the plugin shortcode can be configured from the LPS block or from any Classic block, using the plugin button.

== Demo ==
https://youtu.be/er5wnGolfw8

= Usage example =
`[latest-selected-content ver="2" perpage="4" showpages="4" display="title,date,excerpt-small" chrlimit="120" url="yes" linktext="Read more" image="thumbnail" elements="3" css="four-columns tall as-overlay light" type="post" status="publish" orderby="dateD" show_extra="ajax_pagination,light_spinner,pagination_all,date_diff,category,hide_uncategorized_category"]`

or simply

`[latest-selected-content ver="2" limit="4" type="post" display="title,content-small" chrlimit="50" image="full" elements="0" css="two-columns as-column" taxonomy="category" term="samples" orderby="dateA"]`

Starting with version 8.0.0, the plugin has a new UI and some new cool features. With this version, the output of the shortcode can be configured also as a slider, with responsive and different modes options. In this way, if you previously used the Latest Post Shortcode Extension, this is no longer needed, the plugin handles all by itself.

Starting with version 7.0.0, the plugin implements new hooks that allows for defining and managing your own custom output, through your theme or your plugins. The new hooks are:
- `lps_filter_tile_patterns` and `lps_filter_display_posts_list` - allows you to add your custom patterns
- `lps_filter_use_custom_tile_markup` - allows you to define your custom tile markup
- `lps_filter_use_custom_section_markup_start` and `lps_filter_use_custom_section_markup_end` - allows you to control the shortcode markup that is shown before and after the tiles block.
Check more hooks details and code sample at https://iuliacazan.ro/latest-post-shortcode/.

== Installation ==
* Upload `Latest Post Shortcode` to the `/wp-content/plugins/` directory of your application
* Login as Admin
* Activate the plugin through the 'Plugins' menu in WordPress

== Hooks ==
Version 2: lps/override_section_start, lps/override_section_end, lps/override_card, lps/override_card_patterns, lps/override_card_display
Version 1: lps_filter_tile_patterns, lps_filter_display_posts_list, lps_filter_use_custom_tile_markup, lps_filter_use_custom_section_markup_start, lps_filter_use_custom_section_markup_end, lps_filter_use_custom_shortcode_arguments, lps_filter_use_custom_query_arguments

== Screenshots ==
1. Example of 3 columns grid.
2. Example of 4 columns grid.
3. Example of 5 columns grid.
4. Example of pagination for a 4 columns grid.
5. Example of slider.

== Frequently Asked Questions ==
= Where can I find the button for configuring the shortcode =
The button for configuring the shortcode is displayed as an icon or as the LPS button, depending on the mode you use when adding/updating content (the posts, pages, widgets, etc.):
- in the Visual mode of the editor, the button appears in the toolbar as an icon
- in the Text mode of the editor, the button appears in the toolbar as the LPS button

The button for the shortcode configurator can be used:
- when adding/editing posts, pages, text widgets
- in the Classic block for Gutenberg
- for version >= 8.7 the button is also available in Elementor

== Changelog ==
= 11.6.0 =
* Tested up to 6.4.1
* Detect command + click and open card link in new tab for navigation enhancement
* Added two new card content vertical alignment: first top and last bottom
* Compatibility with Twenty Twenty-Four
* Dependencies and styles updates
* Fixed the post type option when there was nothing selected

= 11.5.2 =
* Tested up to 6.4
* Styles updates
* Added the option to always load the assets for backward compatibility or for custom page builders
* Fixed the pagination in the single page

= 11.5.1 =
* Tested up to 6.3.1
* Added the option to display the total number of items when using pagination (and customizable text)
* Added the dynamic parent option (filter by current post parent)
* Added the dynamic author option (filter by current post author)
* Added the card and image aspect ratio options (1:1, 16:9, 4:3, 3:2, 5:9, 4:5)
* Assets load optimization
* Fixed the card highlight style
* Fixed the slider preview in the editor

= 11.5.0 =
* Tested up to 6.3
* Added site option (only available in multisite)

= 11.4.1 =
* Fail fast if the shortcode is not used as intended, fixed the errors when the arguments are not set

= 11.4.0 =
* Added the `lps/card_output_types` new filter that allows registering custom card output options
* Added a new parameter to the `lps/override_card` filter that specifies the card selected type to make it easier targetting only specific card type
* Added the option to show a plain fallback message when no posts were found.

= 11.3.0 =
* Tested up to 6.2.2
* Added the terms exclude children option
* Added the editor placeholder for the block when there is no result
* Fixed the trim with punctuation

= 11.2.0 =
* Tested up to 6.2
* Tested with Elementor 3.12.0
* Added the image size option on the horizontal card
* Added card auto vertical alignment option
* Added the title no decoration option
* Added the title uppercase option
* Added the card hover highlight option
* Added a better precision for trimming the card content
* Improved the grouping of card options in the UI
* Minor styles updated in the shortcode UI
* Translation updates

= 11.1.0 =
* Added space between option for the pagination
* Added search key option
* Added archive option
* Added trailing chars option for trimmed strings
* Added the option to apply the chars limit to title and text together (the excerpt/content length will be computed by subtracting the title length from the chars limit)
* Added the option to hide taxonomy names when listed as extra options
* Added the option to display only one term for taxonomies listed as extra options
* Updated link nesting for SEO improvement
* Updated the admin styles
* Fixed pagination first element in archive
* Fixed Firefox full card cursor
* Fixed the block styles not loading when used in the site editor

= 11.0.0 =
* NOTE: POTENTIAL BREAKING CHANGES for older versions - PLEASE BACKUP BEFORE UPGRADING
* Tested up to 6.1.1
* Compatibility updates for Elementor 3.8.1
* Compatibility updates for PHP 8
* Added the vertical card as the default card output
* Added the horizontal card as an option in the plugin UI: image + info and info + image (no longer experimental)
* Added the drop shadow option
* Added the border radius option
* Added the image spacing option
* Added the card title color and title size options
* Added the card text color and text size options
* Added card background color option
* Added overlay image opacity option
* Added new display options for WooCommerce products: price, add to cart, price + add to cart
* Added vertical alignment option
* Added 3 insert variants for the LPS block: 2 horizontal cards, 4 column cards, and 4 overlay cards
* Added new sort options: by text post meta and by numeric post meta
* Changed the plugin UI for the styles helper
* Changed the default values for cards (height, padding, spacing, and overlay padding) to use the rem unit (recommended)
* Updated the dependencies for the LPS block
* Decoupled the front-end grid script from jQuery (excerpt for the carousel, if that is used)
* Delegated click events to the whole card (when URLs are used)
* Spinners/pagination and markup nesting updates
* Changes the output markup for improved SEO
* Global styles updates

= 10.0.0 =
* Tested up to 6.0.1
* Updated the filtered statuses, CPTs and taxonomies
* Compatibility updated for Elementor 3.7.3
* Compatibility updated for PHP 8

= 9.6.5 =
* Restore the post object inside the legacy custom templates.

= 9.6.4 =
* Assets optimization.
* Elementor block icons update for dark mode.

= 9.6.3 =
* Tested up to 5.5.
* Icon update.

= 9.6.2 =
* Tested up to 5.4.2.
* Added the LPS / Latest Post Shortcode Gutenberg block.
* Added the option to sort the posts ascending/descending by ID.

= 9.6.1 =
* Fix the tiles variable height when not using columns.

= 9.6 =
* Tested up to 5.4.
* Fix tiles stripped attributes.
* Added the CSS helper to make it easier to chain the CSS classes based on what the output should look like.
* Added options for setting different height, gaps, padding, overlay padding for desktop vs. tablet vs. mobile.
* Added the clear overlay option.
* Added the hover scale effect for tiles rendered with overlays.
* Added experimental horizontal tile (image + text and text + image).
* Fix for Elementor preview not updating when the shortcode was embedded.
* Added post classes for the articles.

= 9.5.1 =
* Added the cache feature.
* Added lightbox up arrow in small resolution view.
* Added support for 5 and 6 columns.
* Added support for aligning to left, center or right the tile content.
* Fix limit attribute update when configuring the shortcode as a slider.
* Translation updates.
* Added demo video.
* Screenshots update.

= 9.5 =
* Tested up to 5.3.2.
* Added the new option for infinite scroll (this appends posts on the page when scrolling the page).
* Added slider wrapper element.
* Limit for pagination (regardless of the total of posts that match the shortcode).
* Multiple image placeholders (one is randomly selected when the case).
* Slider default breakpoint update to 1200.
* Shortcode UI styles updates.
* Update filters for post types, statuses and taxonomies.
* Display taxonomies slugs in the UI, to make it easier to identify these when having the same titles.
* Enqueue updates.
* Fix slider preview when using the shortcode with Elementor.
* Fix warning for no post type set.
* Fix setting item select when scrolling the settings lightbox.
* Translations updates.

= 9.4 =
* Tested up to 5.3.
* Added two out of the box CSS classes that allows to center or align right the pagination.

= 9.3 =
* Tested up to 5.2.2.
* Added `$args` argument for the custom tile markup filter for allowing access from other scripts to the shortcode configuration
* Added sticky posts filter: only sticky posts, no sticky posts, no restriction in terms of sticky option
* Added one more taxonomy filter and their terms input for more targeted filtering of posts
* Added the option for line break, that will clear the content below by adding a line break after the shortcode.

= 9.2.1 =
* Added the caption extra option to be exposed in the tile.
* Updated the mime type extra option to allow for selecting a position inside the tile for it.
* Tested up to 5.2.
* Tested with Elementor 2.5.15.

= 9.2 =
* Fix no link for tiles using as-overlay class
* Added new option to hookup the media link and media lightbox (integrate with Easy FancyBox and FooBox Image Lightbox)

= 9.1 =
* Fix attachment multiple status filters
* Added extra options for showing the mime type for attachments as text or/and as CSS class for the tile wrapper

= 9.0 =
* Tested up to 5.1.1
* Added the attachment tiles options
* Added date range and dynamic range filtering for items

= 8.7 =
* Tested up to 5.1
* Added configurable title wrap element
* Added raw content option
* Added four new tile patterns with more targeted links
* Added support for Elementor, the Latest Post Shortcode functionality can be used from Elementor, as a basic element.

= 8.61 =
* Fix resize when used with Gutenberg

= 8.6 =
* Tested up to 5.0.1
* Added 4 columns styles support
* Added styles to support tiles with image as the background and the content as the overlay text
* Added overlay (dark by default, but supports the `light` option)
* Fixed carousel placeholder
* Better translation

= 8.5 =
* Fix the multiple terms filter
* Change the date difference function to use the timezone from the settings

= 8.4 =
* Tested up to 4.9.8
* Implement the "load more" feature to switch the AJAX pagination into a load more button with customizable text
* Added AJAX spinner option for light and dark colors (you can still disable the spinner)
* Added date option as date difference, so that the date to read 30 minutes ago, or 2 das ago, etc.
* Added new tile pattern that allows to display date, title, excerpt, and content
* Fix the issue when first page from pagination was not showing as selected by default
* Allow for sliders without image (not recommended as it comes with more limitations like fixed height)

= 8.3 =
* Tested up to 4.9.7
* Added exclude by tags and exclude by categories in the UI
* Added plugin translations

= 8.2 =
* Pagination update to use a more intuitive display + auto exclude
* Option to display all elements of the pagination (to display the pagination elements all the time, including the disabled elements like: go to first, previous, next, and last page, even if these are disabled)
* Workaround for Gravity Forms compatibility
* SEO improvement

= 8.1 =
* Added missing assets from the recent release

= 8.0 =
* Tested up to 4.9.6
* New UI
* Added exclude content by post IDs option
* Added exclude content by author IDs option
* Added placeholder that allows to define an image to be used for the posts that do not have a featured image, so that the lists/grid looks nicer
* Slider output options

= 7.4 =
* Tested up to 4.8.2
* Add the posts filter option by author IDs
* Update the date arguments
* Added filters for shortcode arguments and shortcode query arguments.

= 7.3 =
* Added the option to show the author, categories and tags before or after specific tile elements.

= 7.2 =
* Added the option to exclude dynamic content already exposed by the shortcodes embedded above in the current page content.

= 7.1 =
* Extra options to display author and taxonomies
* Allow to order items by random

= 7.0 =
* New shortcode config UI
* Introduce hooks for allowing the definition of custom output

= 6.4 =
* Tested up to 4.8
* Three columns style fix

= 6.3 =
* Tested up to 4.5.2
* Fix parents list
* Replace thickbox style

= 6.2 =
* Add support for post status filter
* Add support for exclude tags by slugs. The new argument sample: exclude_tags="slug1,slug2"

= 6.1 =
* Add suppress filters false
* Apply filters before displaying the post image

= 6.0 =
* Add support for the Latest Post Shortcode Slider extension

= 5.4 =
* Add the plugin link
* Separate the content and excerpt filters
* Tested up to 4.3.1

= 5.3 =
* Add the 'open in a new window' option for the links

= 5.2 =
* Implement changes to render full posts content (including the extra shortcodes)

= 5.1 =
* Add the order option (by date, title, menu order)
* Add the ajax pagination option. As the shortcode pagination relies on the wp native pagination, when using more shortcodes with pagination on the same page, the navigation will affect all shortcodes, hence, by activating the ajax pagination, each shortcode pagination will act independent

= 5.0 =
* Add the extra display of post tags of the posts
* Add filter to allow the text widget to render the content of a shortcode

= 4.2 =
* Introduce the post date in the output. The general settings will apply to the date and time format.

= 4.1 =
* Add changes to the javascript to avoid the content check for resize when lightbox resources are not available (compatibility with other plugins)

= 4.0 =
* Add Pagination Position (default to top only) so that the pagination can be displayed below the results, or above and below the results
* Add Dynamic Tag option so that you can show the posts that have one of the current page tags (current page is the page where the shortcode is embedded), without the need to specify a particular tag. This is useful to display something like "similar posts" or "on the same topic", etc.

= 3.1 =
* Populate the "Use Image" dropdown dynamically from the list of image sizes registered in the application
* Add global tile a class to differentiate when the link is applied to the entire tile content or to just the "read more" text

= 3.0 =
* Add No Pagination / Paginate Results option that allows to paginate the posts selection
* Add Records Per Page option
* Add Offset
* Add Hide / Show Pagination Navigation that allows to hide or show the pagination
* Reload Tile Pattern selection when a shortcode is selected before clicking the plugin button (reload shotcode settings in the content selection lightbox)

= 2.0 =
* Allow for different tile pattern (the html tags order in the tile: post title, image, text and read more message)
* Add visual tile pattern selector
* Add short excerpt and short content options
* Add chars limit to the excerpt or content for the tile
* Add custom "read more" message option
* Allow for the post link to wrap the entire tile or just the "read more" message if this is set

= 1.0 =
* Plugin prototype

== Upgrade Notice ==
Chars limit, custom "read more" option and different tile patterns in the new version! You should upgrade, it's free!
Donation and reviews are welcomed and will help me to continue future development.

== License ==
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

== Version history ==
11.6.0 - Tested up to 6.4.1; navigation enhancement; new card content vertical alignment: first top and last bottom; compatibility with Twenty Twenty-Four; dependencies and styles updates; fixed the post type option when there was nothing selected
11.5.2 - Tested up to 6.4; styles updates; added the option to always load the assets for backward compatibility or for custom page builders; fixed pagination in single page
11.5.1 - Tested up to 6.3.1; added total number of items when using pagination (and customizable text); added the dynamic parent option; added the dynamic author option; added the card and image aspect ratio options; assets load optimization; fixed the card highlight style; fixed the slider preview in the editor
11.5.0 - Tested up to 6.3; added site option (only available in multisite)
11.4.1 - Fail fast if the shortcode is not used as intended, fixed the errors when the arguments are not set
11.4.0 - Added new filters that allow changing the cards output, new option for a fallback message when no posts were found.
11.3.0 - Tested up to 6.2.2, added the terms exclude children option, added the editor placeholder for the block when there is no result, fixed the trim with punctuation
11.2.0 - Tested up to 6.2, tested with Elementor 3.12.0, image size option, auto vertical alignment, title no decoration, title uppercase, hover highlight, better precision for trimming the card content, improved grouping of card options in the UI, minor styles updated in the shortcode UI, translation updates
11.1.0 - New options for pagination (space between), search, archive, trailing chars, title+content trim together, hide taxonomy name, show only one term; small SEO improvement; fixed pagination first element in archive, fixed Firefox full card cursor, fixed the block styles in the site editor, admin styles updates
11.0.0 - Tested up to 6.1.1, extensive changes and additional features. NOTE: POTENTIAL BREAKING CHANGES for older versions - PLEASE BACKUP BEFORE UPGRADING
10.0.0 - Tested up to 6.0.1, updated the filtered statuses, CPTs and taxonomies, compatibility updated for Elementor 3.7.3 and PHP 8
9.6.5 - Restore the post object inside the legacy custom templates.
9.6.4 - Assets optimization, Elementor block icons update for dark mode
9.6.3 - Tested up to 5.5, icon update
9.6.2 - Tested up to 5.4.2, added the LPS / Latest Post Shortcode Gutenberg block, added the option to sort the posts ascending/descending by ID
9.6.1 - Fix the tiles variable height when not using columns
9.6 - Tested up to 5.4., fix tiles stripped attributes, added the CSS helper, added more options for responsive tiles, added the clear overlay, added the hover scale effect, added experimental horizontal tile, fix for Elementor preview update, added post classes
9.5.1 - Added the cache feature, up arrow in small resolution, support for 5 and 6 columns, support for aligning the tile content, fix limit attribute update, demo video, screenshots update
9.5 - Tested up to 5.3.2, infinite scroll option, slider wrapper element, pagination limit, multiple placeholders, UI styles update, enqueue updates
9.4 - Tested up to 5.3, align the pagination to center or to right
9.3 - Tested up to 5.2.2., added `$args` argument for the custom tile markup filter, added sticky posts filter, added one more taxonomy filter, added the option for line break after the shortcode
9.2.1 - Added caption extra option, selectable mime type position, tested with Elementor 2.5.15, tested up to 5.2.
9.2 - Fix no link for tiles using as-overlay class, new option to hookup the media link and media lightbox (integrate with Easy FancyBox and FooBox Image Lightbox)
9.1 - Fix attachment multiple status filters, added extra options for showing the mime type for attachments as text or/and as CSS class for the tile wrapper
9.0 - Tested up to 5.1.1, added the attachment tiles, added date range and dynamic range filtering for items
8.7 - Tested up to 5.1, title wrap, new tile patterns, raw content, support for Elementor
8.61 - Fix resize when used with Gutenberg
8.6 - Tested up to 5.0.1, support for four columns, support for tiles overlay, better translation
8.5 - Fix multiple terms filter, date difference update
8.4 - Tested up to 4.9.8, load more option, AJAX spinners, date difference option, excerpt and content pattern, slider without images
8.3 - Tested up to 4.9.7, exclude by tags and by categories, plugin translations
8.2 - Pagination update, SEO improvement, Gravity Forms compatibility
8.1 - Added missing assets
8.0 - New UI, new content filters, placeholder, output as slider options, tested up to 4.9.6
7.4 - Filters for shortcode arguments and shortcode query, filter by author, tested up to 4.8.2
7.3 - Added the option to show the author, categories and tags before or after specific tile elements
7.2 - Exclude dynamic content already exposed in the current page
7.1 - Extra options to display author and taxonomies, and order by random
7.0 - Introduce hooks for allowing the definition of custom output and the new UI
6.4 - Three columns style fix, tested up to 4.8
6.3 - Fix parents list, tested up to 4.4.2
6.2 - Add status support and exclude by tags support
6.1 - Apply more filters
6.0 - Add support for the Latest Post Shortcode Slider extension
5.4 - Separate the content and excerpt filters
5.3 - Open links in a new window
5.2 - Render full post content
5.1 - Posts order and ajax pagination
5.0 - Extra tags display and text widget filter
4.2 - Post date option
4.1 - Compatibility update
4.0 - Pagination position and dynamic tag
3.1 - Dynamic image dropdown option
3.0 - Pagination options
2.0 - Visual pattern selector and more features
1.0 - Initial version
