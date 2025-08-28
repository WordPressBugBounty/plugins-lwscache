<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.lws.fr
 * @since      1.0
 *
 * @package
 * @subpackage /admin
 */

/**
 * Description of FastCGI_Purger
 *
 * @package
 * @subpackage /admin
 * @author     rtCamp
 */
class FastCGI_Purger extends Purger {

	/**
	 * Function to purge url.
	 *
	 * @param string $url URL.
	 * @param bool   $feed Weather it is feed or not.
	 */
	public function purge_url( $url, $feed = true ) {

		// Purge the specific URL that was passed to this method
		wp_remote_request($url, array('method' => 'PURGE'));

		// If the URL is using HTTPS, also try HTTP version and vice versa
		$alt_scheme_url = (strpos($url, 'https://') === 0)
			? str_replace('https://', 'http://', $url)
			: str_replace('http://', 'https://', $url);

		wp_remote_request($alt_scheme_url, array('method' => 'PURGE'));
	}

	/**
	 * Function to custom purge urls.
	 */
	public function custom_purge_urls() {

		global $lws_cache_admin;

		$parse = wp_parse_url( home_url() );

		$purge_urls = isset( $lws_cache_admin->options['purge_url'] ) && ! empty( $lws_cache_admin->options['purge_url'] ) ?
			explode( "\r\n", $lws_cache_admin->options['purge_url'] ) : array();

		/**
		 * Allow plugins/themes to modify/extend urls.
		 *
		 * @param array $purge_urls URLs which needs to be purged.
		 * @param bool  $wildcard   If wildcard in url is allowed or not. default false.
		 */
		$purge_urls = apply_filters( 'rt_lws_cache_purge_urls', $purge_urls, false );

		switch ( $lws_cache_admin->options['purge_method'] ) {

			case 'unlink_files':
				$_url_purge_base = $parse['scheme'] . '://' . $parse['host'];

				if ( is_array( $purge_urls ) && ! empty( $purge_urls ) ) {

					foreach ( $purge_urls as $purge_url ) {

						$purge_url = trim( $purge_url );

						if ( strpos( $purge_url, '*' ) === false ) {

							$purge_url = $_url_purge_base . $purge_url;
							$this->log( '- Purging URL | ' . $purge_url );
							$this->delete_cache_file_for( $purge_url );

						}
					}
				}
				break;

			case 'get_request':
				// Go to default case.
			default:
				$_url_purge_base = $this->purge_base_url();

				if ( is_array( $purge_urls ) && ! empty( $purge_urls ) ) {

					foreach ( $purge_urls as $purge_url ) {

						$purge_url = trim( $purge_url );

						if ( strpos( $purge_url, '*' ) === false ) {

							$purge_url = $_url_purge_base . $purge_url;
							$this->log( '- Purging URL | ' . $purge_url );
							$this->do_remote_get( $purge_url );

						}
					}
				}
				break;

		}

	}

	/**
	 * Purge everything.
	 */
	public function purge_all() {

		wp_remote_request(get_site_url(null, '', 'https') . "/*", array('method' => 'PURGE'));
		wp_remote_request(get_site_url(null, '', 'http') . "/*", array('method' => 'PURGE'));

		wp_remote_request(get_site_url(null, '', 'https') . "/*", array('method' => 'FULLPURGE'));
		wp_remote_request(get_site_url(null, '', 'http') . "/*", array('method' => 'FULLPURGE'));
	}

	/**
	 * Constructs the base url to call when purging using the "get_request" method.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	private function purge_base_url() {

		$parse = wp_parse_url( home_url() );

		/**
		 * Filter to change purge suffix for FastCGI cache.
		 *
		 * @param string $suffix Purge suffix. Default is purge.
		 *
		 * @since 1.0
		 */
		$path = apply_filters( 'rt_lws_cache_fastcgi_purge_suffix', '/--api/cache-purge' );

		// Prevent users from inserting a trailing '/' that could break the url purging.
		$path = trim( $path, '/' );

		$purge_url_base = $parse['scheme'] . '://' . $parse['host'] . '/' . $path;

		/**
		 * Filter to change purge URL base for FastCGI cache.
		 *
		 * @param string $purge_url_base Purge URL base.
		 *
		 * @since 1.0
		 */
		$purge_url_base = apply_filters( 'rt_lws_cache_fastcgi_purge_url_base', $purge_url_base );

		// Prevent users from inserting a trailing '/' that could break the url purging.
		return untrailingslashit( $purge_url_base );

	}

}
