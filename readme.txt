=== Thermal API ===
Contributors: voceplatforms
Tags: thermal, JSON, API
Requires at least: 3.5
Tested up to: 3.5.1
Stable tag: 0.7.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Thermal is the WordPress plugin that gives you the power of WP_Query in a RESTful API.

== Description ==
Thermal is the WordPress plugin that gives you the power of WP_Query in a RESTful API. Thermal supports client-based decisions that when combined with a responsive design framework, allow for a truly responsive application leveraging a WordPress content source.

**Minimum Requirements**

* PHP >= 5.3.0

== Installation ==

1. Upload the `thermal-api` directory to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Optional: Define `Voce\Thermal\API_BASE` in `wp-config.php` to change the API root url (defaults to `[SITE URL]/wp_api/[API VERSION]`).

== Frequently Asked Questions ==

= Is there a github repo? I love me some submodules! =

Yes. https://github.com/voceconnect/thermal-api

== Changelog ==
= 0.7.5 =
* Fixed missing media content for unattached featured images or images in content.

= 0.7.4 = 
* Added entity filters for posts, users, taxonomies, comments, and terms.

= 0.7.3 =
* Fixed conversion of per_page argument for posts.

= 0.7.2 =
* Fixed references to MAX_X_PER_PAGE constants

= 0.7.1 =
* Fixed tests and implementation of media attachments on posts response.

= 0.7.0 =
* Added endpoints for comments.
* Misc bug fixes.
* Updated documentation to include JSONP handling.
* Split endpoint handling into separate controllers/models.
* Changed 'include' argumetn to 'in' for terms and users.

= 0.6.1 =
* Fixed error with missing namespace around dispatcher initialization.

= 0.6.0 =
* Improved handling of missing slashes around API_BASE constant.
* Applying VOCE\Thermal namespace to API_BASE matching.
* Introduced PHP version check before allowing activation.

= 0.5.1 =
* Initial public version of Thermal API.