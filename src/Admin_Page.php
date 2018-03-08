<?php

namespace Yoast\Version_Controller;

use Yoast\Version_Controller\Plugin\Plugin;

class Admin_Page {
	/** @var Plugin[] Plugins */
	protected $plugins;

	/** @var Plugin_Options */
	protected $plugin_options;

	/** @var Plugin_Version */
	protected $plugin_version;

	/** @var Plugin_Features */
	protected $plugin_features;

	/**
	 * Admin_Page constructor.
	 *
	 * @param                 $plugins
	 * @param Plugin_Options  $plugin_options
	 * @param Plugin_Version  $plugin_version
	 * @param Plugin_Features $plugin_features
	 */
	public function __construct(
		$plugins,
		Plugin_Options $plugin_options,
		Plugin_Version $plugin_version,
		Plugin_Features $plugin_features
	) {
		$this->plugins         = $plugins;
		$this->plugin_options  = $plugin_options;
		$this->plugin_version  = $plugin_version;
		$this->plugin_features = $plugin_features;
	}

	/**
	 * @return string
	 */
	public function get_admin_page() {
		return 'yoast-version-controller';
	}

	/**
	 *
	 */
	public function add_hooks() {
		add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
		add_action( 'admin_post_yoast_version_control', [ $this, 'handle_submit' ] );

		// Expose the admin page we are running on.
		add_filter( 'wpseo_version_control_admin_page', [ $this, 'get_admin_page' ] );
	}

	/**
	 *
	 */
	public function register_admin_menu() {
		add_menu_page(
			'Yoast Dev',
			'SEO VC',
			'manage_options',
			sanitize_key( $this->get_admin_page() ),
			[ $this, 'show_admin_page' ],
			$this->get_icon(),
			999
		);
	}

	/**
	 *
	 */
	public function show_admin_page() {
		echo '<h1>Yoast Version Controller</h1>';

		echo '<form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="POST">';
		echo '<input type="hidden" name="action" value="yoast_version_control">';

		echo '<table>';
		echo '<thead><tr>' .
		     '<th style="text-align:left;">Plugin</th>' .
		     '<th style="text-align:left;">DB Version</th>' .
		     '<th style="text-align:left;">Real</th>' .
		     '<th style="text-align:left;">Saved options</th>' .
		     '</tr></thead>';

		foreach ( $this->plugins as $plugin ) {
			echo $this->get_plugin_option( $plugin );
		}
		echo '</table>';

		echo '<button class="button button-primary">Save</button>';
		echo '</form>';

		// Show feature resets.
		echo $this->plugin_features->get_controls();
	}

	/**
	 * @param Plugin $plugin
	 *
	 * @return string
	 */
	protected function get_plugin_option( Plugin $plugin ) {
		return sprintf(
			'<tr><td>%s:</td><td><input type="text" name="%s" value="%s" maxlength="7" size="8"></td><td>(%s)</td><td>%s</td></tr>',
			esc_html( $plugin->get_name() ),
			esc_attr( $plugin->get_identifier() ),
			esc_attr( $this->plugin_version->get_version( $plugin ) ),
			esc_html( $plugin->get_version_constant() ),
			$this->get_option_history_select( $plugin )
		);
	}

	/**
	 * @param Plugin $plugin
	 *
	 * @return string
	 */
	protected function get_option_history_select( Plugin $plugin ) {
		$history    = $this->plugin_options->get_saved_options( $plugin );
		$timestamps = array_reverse( array_keys( $history ) );

		return sprintf(
			'<select name="%s"><option name=""></option>%s</select>',
			esc_attr( $plugin->get_identifier() . '-history' ),
			implode( '', array_map( function ( $item ) {
				return sprintf( '<option name="%s">%s</option>', esc_attr( $item ),
					esc_html( date( 'Y-m-d H:i:s', $item ) ) );
			}, $timestamps ) )
		);
	}

	/**
	 *
	 */
	public function handle_submit() {
		if ( ! $this->load_history() ) {
			foreach ( $this->plugins as $plugin ) {
				$this->update_plugin_version( $plugin, $_POST[ $plugin->get_identifier() ] );
			}
		}

		wp_redirect( self_admin_url( '?page=' . $this->get_admin_page() ) );
	}

	/**
	 * @return bool
	 */
	protected function load_history() {
		foreach ( $this->plugins as $plugin ) {
			// if -history is set, load the history item, otherwise save.
			if ( ! empty( $_POST[ $plugin->get_identifier() . '-history' ] ) ) {
				$this->plugin_options->restore_options( $plugin, $_POST[ $plugin->get_identifier() . '-history' ] );

				return true;
			}
		}

		return false;
	}

	/**
	 * @param Plugin $plugin
	 * @param        $version
	 */
	protected function update_plugin_version( Plugin $plugin, $version ) {
		$this->plugin_version->update_version( $plugin, $version );
		$this->plugin_options->save_options( $plugin );
	}

	/**
	 * @return string
	 */
	protected function get_icon() {
		if ( class_exists( '\WPSEO_Utils' ) ) {
			return \WPSEO_Utils::get_icon_svg( true );
		}

		$svg = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xml:space="preserve" width="100%" height="100%" style="fill:#82878c" viewBox="0 0 512 512"><g><g><g><g><path d="M203.6,395c6.8-17.4,6.8-36.6,0-54l-79.4-204h70.9l47.7,149.4l74.8-207.6H116.4c-41.8,0-76,34.2-76,76V357c0,41.8,34.2,76,76,76H173C189,424.1,197.6,410.3,203.6,395z"/></g><g><path d="M471.6,154.8c0-41.8-34.2-76-76-76h-3L285.7,365c-9.6,26.7-19.4,49.3-30.3,68h216.2V154.8z"/></g></g><path stroke-width="2.974" stroke-miterlimit="10" d="M338,1.3l-93.3,259.1l-42.1-131.9h-89.1l83.8,215.2c6,15.5,6,32.5,0,48c-7.4,19-19,37.3-53,41.9l-7.2,1v76h8.3c81.7,0,118.9-57.2,149.6-142.9L431.6,1.3H338z M279.4,362c-32.9,92-67.6,128.7-125.7,131.8v-45c37.5-7.5,51.3-31,59.1-51.1c7.5-19.3,7.5-40.7,0-60l-75-192.7h52.8l53.3,166.8l105.9-294h58.1L279.4,362z"/></g></g></svg>';

		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}
}
