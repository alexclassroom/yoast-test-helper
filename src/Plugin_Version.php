<?php

namespace Yoast\Version_Controller;

use Yoast\Version_Controller\Plugin\Plugin;

class Plugin_Version {
	/**
	 * @param Plugin $plugin
	 *
	 * @return string
	 */
	public function get_version( Plugin $plugin ) {
		$data = get_option( $plugin->get_version_option_name() );
		if ( isset( $data[ $plugin->get_version_key() ] ) ) {
			return $data[ $plugin->get_version_key() ];
		}

		return '';
	}

	/**
	 * @param Plugin $plugin
	 * @param string $version
	 *
	 * @return bool
	 */
	public function update_version( Plugin $plugin, $version ) {
		$data = get_option( $plugin->get_version_option_name() );

		if ( $data[ $plugin->get_version_key() ] === $version ) {
			return false;
		}

		$data[ $plugin->get_version_key() ] = $version;

		update_option( $plugin->get_version_option_name(), $data );

		return true;
	}
}
