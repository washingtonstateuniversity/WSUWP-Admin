# WSUWP Admin Changelog

### 1.8.5 (July 10, 2018)

* Add CAHNRS WSUWP2 staging sites to Core and Pagebuilder plugin whitelist.
* Improve whitelisting of cahnrs.wsu.edu and extension.wsu.edu domains.

### 1.8.4 (July 9, 2018)

* Add CAHNRS WSUWP2 sites to Core and Pagebuilder plugin whitelist.

### 1.8.3 (June 29, 2018)

* Add CAHNRS CW sites to Core, Pagebuilder, and Events Calendar Pro whitelist.

### 1.8.2 (June 13, 2018)

* Add cahnrs.wsu.edu/news/ to CAHNRS Core and Pagebuilder plugin whitelists.

### 1.8.1 (June 7, 2018)

* Improve strict comparison of options when deciding to notify of a change.

### 1.8.0 (June 6, 2018)

* Notify Slack whenever `blog_public` is modified on a site.

### 1.7.2 (June 6, 2018)

* Make WP Event Calendar visible to events.wsu.edu.

### 1.7.1 (June 4, 2018)

* Add a new extension site to plugin access list.

### 1.7.0 (June 4, 2018)

* Filter Akismet to remove GDPR notice.
* Filter MSM Sitemap to include pages in sitemaps.

### 1.6.3 (May 29, 2018)

* Allow standard authentication on the platform.

### 1.6.2 (May 22, 2018)

* Restrict access to the CAHNRS Extension Core plugin.

### 1.6.1 (May 22, 2018)

* Add Extension sites to plugin access list.

### 1.6.0 (May 21, 2018)

* Prevent Gravity Forms System Status page from appearing.

### 1.5.2 (May 4, 2018)

* Add JCDREAM staging site to allowed Gutenberg list.

### 1.5.1 (April 23, 2018)

* Fix fatal error in content visibility extension.

### 1.5.0 (April 20, 2018)

* Add support for Content Visibility customizations.
* Add an AD group to Content Visibility for AOI support.

### 1.4.7 (April 12, 2018)

* Add CAHNRS core plugin to restricted plugin list.
* Fix restrictions for CAHNRS page builder plugin.

### 1.4.6 (March 29, 2018)

* Enable Gutenberg for inquvo.com.

### 1.4.5 (March 27, 2018)

* Add CAHNRS sites to plugin access list.

### 1.4.4 (March 8, 2018)

* Adjust a handful of plugin permissions.
* Add DWG (autocad) to allowed upload mime types.

### 1.4.3 (March 6, 2018)

* Hide the MSM Sitemap admin screen from the dashboard.

### 1.4.2 (February 27, 2018)

* Enable WSU People Directory for pharmacy.wsu.edu.
* Enable WP API Menus for pharmacy.wsu.edu.

### 1.4.1 (February 26, 2018)

* Fix bug in s3.wp.wsu.edu cache invalidation.

### 1.4.0 (February 15, 2018)

* Move several customizations from WSUWP Multiple Networks into this plugin.
* Clear s3.wp.wsu.edu cache when a file is deleted from S3 Uploads.

### 1.3.3 (February 13, 2018)

* Enable WP Event Calendar for stage.events.wsu.edu.
* Enable WSUWP Extended WP Event Calendar for stage.events.wsu.edu.

### 1.3.2 (January 16, 2018)

* Enable Gutenberg for globalcampus.wp.wsu.edu/playground

### 1.3.1 (December 6, 2017)

* Adjust several plugin restrictions.

### 1.3.0 (December 6, 2017)

* Remove code controlling site defaults for project.wsu.edu and sites.wsu.edu.

### 1.2.2 (November 27, 2017)

* Enable Polylang on financialaid.wsu.edu.
* Enable WSUWP Extended Polylang on financialaid.wsu.edu.

### 1.2.1 (November 9, 2017)

