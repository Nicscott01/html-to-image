=== Client Side Image Generator ===
Contributors: nicscott01
Tags: image generation, html to image, screenshot, capture, png
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate PNG images from HTML elements on your website using client-side rendering technology.

== Description ==

The Client Side Image Generator plugin allows you to capture HTML elements as high-quality PNG images directly from your WordPress admin. Perfect for creating consistent email signatures, business cards, or any visual content that needs to be saved as an image.

**Key Features:**

* **No Server Dependencies**: Uses client-side JavaScript rendering - no server-side image processing required
* **Flexible Targeting**: Use CSS selectors to capture any HTML element on your pages
* **Multiple Formats**: Generate PNG images with customizable quality settings
* **Responsive Capture**: Preview and capture at different viewport sizes (desktop, tablet, mobile, custom)
* **Batch Processing**: Capture multiple elements with the same selector in one operation
* **File Management**: Built-in tools to download, preview, and delete generated images
* **Retina Support**: High-DPI image generation for crisp, professional results

**How It Works:**

1. Create an "Image Job" with your target URL and CSS selector
2. Preview the page in the built-in iframe
3. Choose your viewport size and image quality settings
4. Click "Generate Images Now" to capture PNG files
5. Download or manage your generated images from the admin panel

**Perfect For:**

* Email signature images for teams
* Business card generation
* Social media graphics
* Product mockups
* Website screenshots
* Marketing materials

The plugin integrates seamlessly with any theme or page builder and doesn't require any special templating - it works with your existing HTML structure.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/client-side-image-generator` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to **Image Jobs → Add New** to create your first capture job.
4. Configure the job settings with your target URL and CSS selector.
5. Use the sidebar preview panel to generate images.

== Frequently Asked Questions ==

= What CSS selectors can I use? =

You can use any valid CSS selector. Examples:
* `.my-business-card` - captures all elements with this class
* `#header-logo` - captures the element with this ID  
* `.team-member .card` - captures nested elements
* `article.post` - captures article elements with the post class

= What image formats are supported? =

Currently, the plugin generates PNG images with customizable quality settings. PNG format ensures the best quality and transparency support.

= Does this work with any theme or page builder? =

Yes! The plugin captures whatever HTML is rendered on your page, regardless of how it was created (theme, Gutenberg, Elementor, Beaver Builder, etc.).

= Are there any server requirements? =

The plugin uses client-side rendering, so there are no special server requirements beyond standard WordPress hosting. No ImageMagick, GD, or other image libraries needed.

= Can I capture elements from external websites? =

Due to browser security restrictions (CORS), you can only capture elements from pages on the same domain as your WordPress installation.

= What happens if an element contains images? =

The plugin includes advanced image loading and cache-busting technology to ensure images render consistently in captures, even for subsequent generations.

== Screenshots ==

1. **Job Management** - Create and manage image capture jobs from the WordPress admin
2. **Live Preview** - Preview your target page in different viewport sizes before capturing
3. **Generation Controls** - Simple interface to generate images with quality and format options
4. **File Management** - View, download, and manage all generated image files
5. **Settings Panel** - Configure capture settings including URL, selector, and output options

== Changelog ==

= 1.1.0 =
* Enhanced image loading with aggressive cache-busting for consistent renders
* Improved button state management and user interface reliability
* Added retina/high-DPI support with progressive fallbacks
* Fixed JavaScript dependency issues that prevented button functionality
* Enhanced error handling and console logging for better debugging
* Improved iframe loading coordination and timing

= 1.0.5 =
* Initial public release
* Bundled html-to-image library directly in plugin
* Removed unstable vector/PDF modes to focus on reliable PNG output
* Core functionality for HTML element capture and PNG generation

== Upgrade Notice ==

= 1.1.0 =
This version significantly improves image generation reliability, especially for pages with images. All users should upgrade for better consistency and enhanced retina support.

== Support ==

For support, feature requests, or bug reports, please visit the plugin's support forum or contact the developer.

== Technical Details ==

* **Rendering Engine**: html-to-image JavaScript library (v1.11.11)
* **Browser Support**: Modern browsers with Canvas API support
* **Image Processing**: Client-side canvas rendering
* **File Storage**: WordPress uploads directory
* **Security**: Follows WordPress security best practices