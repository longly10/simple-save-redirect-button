<?php
/*
 * @wordpress-plugin
 * Plugin Name:	Simple Save Redirect Button
 * Description:	A new "Save" button which is enhanced post's standard "Save" button. It saves post and execute next action: Next/Previous Post, Next/Previous Page, Posts list page, scroll and highlight last edited post, etc.  Saves a lot of clicks and time if you have a lot of posts.
 * Version:		1.0.0
 * Author:		Yilong Li
 * Author URI:	https://github.com/longly10
 * License:		GPL-2.0+
 * License URI:	http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:	simple-save-redirect-button
 */

// If this file is called directly, abort.
if ( !defined('ABSPATH') )
	die;

if ( !class_exists('Simple_Save_Redirect_Button') ){


/* Main Class */
class Simple_Save_Redirect_Button{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Simple_Save_Redirect_Button_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name 	= 'simple-save-redirect-button';
		$this->version 		= '1.0.0';

		$this->load_dependencies();
		$this->define_admin_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-loader.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-simple-save-redirect-actions.php';

		$this->loader = new Simple_Save_Redirect_Button_Loader();
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$actions = new Simple_Save_Redirect_Button_Actions( $this->get_plugin_name(), $this->get_version() );
		
		$this->loader->add_action( 'admin_enqueue_scripts', $actions, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $actions, 'enqueue_scripts' );

		$this->loader->add_action( 'current_screen', $actions, 'current_screen' );
		$this->loader->add_filter( 'redirect_post_location', $actions, 'redirect_post_location', 10, 2 );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Simple_Save_Redirect_Button_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}

}

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function run_simple_save_redirect_button() {

	$plugin = new Simple_Save_Redirect_Button();
	$plugin->run();

}
run_simple_save_redirect_button();
?>