* Filter HTTP headers in WP Document Revisions in an improved way.

### 1.2.0 (November 7, 2017)

* Fix an error in Safari where `X-Frame-Options` headers were conflicting.

### 1.1.10 (October 26, 2017)

* Fix an issue with our Restricted Site Access hook.

### 1.1.9 (October 24, 2017)

* Enable WSU People Directory on physics.wsu.edu and stage.physics.wsu.edu.

### 1.1.8 (October 16, 2017)

* Restrict sites to members of the site in Restricted Site Access when authentication is required.

### 1.1.7 (October 5, 2017)

* Add support for excerpts to pages.

### 1.1.6 (September 25, 2017)

* Enable WP API Menus on stage.pharmacy.wsu.edu.
* Enable WSU People Directory on stage.pharmacy.wsu.edu and nursing.wsu.edu.

### 1.1.5 (September 21, 2017)

* Remove admin notices displayed by Restricted Site Access.

### 1.1.4 (September 11, 2017)

* Remove S3 upload handling now that plugin is fully auto-activated in `wp-config.php`.

### 1.1.3 (September 6, 2017)

* Enable S3 uploads on all new sites by default.

### 1.1.2 (August 30, 2017)

* Enable Community Events on Pharmacy sites.

### 1.1.1 (July 31, 2017)

* Adjust remarketing script output location.

### 1.1.0 (July 27, 2017)

* Introduce a method for managing remarketing pixels. Fun!
* Add a remarketing pixel for etm.wsu.edu.
* Remove more sites from WordPress SEO whitelist.

### 1.0.9 (July 19, 2017)

* Restrict WordPress SEO to sites that have already activated it.
* Restrict WordPress SEO to site activation only.

### 1.0.8 (June 1, 2017)

* Revert 1.0.2 change to store labs.wsu.edu in a different ES index.

### 1.0.7 (May 23, 2017)

* Move Shortcake Bakery filters to WSUWP Extended Shortcode UI.
* Filter the list of post types used with Editorial Access Manager.

### 1.0.6 (May 12, 2017)

* Restrict WP API Menus to specific sites.

### 1.0.5 (May 8, 2017)

* Restrict REST Email Proxy to the main site only.

### 1.0.4 (May 4, 2017)

* Set Safe Redirect Manager max redirects to 2000.

### 1.0.3 (May 1, 2017)

* Set Safe Redirect Manager max redirects to 1000.
* Remove `wpmu_drop_tables` filter, moved to MU plugin.

### 1.0.2 (March 24, 2017)

* Filter the index used to save labs.wsu.edu content in ES

### 1.0.1 (March 22, 2017)

* Fix include path.

### 1.0.0 (March 22, 2017)

* Refactor plugin structure.
* Auto-enable S3 uploads for new sites on a given list of domains.

### 0.7.3 (March 21, 2017)

* Restrict WSUWS WooCommerce payment gateway to specific sites.

### 0.7.2 (January 27, 2017)

* Temporarily disable strict mime type checking.

### 0.7.1 (January 26, 2017)

* Remove TablePress from WP search queries.

### 0.7.0 (December 13, 2016)

* Refactor the handling of the plugin access list.
* Add iDonate to the plugin access list.

### 0.6.24 (November 30, 2016)

* Enable WooCommerce for WSU Press sites.

### 0.6.23 (November 17, 2016)

* Enable Community Events for research.wsu.edu.

### 0.6.22 (November 15, 2016)

* Fix a bug with mime type detection and S3 Uploads.

### 0.6.21 (September 16, 2016)

* Filter password protected posts cookie to be a session cookie rather than the default 10 day expiration.
### 0.6.20

* Enable Community Events on native.wsu.edu.

### 0.6.19

* Restrict the Make Plus plugin to aswsu.wsu.edu.

### 0.6.18

* Remove several default actions added by WordPress to output RSD, manifest, and shortlink information in the header.

