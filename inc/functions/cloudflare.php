<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * Get a CloudFlare\Api instance
 *
 * @since 2.9
 * @author Remy Perona
 *
 * @return mixed bool|object CloudFlare\Api instance if crendentials are set, false otherwise
 */
function get_rocket_cloudflare_api_instance() {
	$cf_email   = get_rocket_option( 'cloudflare_email', null );
	$cf_api_key = ( defined( 'WP_ROCKET_CF_API_KEY' ) ) ? WP_ROCKET_CF_API_KEY : get_rocket_option( 'cloudflare_api_key', null );

	if ( isset( $cf_email, $cf_api_key ) ) {
    	$cf_instance = new Cloudflare\Api( $cf_email, $cf_api_key );

		return $cf_instance;
	}

    return false;
}

/**
 * Get a CloudFlare\Api instance & the zone_id corresponding to the domain
 *
 * @since 2.8.16 Update to CloudFlare API v4
 * @since 2.5
 *
 * @return mixed bool|object CloudFlare instance & zone_id if instance is set, false otherwise
 */
function get_rocket_cloudflare_instance() {
	if ( false !== $cf_api_instance = get_rocket_cloudflare_api_instance() ) {
    	$cf_instance = ( object ) [ 'auth' => $cf_api_instance, 'zone_id' => get_rocket_option( 'cloudflare_domain' ) ];

		return $cf_instance;
	}

	return false;
}

/**
 * Test the connection with CloudFlare
 *
 * @since 2.9
 * @author Remy Perona
 *
 * @return bool True if connection is successful, false otherwise
 */
 function rocket_cloudflare_valid_auth() {
    if ( false !== $cf_api_instance = get_rocket_cloudflare_api_instance() ) {
        $cf_zone_instance = new CloudFlare\Zone( $cf_api_instance );
    	$cf_zones         = $cf_zone_instance->zones();

        if ( $cf_zones->success === true ) {
            return true;
        }
    }

    return false;
 }

/**
 * Get Zones linked to a CloudFlare account
 *
 * @since 2.9
 * @author Remy Perona
 *
 * @return Array List of zones or default no domain
 */
function get_rocket_cloudflare_zones() {
	if ( false !== $cf_api_instance = get_rocket_cloudflare_api_instance() ) {
    	$cf_zone_instance        = new CloudFlare\Zone( $cf_api_instance );
    	$cf_zones                = $cf_zone_instance->zones();
    	$cf_zones_list           = $cf_zones->result;
    	$domains = array();

        if ( ! ( bool ) $cf_zones_list ) {
            $domains[] = __( 'No domain available in your CloudFlare account', 'rocket' );

            return $domains;
        }

        foreach( $cf_zones_list as $cf_zone ) {
            $domains[ $cf_zone->id ] = $cf_zone->name;
        }

        return $domains;
	}
}

/**
 * Returns the main instance of CloudFlare API to prevent the need to use globals.
 */
$GLOBALS['rocket_cloudflare'] = get_rocket_cloudflare_instance();

/**
 * Get all the current CloudFlare settings for a given domain.
 *
 * @since 2.8.16 Update to CloudFlare API v4
 * @since 2.5
 *
 * @return Array 
 */
function get_rocket_cloudflare_settings() {
	if( ! is_object( $GLOBALS['rocket_cloudflare'] ) ) {
		return false;
	}

	$cf_settings_instance = new CloudFlare\Zone\Settings( $GLOBALS['rocket_cloudflare']->auth );
	$cf_settings          = $cf_settings_instance->settings( $GLOBALS['rocket_cloudflare']->zone_id );
	$cf_minify            = $cf_settings->result[16]->value;
	$cf_minify_value      = 'on';

	if ( $cf_minify->js === 'off' || $cf_minify->css === 'off' || $cf_minify->html === 'off' ) {
    	$cf_minify_value = 'off';
	}

	$cf_settings_array  = array(
    	'cache_level'       => $cf_settings->result[5]->value,
    	'minify'            => $cf_minify_value,
    	'rocket_loader'     => $cf_settings->result[25]->value,
    	'browser_cache_ttl' => $cf_settings->result[3]->value
	);

	return $cf_settings_array;
}

