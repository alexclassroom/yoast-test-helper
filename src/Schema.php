<?php
/**
 * Handler for the Schema testing functions.
 *
 * @package Yoast\Test_Helper
 */

namespace Yoast\Test_Helper;

/**
 * Class to manage registering and rendering the admin page in WordPress.
 */
class Schema implements Integration {
	/**
	 * Holds our option instance.
	 *
	 * @var Option
	 */
	private $option;

	/**
	 * Class constructor.
	 *
	 * @param Option $option Our option array.
	 */
	public function __construct( Option $option ) {
		$this->option = $option;
	}

	/**
	 * Adds the required hooks for this class.
	 */
	public function add_hooks() {
		if ( $this->option->get( 'replace_schema_domain' ) === true ) {
			add_filter( 'wpseo_debug_json_data', array( $this, 'replace_domain' ) );
		}

		add_action( 'admin_post_yoast_seo_test_schema', array( $this, 'handle_submit' ) );
	}

	/**
	 * Retrieves the controls.
	 *
	 * @return string The HTML to use to render the controls.
	 */
	public function get_controls() {
		$output = Form_Presenter::create_checkbox(
			'replace_schema_domain', 'Replace .test domain name with example.com in Schema output.',
			$this->option->get( 'replace_schema_domain' )
		);

		return Form_Presenter::get_html( 'Schema', 'yoast_seo_test_schema', $output );
	}

	/**
	 * Handles the form submit.
	 *
	 * @return void
	 */
	public function handle_submit() {
		if ( check_admin_referer( 'yoast_seo_test_schema' ) !== false ) {
			$this->option->set( 'replace_schema_domain', isset( $_POST['replace_schema_domain'] ) );
		}

		wp_safe_redirect( self_admin_url( 'tools.php?page=' . apply_filters( 'yoast_version_control_admin_page', '' ) ) );
	}

	/**
	 * Replaces your .test domain name with example.com in JSON output.
	 *
	 * @param array $data Data to replace the domain in.
	 *
	 * @return array $data Data to replace the domain in.
	 */
	public function replace_domain( $data ) {
		$source = \WPSEO_Utils::get_home_url();
		$target = 'https://example.com';

		if ( $source[ strlen( $source ) - 1 ] === '/' ) {
			$source = substr( $source, 0, -1 );
		}

		return $this->array_value_str_replace( $source, $target, $data );
	}

	/**
	 * Deep replace strings in array.
	 *
	 * @param string $needle      The needle to replace.
	 * @param string $replacement The replacement.
	 * @param array  $array       The array to replace in.
	 *
	 * @return array The array with needle replaced by replacement in strings.
	 */
	private function array_value_str_replace( $needle, $replacement, $array ) {
		if ( is_array( $array ) ) {
			foreach ( $array as $key => $value ) {
				if ( is_array( $value ) ) {
					$array[ $key ] = $this->array_value_str_replace( $needle, $replacement, $array[ $key ] );
				}
				else {
					if ( strpos( $value, $needle ) !== false ) {
						$array[ $key ] = str_replace( $needle, $replacement, $value );
					}
				}
			}
		}

		return $array;
	}
}