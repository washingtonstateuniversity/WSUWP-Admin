# WSUWP Admin Changelog

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
