# HTML to Image Generator for Wordpress

This repo is for a wordpress plugin that saves off an html element as a raster image. It uses the html to image javascript library to generate the image on the client side browser, so there's no need to install any npm packages on the server.

It lets you use any HTML as a source for the image generation. An iframe loads on the editor screen where you can enter a css slector and click "generate". Multiple elements on the page with the same selector will be saved off. 

The use case for this is creating email signature images. A way to have a consistent "business card-looking" block at the end of the email, with a company logo, name, font, etc. When you have a lot of employees, you can load that content into a post type or repeater field, and output a loop of the business card block on a webpage (just like a post archive).

This plugin doesn't have any templating built in. Instead it relies on your theme, page builder of choice, etc.

## Some future features could be:
- Save on cron schedule so images are automatically updated

## Release Notes
### Version 1.0.5
- This is the first real release. 