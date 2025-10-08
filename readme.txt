=== HTML to Image Generator ===
Contributors: nicscott01
Tags: html-to-image, screenshot, png, image-generator, email-signature, business-cards
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.1.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate PNG images from HTML elements on your website using client-side rendering technology.

== Description ==

HTML to Image Generator allows you to create high-quality PNG images from any HTML element on your website. Perfect for generating email signatures, business cards, marketing materials, and any visual content that needs to be shared as an image.

**Key Features:**

* Client-side image generation (no server processing required)
* High-quality PNG output with retina support  
* Responsive design preview modes
* Custom CSS injection for perfect styling
* Batch processing for multiple elements
* Custom filename support via data attributes
* No external dependencies or API calls

**Perfect For:**

* Email signatures
* Business cards
* Marketing materials  
* Social media graphics
* Print-ready designs

**How It Works:**

1. Create a new "Image Generation Job"
2. Enter the URL of the page containing your HTML elements
3. Specify a CSS selector to target specific elements
4. Configure output settings (quality, retina support, etc.)
5. Click "Generate Images Now" to create PNG files

The plugin uses advanced client-side rendering technology to capture HTML elements directly in your browser, ensuring perfect fidelity and no server load.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/html-to-image` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to 'HTML to Image' in your WordPress admin menu
4. Create your first image generation job

== Frequently Asked Questions ==

= How does this work? =

The plugin uses client-side JavaScript technology (html-to-image library) to render HTML elements directly in your browser as PNG images. No server processing or external services required.

= Can I generate retina/high-resolution images? =

Yes! Enable "Retina Support" in the job settings for crisp, high-DPI images perfect for modern displays and print.

= How do I target specific elements? =

Use CSS selectors in the "Element Selector" field. For example:
* `.my-card` - targets elements with class "my-card"
* `#signature` - targets element with ID "signature"  
* `.email-signature img` - targets images inside elements with class "email-signature"

= Can I customize the generated filenames? =

Yes! Add a `data-csig-filename` attribute to your HTML elements:
`<div class="card" data-csig-filename="john-doe-card">...</div>`

= What image formats are supported? =

Currently PNG format, which provides the best quality and transparency support for most use cases.

= Does this work with any WordPress theme? =

Yes! The plugin works independently of your theme and can capture elements from any webpage accessible to your WordPress installation.

== Screenshots ==

1. Main job creation interface with URL and selector configuration
2. Preview modes showing responsive design options  
3. Generated images list with download links
4. Settings panel with quality and retina options

== Changelog ==

= 1.1.1 =
* Enhanced image caching and reliability for consistent renders
* Improved retina support with progressive fallback
* Better error handling and detailed logging
* Fixed button state management and iframe loading issues
* Added aggressive cache busting for image consistency
* Improved font loading and Google Fonts integration

= 1.1.0 =
* Major improvements to image generation reliability
* Enhanced retina support with configurable pixel ratios
* Better error handling and logging
* Fixed button state management issues
* Improved iframe loading detection

= 1.0.0 =
* Initial release
* Core HTML to image generation functionality
* Responsive preview modes
* Basic settings and configuration
* Custom filename support

== Upgrade Notice ==

= 1.1.1 =
Significant improvements to image generation reliability and retina support. Fixes issues with images not appearing in subsequent renders. Update highly recommended.

= 1.1.0 =
Major improvements to image generation reliability and retina support. Update recommended.