/**
 * Set the CloudFlare Development mode.
 *
 * @since 2.8.16 Update to CloudFlare API v4
 * @since 2.5
 *
 * @return void
 */
function set_rocket_cloudflare_devmode( $mode ) {
	if( ! is_object( $GLOBALS['rocket_cloudflare'] ) ) {
		return false;
	}

    if ( ( int ) $mode === 0 ) {
        $value = 'off';
    } else if ( ( int ) $mode === 1 ) {
        $value = 'on';
    }

	$cf_settings = new CloudFlare\Zone\Settings( $GLOBALS['rocket_cloudflare']->auth );
	$cf_settings->change_development_mode( $GLOBALS['rocket_cloudflare']->zone_id, $value );
}

/**
 * Set the CloudFlare Caching level.
 *
 * @since 2.8.16 Update to CloudFlare API v4
 * @since 2.5
 *
 * @return void
 */
function set_rocket_cloudflare_cache_level( $mode ) {
	if( ! is_object( $GLOBALS['rocket_cloudflare'] ) ) {
		return false;
	}

	$cf_settings = new CloudFlare\Zone\Settings( $GLOBALS['rocket_cloudflare']->auth );
	$cf_settings->change_cache_level( $GLOBALS['rocket_cloudflare']->zone_id, $mode );
}

/**
 * Set the CloudFlare Minification.
 *
 * @since 2.8.16 Update to CloudFlare API v4
 * @since 2.5
 *
 * @return void
 */
function set_rocket_cloudflare_minify( $mode ) {
	if( ! is_object( $GLOBALS['rocket_cloudflare'] ) ) {
		return false;
	}

    $cf_minify_settings = array(
        'css'  => $mode,
        'html' => $mode,
        'js'   => $mode
    );

	$cf_settings = new CloudFlare\Zone\Settings( $GLOBALS['rocket_cloudflare']->auth );
	$cf_settings->change_minify( $GLOBALS['rocket_cloudflare']->zone_id, $cf_minify_settings );
}

/**
 * Set the CloudFlare Rocket Loader.
 *
 * @since 2.8.16 Update to CloudFlare API v4
 * @since 2.5
 *
 * @return void
 */
function set_rocket_cloudflare_rocket_loader( $mode ) {
	if( ! is_object( $GLOBALS['rocket_cloudflare'] ) ) {
		return false;
	}

	$cf_settings = new CloudFlare\Zone\Settings( $GLOBALS['rocket_cloudflare']->auth );
	$cf_settings->change_rocket_loader( $GLOBALS['rocket_cloudflare']->zone_id, $mode );
}

/**
 * Set the Browser Cache TTL in CloudFlare.
 *
 * @since 2.8.16
 *
 * @return void
 */
function set_rocket_cloudflare_browser_cache_ttl( $mode ) {
	if( ! is_object( $GLOBALS['rocket_cloudflare'] ) ) {
		return false;
	}

	$cf_settings = new CloudFlare\Zone\Settings( $GLOBALS['rocket_cloudflare']->auth );
	$cf_settings->change_browser_cache_ttl( $GLOBALS['rocket_cloudflare']->zone_id, $mode );
}

/**
 * Purge CloudFlare cache.
 *
 * @since 2.8.16 Update to CloudFlare API v4
 * @since 2.5
 *
 * @return void
 */
function rocket_purge_cloudflare() {
	if( ! is_object( $GLOBALS['rocket_cloudflare'] ) ) {
		return false;
	}

	$cf_cache = new CloudFlare\Zone\Cache( $GLOBALS['rocket_cloudflare']->auth );
	$cf_cache->purge( $GLOBALS['rocket_cloudflare']->zone_id, true );
}

/**
 * Get CloudFlare IPs.
 *
 * @since 2.8.16
 *
 * @return Object Result of API request
 */
function rocket_get_cloudflare_ips() {
    $cf_instance = get_rocket_cloudflare_api_instance();
    if( ! is_object( $cf_instance ) ) {
		return false;
	}

    $cf_ips_instance = new CloudFlare\IPs( $cf_instance );
    return $cf_ips_instance->ips();
}