<?php
/**
 * Plugin Name: WooCommerce Square - Prevent Image Override
 * Description: Prevents Square images from overriding site images when image override setting is disabled, even when force_update is true.
 * Version: 1.0.0
 * Author: Custom Fix
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Requires Plugins: woocommerce, woocommerce-square
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if a URL is from Square's image hosting.
 * 
 * Square images are hosted on AWS S3 buckets with patterns like:
 * - items-images-production.s3.*.amazonaws.com
 * - Other Square CDN domains may exist
 * 
 * @param string $url The image URL to check.
 * @return bool True if the URL is from Square, false otherwise.
 */
function wc_square_pio_is_square_image_url( $url ) {
	if ( empty( $url ) || ! is_string( $url ) ) {
		return false;
	}
	
	// Parse the URL to get the host
	$parsed_url = parse_url( $url );
	if ( ! isset( $parsed_url['host'] ) ) {
		return false;
	}
	
	$host = $parsed_url['host'];
	
	// Check for Square's AWS S3 image hosting pattern
	// Pattern: items-images-production.s3.*.amazonaws.com
	if ( preg_match( '/items-images-production\.s3\.[^\.]+\.amazonaws\.com/i', $host ) ) {
		return true;
	}
	
	// Check for other potential Square CDN domains (add more patterns as needed)
	// Square may use other CDN domains in the future
	if ( preg_match( '/square.*cdn|square.*image|squareup.*image/i', $host ) ) {
		return true;
	}
	
	return false;
}

/**
 * Intercept HTTP requests for Square images when image override is disabled.
 * 
 * This filter runs before wp_safe_remote_get() downloads the image.
 * We check if:
 * 1. The URL is from Square
 * 2. The image override setting is disabled
 * 3. The product already has an image
 * 
 * If all conditions are met, we return a WP_Error to abort the download.
 * 
 * @param false|array|WP_Error $preempt Whether to preempt the HTTP request.
 * @param array                 $args    HTTP request arguments.
 * @param string               $url     The request URL.
 * @return false|WP_Error False to continue, WP_Error to abort.
 */
function wc_square_pio_prevent_image_override( $preempt, $args, $url ) {
	// Only process if this is a Square image URL
	if ( ! wc_square_pio_is_square_image_url( $url ) ) {
		return false; // Continue with normal request
	}
	
	// Check if WooCommerce Square plugin is active
	if ( ! function_exists( 'wc_square' ) ) {
		return false;
	}
	
	// Check if image override is disabled
	$settings_handler = wc_square()->get_settings_handler();
	if ( ! $settings_handler ) {
		return false;
	}
	
	// Get the image override setting (respects the filter)
	$image_override_enabled = $settings_handler->is_override_product_images_enabled();
	
	// If image override is enabled, allow the download
	if ( $image_override_enabled ) {
		return false;
	}
	
	// Image override is disabled, so prevent Square images from being downloaded
	// This prevents the bug where force_update=true bypasses the setting
	return new WP_Error(
		'square_image_override_disabled',
		__( 'Square image download blocked: Image override setting is disabled.', 'woocommerce-square' )
	);
}

// Hook into pre_http_request to intercept Square image downloads
add_filter( 'pre_http_request', 'wc_square_pio_prevent_image_override', 10, 3 );

/**
 * Alternative approach: Filter media_sideload_image to abort Square image downloads.
 * 
 * This uses a different approach by filtering the allowed extensions for Square images
 * when override is disabled. However, this is less clean than the pre_http_request approach.
 * 
 * Note: This is kept as a backup but the pre_http_request filter above is preferred.
 */
function wc_square_pio_filter_sideload_extensions( $allowed_extensions, $file ) {
	// Only process if this is a Square image URL
	if ( ! wc_square_pio_is_square_image_url( $file ) ) {
		return $allowed_extensions;
	}
	
	// Check if WooCommerce Square plugin is active
	if ( ! function_exists( 'wc_square' ) ) {
		return $allowed_extensions;
	}
	
	// Check if image override is disabled
	$settings_handler = wc_square()->get_settings_handler();
	if ( ! $settings_handler ) {
		return $allowed_extensions;
	}
	
	// Get the image override setting
	$image_override_enabled = $settings_handler->is_override_product_images_enabled();
	
	// If image override is disabled, return empty array to prevent sideload
	// This will cause media_sideload_image to return an error
	if ( ! $image_override_enabled ) {
		return array(); // Empty array will cause "Invalid image URL" error
	}
	
	return $allowed_extensions;
}

// Uncomment the line below to use the alternative approach instead
// add_filter( 'image_sideload_extensions', 'wc_square_pio_filter_sideload_extensions', 10, 2 );

