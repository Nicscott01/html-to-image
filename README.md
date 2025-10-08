# HTML to Image Generator for Wordpress

This repo is for a wordpress plugin that saves off an html element as a raster image. It uses the html to image javascript library to generate the image on the client side browser, so there's no need to install any npm packages on the server.

It lets you use any HTML as a source for the image generation. An iframe loads on the editor screen where you can enter a css slector and click "generate". Multiple elements on the page with the same selector will be saved off. 

The use case for this is creating email signature images. A way to have a consistent "business card-looking" block at the end of the email, with a company logo, name, font, etc. When you have a lot of employees, you can load that content into a post type or repeater field, and output a loop of the business card block on a webpage (just like a post archive).

This plugin doesn't have any templating built in. Instead it relies on your theme, page builder of choice, etc.

## Features

- **Client-side Generation**: No server processing required - everything happens in the browser
- **High Quality Output**: Configurable image quality with retina/high-DPI support
- **Responsive Preview**: Test your designs across different screen sizes
- **Custom Filenames**: Use `data-csig-filename` attributes for custom file naming
- **Batch Processing**: Generate multiple images from different elements in one job
- **Google Fonts Support**: Automatic font loading and embedding for consistent rendering


## Some future features could be:
- Save on cron schedule so images are automatically updated



## Installation

### From WordPress Admin
1. Download the latest release
2. Go to Plugins > Add New > Upload Plugin
3. Upload the ZIP file and activate

### Manual Installation
1. Clone this repository to your `wp-content/plugins/` directory
3. Activate the plugin in WordPress admin

## Usage

1. **Create a Job**: Go to HTML to Image > Add New in your WordPress admin
2. **Configure Settings**:
   - **URL**: The page containing your HTML elements
   - **Selector**: CSS selector to target specific elements (e.g., `.email-signature`)
   - **Quality**: Choose from low, medium, high, or ultra quality
   - **Retina Support**: Enable for high-DPI displays
3. **Preview**: Use the responsive preview to see how your elements look
4. **Generate**: Click "Generate Images Now" to create PNG files

## Custom Filenames

Add filename attributes to your HTML elements:

```html
<div class="business-card" data-csig-filename="john-doe-card">
  <!-- Your content -->
</div>

<div class="email-signature" data-csig-filename="jane-smith-signature">
  <!-- Your content -->
</div>
```

## Technical Details

- **Frontend**: Uses [html-to-image](https://github.com/bubkoo/html-to-image) library (v1.11.11)
- **Backend**: PHP 7.4+ required
- **WordPress**: 5.0+ required, tested up to 6.8
- **Image Format**: PNG with transparency support
- **Rendering**: Client-side canvas rendering with aggressive cache busting


## Release Notes
### Version 1.1.1 
- Name change
### Version 1.1.0
- No, this is the first real release.
### Version 1.0.5
- This is the first real release. 
- Bundled the HTML-to-image renderer directly in the plugin and removed the broken vector/PDF mode so the release focuses on dependable PNG output.

## Development
### Requirements
- PHP 7.4+
- WordPress 5.0+
- Node.js (for JavaScript dependencies)

### Setup
```bash
git clone https://github.com/nicscott01/html-to-image.git
cd html-to-image
npm install
```

### File Structure
```
html-to-image/
├── assets/
│   ├── css/
│   └── js/
│       └── csig-job-editor.js
├── includes/
│   ├── class-plugin.php
│   └── class-job-post-type.php
├── client-side-image-generator.php
├── package.json
└── readme.txt
```

## Troubleshooting

### Images Not Loading in Subsequent Renders
The plugin includes aggressive cache busting to ensure images load consistently. If you still experience issues:

1. Check browser console for errors
2. Ensure all images are accessible (no CORS issues)
3. Try reducing image quality or disabling retina support

### Button Not Working
1. Check that the preview iframe has loaded completely
2. Ensure no JavaScript errors in console
3. Verify all plugin files are uploaded correctly

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

GPLv2 or later. See [LICENSE](LICENSE) for details.

## Support

- **Issues**: [GitHub Issues](https://github.com/nicscott01/html-to-image/issues)
- **Documentation**: [Plugin Documentation](https://www.crearewebsolutions.com/plugin/html-to-image-generator/)