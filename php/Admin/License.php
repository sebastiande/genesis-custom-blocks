<?php
/**
 * Enable and validate Pro version licensing.
 *
 * @package   Genesis\CustomBlocks
 * @copyright Copyright(c) 2020, Genesis Custom Blocks
 * @license   http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 */

namespace Genesis\CustomBlocks\Admin;

use Genesis\CustomBlocks\ComponentAbstract;

/**
 * Class License
 */
class License extends ComponentAbstract {
	/**
	 * URL of the Genesis Custom Blocks store.
	 *
	 * @var string
	 */
	public $store_url;

	/**
	 * Product slug of the Pro version on the Genesis Custom Blocks store.
	 *
	 * @var string
	 */
	public $product_slug;

	/**
	 * Option name of the license.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'genesis_custom_blocks_license_key';

	/**
	 * The name of the license key transient.
	 *
	 * @var string
	 */
	const TRANSIENT_NAME = 'genesis_custom_blocks_license';

	/**
	 * The transient 'license' value for when the request to validate the Pro license failed.
	 *
	 * This is for when the actual POST request fails,
	 * not for when it returns that the license is invalid.
	 *
	 * @var string
	 */
	const REQUEST_FAILED = 'request_failed';

	/**
	 * Initialise the Pro component.
	 */
	public function init() {
		$this->store_url    = 'https://getblocklab.com';
		$this->product_slug = 'genesis-custom-blocks-pro';
	}

	/**
	 * Register any hooks that this component needs.
	 */
	public function register_hooks() {
		add_filter( 'pre_update_option_' . self::OPTION_NAME, [ $this, 'save_license_key' ] );
	}

	/**
	 * Check that the license key is valid before saving.
	 *
	 * @param string $key The license key that was submitted.
	 *
	 * @return string
	 */
	public function save_license_key( $key ) {
		$this->activate_license( $key );
		$license = get_transient( self::TRANSIENT_NAME );

		if ( ! $this->is_valid() ) {
			$key = '';
			if ( isset( $license['license'] ) && self::REQUEST_FAILED === $license['license'] ) {
				genesis_custom_blocks()->admin->settings->prepare_notice( $this->license_request_failed_message() );
			} else {
				genesis_custom_blocks()->admin->settings->prepare_notice( $this->license_invalid_message() );
			}
		} else {
			genesis_custom_blocks()->admin->settings->prepare_notice( $this->license_success_message() );
		}

		return $key;
	}

	/**
	 * Check if the license if valid.
	 *
	 * @return bool
	 */
	public function is_valid() {
		$license = $this->get_license();

		if ( isset( $license['license'] ) && 'valid' === $license['license'] ) {
			if ( isset( $license['expires'] ) && time() < strtotime( $license['expires'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Retrieve the license data.
	 *
	 * @return mixed
	 */
	public function get_license() {
		$license = get_transient( self::TRANSIENT_NAME );

		if ( ! $license ) {
			$key = get_option( self::OPTION_NAME );
			if ( ! empty( $key ) ) {
				$this->activate_license( $key );
				$license = get_transient( self::TRANSIENT_NAME );
			}
		}

		return $license;
	}

	/**
	 * Try to activate the license.
	 *
	 * @param string $key The license key to activate.
	 */
	public function activate_license( $key ) {
		// Data to send in our API request.
		$api_params = [
			'edd_action' => 'activate_license',
			'license'    => $key,
			'item_name'  => rawurlencode( $this->product_slug ),
			'url'        => home_url(),
		];

		// Call the Genesis Custom Blocks store's API.
		$response = wp_remote_post(
			$this->store_url,
			[
				'timeout'   => 10,
				'sslverify' => true,
				'body'      => $api_params,
			]
		);

		if ( is_wp_error( $response ) ) {
			$license = [ 'license' => self::REQUEST_FAILED ];
		} else {
			$license = json_decode( wp_remote_retrieve_body( $response ), true );
		}

		$expiration = DAY_IN_SECONDS;

		set_transient( self::TRANSIENT_NAME, $license, $expiration );
	}

	/**
	 * Admin notice for correct license details.
	 *
	 * @return string
	 */
	public function license_success_message() {
		$message = __( 'Your Genesis Custom Blocks license was successfully activated!', 'genesis-custom-blocks' );
		return sprintf( '<div class="notice notice-success"><p>%s</p></div>', esc_html( $message ) );
	}

	/**
	 * Admin notice for the license request failing.
	 *
	 * This is for when the validation request fails entirely, like with a 404.
	 * Not for when it returns that the license is invalid.
	 *
	 * @return string
	 */
	public function license_request_failed_message() {
		$message = sprintf(
			/* translators: %s is an HTML link to contact support */
			__( 'There was a problem activating the license, but it may not be invalid. If the problem persists, please %s.', 'genesis-custom-blocks' ),
			sprintf(
				'<a href="%1$s">%2$s</a>',
				'mailto:hi@getblocklab.com?subject=There was a problem activating my Genesis Custom Blocks Pro license',
				esc_html__( 'contact support', 'genesis-custom-blocks' )
			)
		);

		return sprintf( '<div class="notice notice-error"><p>%s</p></div>', wp_kses_post( $message ) );
	}

	/**
	 * Admin notice for incorrect license details.
	 *
	 * @return string
	 */
	public function license_invalid_message() {
		$message = __( 'There was a problem activating your Genesis Custom Blocks license.', 'genesis-custom-blocks' );
		return sprintf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
	}
}