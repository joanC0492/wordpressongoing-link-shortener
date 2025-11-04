=== Wordpressongoing Link Shortener ===
Contributors: joancochachi
Donate link: https://wordpressongoing.com
Tags: link shortener, short links, url shortener, redirect, marketing, analytics, wordpress shortener
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional plugin to shorten links with advanced prefix management and full administration directly from WordPress.

== Description ==

**Wordpressongoing Link Shortener** is a complete and professional plugin that allows you to create and manage short links directly from your WordPress dashboard.

= Main Features =

* **Full short link management** – Create and manage all your links from a centralized panel  
* **Customizable prefixes** – Define your own prefix (e.g., `/l/`, `/go/`, `/link/`) for short links  
* **Native integration** – Generate short links directly from any post or page  
* **Prefix history** – Old links remain functional even if you change the prefix  
* **Intuitive interface** – Clean, professional design integrated with WordPress  
* **Advanced search** – Find links by URL, slug, or tag  
* **URL validation** – Automatically verifies that URLs are valid  
* **Unique slugs** – Automatically prevents duplicates  
* **Multilingual** – Includes Spanish translations  

= Use Cases =

* **Digital marketing** – Create links for campaigns  
* **Social media** – Clean, professional short links  
* **Email marketing** – Short and memorable URLs  
* **Content management** – Easier handling of internal links  

= How It Works =

1. **Install and activate** the plugin  
2. **Set your prefix** in Settings > Link Shortener  
3. **Create short links** from the Link Shortener menu or directly from any post/page  
4. **Share your links** – They automatically redirect using HTTP 302  
5. **Manage and update** your links anytime you need  

= Technical Features =

* Pages and posts for link management  
* Dynamic rewrite rules for all prefixes  
* Custom metabox system  
* AJAX for fast operations  
* Full validation and sanitization  
* SEO friendly (excluded from sitemaps)  
* Custom capabilities for access control  

The plugin is designed with usability and performance in mind, providing a professional experience for both basic and advanced users.

== Installation ==

= Automatic Installation =

1. Go to your WordPress dashboard > Plugins > Add New  
2. Search for "Wordpressongoing Link Shortener"  
3. Click "Install Now"  
4. Activate the plugin  

= Manual Installation =

1. Download the plugin’s .zip file  
2. Go to Plugins > Add New > Upload Plugin  
3. Select and upload the .zip file  
4. Activate the plugin  

= Initial Setup =

1. Go to **Link Shortener > Settings**  
2. Set your preferred prefix (default `/l/`)  
3. Save changes  
4. You’re ready to start creating short links!  

== Frequently Asked Questions ==

= Can I change the prefix after creating links? =

Yes, you can change the prefix at any time. Existing links will continue to work with their original prefix, while new links will use the new one.

= Do short links affect my site’s SEO? =

No. The plugin is designed to exclude short links from sitemaps and hide SEO metaboxes for this type of content.

= Can I create short links from any post or page? =

Yes, the plugin adds a “Short Link” column in all post and page lists where you can generate links directly.

= Is there a limit to the number of short links? =

No technical limit is imposed by the plugin. The limit depends on your hosting and WordPress setup.

= Is it compatible with WordPress multisite? =

Yes. The plugin works with multisite installations, and each site manages its own links independently.

= What redirect code does it use? =

It uses HTTP 302 (temporary) redirects, which are recommended for link shorteners and marketing purposes.

== Screenshots ==

1. **Main dashboard** – Full list of all your short links with management options  
2. **Create new link** – Simple interface with real-time validation  
3. **Plugin settings** – Prefix configuration and advanced options  
4. **Integration in lists** – “Short Link” column in posts and pages for quick generation  
5. **Slug rotation modal** – Options to update slugs while keeping compatibility  

== Changelog ==

= 1.0.0 =
* Initial plugin release  
* Full short link management system  
* Customizable prefixes with history  
* Alias and slug rotation system  
* Native integration with posts and pages  
* Complete admin interface  
* URL validation and sanitization  
* Multilingual support (Spanish included)  
* Custom Post Type for links  
* Dynamic rewrite rules  
* AJAX system for fast operations  
* Automatic SEO and sitemap exclusion  

== Upgrade Notice ==

= 1.0.0 =
First version of the plugin. Install to start creating and managing professional short links directly from WordPress.

== Technical Details ==

= System Requirements =
* WordPress 5.0 or higher  
* PHP 7.4 or higher  
* Recommended PHP memory: 128MB+  

= Plugin Structure =
* Custom Post Type: `ls_link`  
* Metadata prefix: `_ls_*`  
* Settings options: `ls_*`  
* Custom capabilities for access control  

= Available Hooks =
* `ls_classes_loaded` – After the plugin classes are loaded  
* Filters available to customize behavior and UI  

= Compatibility =
* Compatible with most WordPress themes  
* Tested with popular SEO plugins (Yoast, Rank Math)  
* Compatible with cache plugins  
* Works in WordPress multisite environments  

For more technical details and developer documentation, visit: [https://wordpressongoing.com](https://wordpressongoing.com)
