<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    WHS_Importer
 * @subpackage WHS_Importer/admin
 */

require_once(dirname(__FILE__) . '/SiteImporter.php');
require_once(dirname(__FILE__) . '/TentativeImporter.php');

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WHS_Importer
 * @subpackage WHS_Importer/admin
 * @author     Your Name <email@example.com>
 */
class WHS_Importer_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in WHS_Importer_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The WHS_Importer_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/whs-importer-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in WHS_Importer_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The WHS_Importer_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/whs-importer-admin.js', array( 'jquery' ), $this->version, false );

	}

    /**
     * Add an options page under the Settings submenu
     *
     * @since  1.0.0
     */
    public function add_import_page() {

        $this->plugin_screen_hook_suffix = add_management_page(
            __( 'Import UNESCO World Heritage Sites', 'whs-importer' ),
            __( 'Import WHS', 'whs-importer' ),
            'import',
            $this->plugin_name,
            array( $this, 'display_import_page' )
        );

    }

    /**
     * Render the import page for plugin
     *
     * @since  1.0.0
     */
    public function display_import_page() {
        include_once 'partials/whs-importer-admin-display.php';
    }

    /*
    |--------------------------------------------------------------------------
    | Importers
    |--------------------------------------------------------------------------
    */

    /**
     * Import World Heritage Sites
     *
     * @return void
     */
    public function import_sites() {
        return (new WHS_Importer_SiteImporter())->import();
    }

    /**
     * Import World Heritage Sites Tentative List
     *
     * @return void
     */
    public function import_tentative() {
        return (new WHS_Importer_TentativeImporter())->import();
    }

}
