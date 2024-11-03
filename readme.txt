=== Datolab Auto SEO ===
Contributors: herson
Donate link: https://github.com/sponsors/herson
Tags: seo, auto seo, tags, categories, openai, wp cli
Requires at least: 5.0
Tested up to: 6.3
Requires PHP: 7.2
Stable tag:2024110201
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enhances your WordPress site's SEO by automatically generating and associating SEO tags and categories for your posts.

== Description ==

The **Datolab Auto SEO** plugin enhances your WordPress site's SEO by automatically generating and associating SEO tags and categories for your posts. It uses the OpenAI API to intelligently generate relevant tags and categories based on your post content. Additionally, it can associate related posts based on shared categories and tags, improving your site's visibility and user engagement.

**Features:**

- Automatically generates SEO tags and categories for draft posts.
- Associates related posts based on shared categories and tags.
- Custom WP CLI commands for easy management and automation of SEO tasks.
- **Requires an OpenAI API Key** to function properly.

**WP CLI Commands:**

1. **Process Draft Posts**

   **Command:**
   ```
   wp datolab-auto-seo process
   ```
   **Description:** Processes all draft posts to generate SEO tags and categories.

2. **Associate Related Posts**

   **Command:**
   ```
   wp datolab-related-posts associate
   ```
   **Description:** Finds and associates related posts based on shared categories and tags.

**Notes:**

- Ensure that you have an OpenAI API key and set it in the plugin settings to enable the generation of SEO tags and categories.
- It is recommended to schedule these WP CLI commands using crontab or a similar scheduling tool for automation.

== Installation ==

1. **Upload the Plugin Files:**

   - Upload the `datolab-auto-seo` folder to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.

2. **Activate the Plugin:**

   - Activate the plugin through the 'Plugins' screen in WordPress.

3. **Configure the Plugin:**

   - Navigate to the plugin settings page and enter your OpenAI API key.

4. **Run WP CLI Commands (Optional):**

   - Use the provided WP CLI commands to process posts and associate related posts.

== Frequently Asked Questions ==

= Do I need an OpenAI API key? =

Yes, an OpenAI API key is required for the plugin to function. The plugin uses the OpenAI API to generate SEO tags and categories based on your post content.

= How do I get an OpenAI API key? =

You can obtain an API key by signing up on the [OpenAI website](https://openai.com/). Once you have the API key, enter it in the plugin settings.

= Can I schedule the WP CLI commands? =

Yes, you can schedule the WP CLI commands using crontab or any other scheduling tool available on your server. This allows the plugin to automatically process posts and associate related posts at specified intervals.

= Is there a GUI for managing the plugin? =

Currently, the plugin is primarily managed through WP CLI commands. However, basic settings can be configured through the WordPress admin dashboard.

== Changelog ==

= 2023103101 =
* Initial release of Datolab Auto SEO plugin.

== Upgrade Notice ==

= 2023103101 =
Initial release. Please ensure you have an OpenAI API key and have configured the plugin settings accordingly.

== License ==

This plugin is licensed under the [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

== Acknowledgments ==

- Thanks to the WordPress community for their continuous support and contributions.
- Special thanks to the developers of the OpenAI API for providing the tools to enhance SEO capabilities.

== Contact ==

For more information, visit the [GitHub repository](https://github.com/Datolab/wp-auto-seo) or check out the [documentation](https://github.com/Datolab/wp-auto-seo/wiki).