### 0.6.17

* Filter WSUWP Content Visibility to manage capabilities for WP Document Revisions

### 0.6.16

* Enable automatic platform user creation with valid NID via WSUWP SSO Authentication filter.
* Add support for WSUWP Content Visibility to WP Document Revisions.

### 0.6.15

* Unrestrict access to Gravity Forms Polls.

### 0.6.14

* Restrict Gravity Forms Polls to specific sites.

### 0.6.13

* Add `tabindex` as an allowed attribute for `div` elements.

### 0.6.12

### 0.6.11

* Add WooCommerce restriction.

### 0.6.10

* Additional restrictions on several other plugins.

### 0.6.9

* Allow Co Authors Plus on hydrogen.wsu.edu.

### 0.6.8

* Allow BuddyPress activation on magazine.wsu.edu/mystory.

### 0.6.7

* Remove the WSU UComm Assets Request plugin from the list of available site plugins.

### 0.6.6

* Remove the WSU People Directory from the list of available site plugins.

### 0.6.5

* Transfer removal of Events Calendar geolocation action to our WSU Extended Events Calendar plugin.

### 0.6.4

* Actually remove cache related headers for 404 requests as promised in 0.6.2.

### 0.6.3

* Fix `$this` bug introduced in 0.6.2

### 0.6.2

* Remove cache related headers for 404 requests so that Batcache and Nginx can determine cache status.

### 0.6.1

* Add CAHNRS `wip` and `wsu` themes to the API update exclude list.

### 0.6.0

* Add a `spine_option` command for WP-CLI to allow for the retrieval of specific Spine options at the command line.

### 0.5.11

* Fix a bug introduced in 0.5.10 that makes everything crash horribly due to a missing class.

### 0.5.10

* Adjust default functionality provided by Shortcake Bakery.

### 0.5.9

* Port page and media taxonomy registration from WSUWP Platform.

### 0.5.8

* Fix bug where documents could not be password protected.
* Prevent WordPress from attempting to drop tables when deleting a site.
* Clean-up unused code.

### 0.5.7

* Account for auto-users via manual option.

### 0.5.6

* Resolve possible bug when serving document revisions.

### 0.5.5

* Add support for and restrict access to Co Authors Plus.

### 0.5.4

* Restrict access to the WSUWP JSON Web Template plugin.

### 0.5.3

* Add support for the User Switching plugin and only allow global admins access.

### 0.5.2

* Add connect.murrow.wsu.edu as an allowed BuddyPress site.

### 0.5.1

* Revert changes from 0.3.3 and allow global users access to restricted sites temporarily while we develop less of a hammer.

### 0.5.0

* Manually override the display of a plugins list to restrict some plugins.

### 0.4.0

* Introduce support for WordPress SEO and provide several forced customizations.

### 0.3.8

* Register taxonomies from University Taxonomy to content types created by University Center.

### 0.3.7

* Exclude some custom themes from the WordPress theme update check.

### 0.3.6

* Fix bug in last modified date offset, introduced in 0.0.1

### 0.3.5

* Add filters to support new Duplicate and Merge Posts plugin

### 0.3.4

* Re-provide a better `Content-Type` header when serving documents from WP Document Revisions.

### 0.3.3

* Restrict site access to members of a site rather than users of the platform.

### 0.3.2

* Hotfix to broken pages from previous `Content-Type` issue.

### 0.3.1

* Provide a better `Content-Type` header when serving documents from WP Document Revisions.

### 0.3.0

* Provide default options for any portfolio sites created.

### 0.2.3

* Flush rewrite rules on new WSU Project sites.

### 0.1.1

* Filter a forked WP Document Revisions to remove support for WebDAV

### 0.1.0

* Filter Safe Redirect Manager to support a maximum of 500 redirects, from the default of 150.

### 0.0.1

* Add a **Last Updated** column to posts and pages showing the author and date the post was edited.
