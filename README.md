# WooCommerce Square - Prevent Image Override

A WordPress plugin that prevents Square images from overriding site images when the image override setting is disabled, even when `force_update` is true.

## Description

This plugin provides a workaround for a bug in the WooCommerce Square plugin where Square images can override local product images even when the "Override product images" setting is disabled. This typically occurs during forced synchronization operations (`force_update=true`).

The plugin intercepts HTTP requests for Square images and blocks them when:
- The image URL is from Square's hosting (AWS S3)
- The WooCommerce Square plugin's image override setting is disabled
- The request would otherwise bypass the setting due to `force_update`

## Features

- ✅ Prevents Square images from overriding local product images when override is disabled
- ✅ Works even during forced synchronization (`force_update=true`)
- ✅ Respects the WooCommerce Square plugin's image override setting
- ✅ Only blocks Square-hosted images (doesn't affect other image sources)
- ✅ Lightweight and efficient - uses WordPress's `pre_http_request` filter
- ✅ Well-documented code with inline comments

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- WooCommerce Square plugin (must be installed and active)

## Installation

1. Download or clone this repository
2. Upload the `woocommerce-square-prevent-image-override` folder to `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Ensure the WooCommerce Square plugin is installed and active

Alternatively, you can install it as a single-file plugin by placing `woocommerce-square-prevent-image-override.php` directly in your plugins directory.

## How It Works

The plugin uses WordPress's `pre_http_request` filter to intercept HTTP requests before they are executed. When a request is made to download an image from Square's hosting:

1. The plugin checks if the URL is from Square's image hosting (AWS S3 buckets)
2. It verifies that the WooCommerce Square plugin is active
3. It checks the image override setting from the Square plugin's settings handler
4. If image override is disabled, it returns a `WP_Error` to abort the download
5. If image override is enabled, it allows the request to proceed normally

## Configuration

No configuration is required. The plugin automatically reads the image override setting from the WooCommerce Square plugin and respects it.

To control whether Square images override local images:
1. Go to WooCommerce → Settings → Square
2. Find the "Override product images" setting
3. Enable or disable it as needed
4. This plugin will automatically respect your choice

## Technical Details

### Square Image Detection

The plugin identifies Square images by checking for these URL patterns:
- `items-images-production.s3.*.amazonaws.com` (primary Square AWS S3 hosting)
- Other Square CDN domains matching patterns like `square.*cdn`, `square.*image`, `squareup.*image`

### Implementation Approach

The plugin uses the `pre_http_request` filter (preferred method) which intercepts requests before they are made. This is more efficient than filtering after the download occurs.

An alternative approach using `image_sideload_extensions` is included but commented out, as the `pre_http_request` method is cleaner and more performant.

## Troubleshooting

### Plugin doesn't seem to be working

1. **Verify WooCommerce Square is active**: The plugin requires WooCommerce Square to be installed and active
2. **Check image override setting**: Go to WooCommerce → Settings → Square and verify the "Override product images" setting
3. **Check for conflicts**: Deactivate other plugins that might interfere with image handling
4. **Check WordPress debug log**: Enable `WP_DEBUG` and `WP_DEBUG_LOG` to see if any errors are generated

### Square images are still overriding local images

- Ensure the plugin is activated
- Verify that the image override setting is actually disabled in WooCommerce Square settings
- Check that the images are actually coming from Square's hosting (check the image URL)

## Changelog

### 1.0.0
- Initial release
- Prevents Square images from overriding local images when override setting is disabled
- Supports forced synchronization scenarios (`force_update=true`)

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/yourusername/woocommerce-square-prevent-image-override).

## License

This plugin is licensed under the GNU General Public License v2.0 or later.

## Credits

Developed as a workaround for the WooCommerce Square plugin's image override bug.
