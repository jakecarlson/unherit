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

use Illuminate\Container\Container as IoC;
use Orchestra\Parser\Xml\Document as XmlDocument;
use Orchestra\Parser\Xml\Reader as XmlReader;
use Http\Adapter\Guzzle6\Client as HttpClient;
use Geocoder\Provider\GoogleMaps\GoogleMaps as GeocodeProvider;
use Geocoder\StatefulGeocoder;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Geocoder\Exception\InvalidArgument;
use PHPHtmlParser\Dom as DomParser;

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
     * @var string $feed_url
     */
//	private $feed_url = 'http://whc.unesco.org/en/list/xml';
    private $feed_url = '../whs.xml';

    /**
     * @var int
     */
    private $batch_import_limit = 15;

    /**
     * @var string $continents_url
     */
    private $continents_url = '../continents.json';

    /**
     * @var array $continents
     */
	private $continents = [];

    /**
     * @var array $countries
     */
	private $countries = [];

    /**
     * @var GeocodeProvider $geocoder
     */
	private $geocoder = null;

    /**
     * @var string $geocode_api_key
     */
	private $geocode_api_key = 'AIzaSyCRzMJnv00ygMzsuawaVoYte48NxsjiS8I';

    /**
     * @var string
     */
	private $site_content_placeholder = "I haven't been here (yet).";

    /**
     * @var string
     */
	private $gallery_suffix = '/gallery';

    /**
     * @var string
     */
	private $gallery_img_prefix = 'http://whc.unesco.org/document/';

    /**
     * @var string
     */
	private $site_img_suffix = ' Image';

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

		$this->feed_url = dirname(__FILE__) . '/' . $this->feed_url;
		$this->continents_url = dirname(__FILE__) . '/' . $this->continents_url;

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
            $this->plugin_name . '-import',
            array( $this, 'display_import_page' )
        );

    }

    /**
     * Render the import page for plugin
     *
     * @since  1.0.0
     */
    public function display_import_page() {
        include_once 'partials/whs-importer-import-display.php';
    }

    /**
     * Add an options page under the Settings submenu
     *
     * @since  1.0.0
     */
    public function add_maps_page() {

        $this->plugin_screen_hook_suffix = add_management_page(
            __( 'Calculate WHS Map Windows', 'whs-importer' ),
            __( 'Calculate WHS Maps', 'whs-importer' ),
            'import',
            $this->plugin_name . '-maps',
            array( $this, 'display_maps_page' )
        );

    }

    /**
     * Render the import page for plugin
     *
     * @since  1.0.0
     */
    public function display_maps_page() {
        include_once 'partials/whs-importer-maps-display.php';
    }

    /*
    |--------------------------------------------------------------------------
    | World Heritage Sites
    |--------------------------------------------------------------------------
    */

    /**
     * Import World Heritage Sites
     *
     * @return void
     */
    public function import() {

        $this->header("Preparing Geocoder");

        // Prepare Geocoder
        $this->set_geocoder();

        // Get continents
        $this->set_continents_map();
        $this->set_countries_map();
        $this->set_categories_map();

        $this->header("Importing XML Feed");

        // Load source XML
        $parsed = $this->load_src_xml();

        // Loop through sites and import to database
        $num_imported = 0;
        $num_processed = 1;
        foreach ($parsed['sites'] as $item) {

            // If the threshold of imports have been processed, kill the loop and abort
            if ($num_imported >= $this->batch_import_limit) {
                $this->error("The import limit of <strong>{$this->batch_import_limit}</strong> has been met, aborting. Please import again to continue processing.");
                break;
            }

            $this->partial("{$num_processed}. [{$item['id_number']}]: {$item['site']} ... ");

            $site = $this->get_site_by_whs_id($item['id_number']);

            // If the site is already in the system, check if this is a more recent revision
            $parsed_revision = $this->get_parsed_revision($item);
            if ($site) {

                $current_revision = $this->get_whs_revision($site->ID);
                if ($parsed_revision > $current_revision) {
                    $destination_id = $this->get_country_destination_id($item);
                    $this->update_existing_site($site->ID, $item, $destination_id);
                    $this->success("REVISION UPDATED (v{$current_revision} --&gt; v{$parsed_revision})");
                    ++$num_imported;
                } else {
                    $this->warning("CURRENT REVISION (v{$parsed_revision})");
                }

            // Otherwise prepare to insert new site
            } else {

                // Determine the primary country in which the site is located
                $destination_id = $this->get_country_destination_id($item);

                // Insert the new site
                $this->insert_new_site($item, $destination_id);

                // If no country was matched, output a warning
                if ($destination_id === 0) {
                    $this->error("INSERTED W/O COUNTRY (v{$parsed_revision})");

                // Otherwise output success
                } else {
                    $this->success("INSERTED (v{$parsed_revision})");
                }

                ++$num_imported;

            }

            ++$num_processed;

        }

    }

    /**
     * Get the feed URL
     *
     * @return string
     */
    public function get_feed_url() {
        return $this->feed_url;
    }

    /**
     * Load the source XML file
     *
     * @return array
     */
    private function load_src_xml() {
        $app = new IoC;
        $document = new XmlDocument($app);
        $reader = new XmlReader($document);
        $this->partial("Fetching WHS XML feed ... ");
        $xml = $reader->load($this->get_feed_url());
        $this->success("OK");
        $this->partial("Parsing source XML ... ");
        $parsed = $xml->parse([
            'sites' => ['uses'=>'row[site,short_description,long_description,category,danger,date_inscribed,historical_description,http_url,image_url,justification,id_number,unique_number,latitude,longitude,location,region,states,transboundary,extension,criteria_txt,iso_code,secondary_dates,revision]'],
        ]);
        $num_items = count($parsed['sites']);
        $this->success("OK, FOUND {$num_items} SITES");
        return $parsed;
    }

    /**
     * Downloads an image from the specified URL and attaches it to a post as a post thumbnail.
     *
     * @param int    $post_id The post ID the post thumbnail is to be associated with.
     * @param string $url    The URL of the image to download.
     * @param string $desc    Optional. Description of the image.
     * @return string|WP_Error Attachment ID, WP_Error object otherwise.
     */
    function sideload_featured_image($post_id, $url, $desc) {

        $file_array = [];
        $file_array['name'] = strtolower($desc) . '.jpg';

        // Download file to temp location.
        $file_array['tmp_name'] = download_url($url);

        // If error storing temporarily, return the error.
        if (is_wp_error($file_array['tmp_name'])) {
            return $file_array['tmp_name'];
        }

        // Do the validation and storage stuff.
        $id = media_handle_sideload($file_array, $post_id, $desc . $this->site_img_suffix);

        // If error storing permanently, unlink.
        if (is_wp_error($id)) {
            @unlink( $file_array['tmp_name'] );
            return $id;
        }
        return set_post_thumbnail($post_id, $id);

    }

    /**
     * Find a site that matches the parsed item using the 'whs_id' unique site ID meta key
     *
     * @param int $id
     * @return WP_Post
     */
    private function get_site_by_whs_id($id) {
        $sites = get_posts([
            'posts_per_page'    =>  1,
            'post_type'         =>  'travel-directory',
            'meta_key'          =>  'whs_id',
            'meta_value'        =>  strval($id),
        ]);
        if (count($sites) > 0) {
            return $sites[0];
        } else {
            return false;
        }
    }

    /**
     * Get WHS meta data.
     *
     * @param $site_id
     * @param $key
     * @return mixed
     */
    private function get_whs_meta($site_id, $key) {
        return get_post_meta($site_id, 'whs_' . $key, true);
    }

    /**
     * Get the site's revision number in the system
     *
     * @param $site_id
     * @return int
     */
    private function get_whs_revision($site_id) {
        return intval($this->get_whs_meta($site_id, 'revision'));
    }

    /**
     * Get the XML parsed revision
     *
     * @param $item
     * @return int
     */
    private function get_parsed_revision($item) {
        return intval($item['revision']);
    }

    /**
     * Insert a new site
     *
     * @param $item
     * @param $destination_id
     * @return int|WP_Error
     */
    private function insert_new_site($item, $destination_id) {

        $site_id = wp_insert_post([
            'post_title'    =>  wp_strip_all_tags($item['site']),
            'post_content'  =>  $this->get_site_content_placeholder(),
            'post_type'     =>  'travel-directory',
            'post_status'   =>  'publish',
            'meta_input'    =>  $this->generate_site_meta($item, $destination_id, true),
        ]);

        // If the new site was successfully inserted ...
        if ($site_id) {

            // Add categories
            $this->add_site_categories($site_id, $item);

            // Sideload the featured image
            $this->sideload_featured_image($site_id, $this->get_site_img_url($item), $item['site']);

        }

        return $site_id;

    }

    /**
     * Update an existing site
     *
     * @param int $site_id
     * @param array $item
     * @param int $destination_id
     * @return int|WP_Error
     */
    private function update_existing_site($site_id, $item, $destination_id) {
        $meta_keys = $this->generate_site_meta($item, $destination_id);
        foreach ($meta_keys as $key=>$val) {
            update_post_meta($site_id, $key, $val);
        }
    }

    /**
     * Generate site meta
     *
     * @param array $item
     * @param int $destination_id
     * @param bool $new
     * @return array
     */
    private function generate_site_meta($item, $destination_id, $new = false) {
        $meta = $this->generate_whs_site_meta($item, $destination_id);
        if ($new) {
            $meta = array_merge($meta, $this->generate_directory_site_meta($item));
        }
        return $meta;
    }

    /**
     * Generate WHS meta
     *
     * @param array $item
     * @param int $destination_id
     * @return array
     */
    private function generate_whs_site_meta($item, $destination_id) {
        return [
            'whs_id'                =>  $item['id_number'],
            'whs_name'              =>  $item['site'],
            'whs_category'          =>  $item['category'],
            'whs_endangered'        =>  ($item['danger'] != '0'),
            'whs_unique_id'         =>  $item['unique_number'],
            'whs_url'               =>  $item['http_url'],
            'whs_image'             =>  $item['image_url'],
            'whs_summary'           =>  $item['short_description'],
            'whs_description'       =>  $item['long_description'],
            'whs_justification'     =>  $item['justification'],
            'whs_historical'        =>  $item['historical_description'],
            'whs_year_inscribed'    =>  $item['date_inscribed'],
            'whs_location'          =>  $item['location'],
            'whs_region'            =>  $item['region'],
            'whs_transboundary'     =>  ($item['transboundary'] != '0'),
            'whs_extension'         =>  ($item['extension'] != '0'),
            'whs_criteria_txt'      =>  $item['criteria_txt'],
            'whs_iso_code'          =>  $item['iso_code'],
            'whs_secondary_dates'   =>  $item['secondary_dates'],
            'whs_revision'          =>  $item['revision'],
            'guide_lists_intro'     =>  wp_strip_all_tags($item['short_description']),
            'destination_parent_id' =>  $destination_id,
        ];
    }

    /**
     * Generate directory meta
     *
     * @param array $item
     * @return array
     */
    private function generate_directory_site_meta($item) {
        return [
            'guide_lists_details'   =>  json_encode([
                'address'       =>  '',
                'google_map'    =>  [
                    'address'   =>  '',
                    'longitude' =>  $item['longitude'],
                    'latitude'  =>  $item['latitude'],
                    'zoom'      =>  8,
                ],
                'contact_name_main'     =>  '',
                'contact_value_main'    =>  '',
                'contact_last_number'   =>  7,
                'contacts'              =>  [],
                'other_name_main'       =>  '',
                'other_value_main'      =>  '',
                'other_last_number'     =>  8,
                'other'                 =>  [],
            ]),
            'guide_list_rating'     =>  json_encode(['rating_types_star'=>null]),
        ];
    }

    /**
     * Get the site content placeholder
     *
     * @return string
     */
    private function get_site_content_placeholder() {
        return $this->site_content_placeholder;
    }

    /**
     * Get the WHS image
     *
     * @param $item
     * @return string
     */
    private function get_site_img_url($item) {
        $dom = new DomParser;
        $dom->loadFromUrl($this->get_site_gallery_url($item));
        $images = $dom->find('a[property="image"]');
        if (count($images) > 0) {
            parse_str(
                parse_url($images[0]->getAttribute('href'), PHP_URL_QUERY),
                $params
            );
            $img_url = $this->get_site_gallery_img_url($params['id']);
        } else {
            $img_url = $item['image_url'];
        }
        return $img_url;
    }

    /**
     * Get a site gallery URL
     *
     * @param array $item
     * @return string
     */
    private function get_site_gallery_url($item) {
        return $item['http_url'] . $this->gallery_suffix;
    }

    /**
     * Get the URL of a gallery image
     *
     * @param int $id
     * @return string
     */
    private function get_site_gallery_img_url($id) {
        return $this->gallery_img_prefix . $id;
    }

    /*
    |--------------------------------------------------------------------------
    | Taxonomy
    |--------------------------------------------------------------------------
    */

    /**
     * Set the categories map
     *
     * @return void
     */
    private function set_categories_map() {
        $this->partial("Generating categories map ... ");
        $categories = get_terms([
            'taxonomy'  =>  'travel-dir-category',
            'hide_empty'=>  false,
        ]);
        foreach ($categories as $category) {
            $this->categories[$category->term_id] = $category->name;
        }
        $this->success("OK");
    }

    /**
     * Get categories map.
     *
     * @return array
     */
    private function get_categories_map() {
        return $this->categories;
    }

    /**
     * Add categories to the site
     *
     * @param $site_id
     * @param $item
     * @return bool
     */
    private function add_site_categories($site_id, $item) {
        $terms = [];
        if ($item['category'] == 'Mixed') {
            $terms[] = $this->get_category_id('Mixed');
        }
        if (in_array($item['category'], ['Natural','Mixed'])) {
            $terms[] = $this->get_category_id('Natural');
        }
        if (in_array($item['category'], ['Cultural','Mixed'])) {
            $terms[] = $this->get_category_id('Cultural');
        }
        if (($item['danger'] != '0') && !empty($item['danger'])) {
            $terms[] = $this->get_category_id('Endangered');
        }
        if (($item['transboundary'] != '0') && !empty($item['transboundary'])) {
            $terms[] = $this->get_category_id('Transboundary');
        }
        return wp_set_post_terms($site_id, $terms, 'travel-dir-category');
    }

    /**
     * Get the ID of the passed category name
     *
     * @param $category
     * @return mixed
     */
    private function get_category_id($category) {
        return array_search($category, $this->get_categories_map());
    }

    /*
    |--------------------------------------------------------------------------
    | Geographic
    |--------------------------------------------------------------------------
    */

    /**
     * Set the geocoder object.
     *
     * @return void
     */
    private function set_geocoder() {
        $this->partial("Initializing geocoder ... ");
        $client = new HttpClient();
        $provider = new GeocodeProvider($client, null, $this->get_geocode_api_key());
        $this->geocoder = new StatefulGeocoder($provider, 'en');
        $this->success("OK");
    }

    /**
     * Get continents JSON URL.
     *
     * @return string
     */
    private function get_geocode_api_key() {
        return $this->geocode_api_key;
    }

    /**
     * Set the continents map
     *
     * @return void
     */
    private function set_continents_map() {
        $this->partial("Generating continent map ... ");
        $destinations = get_posts([
            'post_type'     =>  'destination',
            'post_parent'   =>  0,
            'posts_per_page'=>  -1,
            'orderby'       =>  'title',
            'order'         =>  'ASC',
        ]);
        foreach ($destinations as $destination) {
            $this->continents[$destination->ID] = $destination->post_title;
        }
        $this->success("OK");
    }

    /**
     * Get continents map.
     *
     * @return array
     */
    private function get_continents_map() {
        return $this->continents;
    }

    /**
     * Get continents JSON URL.
     *
     * @return string
     */
    private function get_continents_url() {
        return $this->continents_url;
    }

    /**
     * Find an existing country post or create it if it doesn't exist
     *
     * @param string $name
     * @param string $code
     * @return integer
     */
    private function find_or_create_country($name, $code) {

        // Find existing country
        $countries = get_posts([
            'posts_per_page'    =>  1,
            'post_type'         =>  'destination',
            's'                 =>  $name,
        ]);

        // If it's already in the DB, return that
        if (count($countries) > 0) {
            return $countries[0]->ID;

            // Otherwise we'll need to create it ...
        } else {

            // Create the country and return
            return $this->insert_new_country(
                $name,
                $this->get_continent_id($code),
                $this->get_country_coordinates($name)
            );

        }
    }

    /**
     * Set countries continents map.
     *
     * @return void
     */
    private function set_countries_map() {
        $this->partial("Importing country-to-continent map ...");
        $this->countries = json_decode(file_get_contents($this->get_continents_url()), true);
        $this->success("OK");
    }

    /**
     * Get continent name from country continent map.
     *
     * @param string $code
     * @return string
     */
    private function get_continent_from_country_code($code) {
        return $this->countries[$code];
    }

    /**
     * Figure out which continent the country belongs to
     *
     * @param $code
     * @return int
     */
    private function get_continent_id($code) {
        return array_search(
            $this->get_continent_from_country_code($code),
            $this->get_continents_map()
        );
    }

    /**
     * Get a country's coordinates
     *
     * @param $name
     * @return \Geocoder\Model\Coordinates|null
     */
    private function get_country_coordinates($name) {
        $location = $this->geocoder->geocodeQuery(GeocodeQuery::create($name))->first();
        return $location->getCoordinates();
    }

    /**
     * Get the destination ID of the item's country
     *
     * @param array $item
     * @return int
     */
    private function get_country_destination_id($item) {
        try {
            $locations = $this->geocoder->reverseQuery(ReverseQuery::fromCoordinates($item['latitude'], $item['longitude']));
        } catch (InvalidArgument $e) {}
        if (
            (!isset($locations) || ($locations->count() == 0)) &&
            !empty($item['states'])
        ) {
            $countries = explode(',', $item['states']);
            $locations = $this->geocoder->geocodeQuery(GeocodeQuery::create($countries[0]));
        }
        if (
            (!isset($locations) || ($locations->count() == 0)) &&
            !empty($item['iso_code'])
        ) {
            $codes = explode(',', $item['iso_code']);
            $locations = $this->geocoder->geocodeQuery(GeocodeQuery::create($codes[0]));
        }
        $country = $locations->first()->getCountry();
        $destination_id = $this->find_or_create_country($country->getName(), $country->getCode());
        return $destination_id;
    }

    /**
     * Insert a new country destination
     *
     * @param $name
     * @param $continent_id
     * @param $coordinates
     * @return int|WP_Error
     */
    private function insert_new_country($name, $continent_id, $coordinates) {
        return wp_insert_post([
            'post_title'    =>  $name,
            'post_content'  =>  $name,
            'post_type'     =>  'destination',
            'post_parent'   =>  $continent_id,
            'post_status'   =>  'publish',
            'meta_input'    =>  [
                'destination_intro'     =>  '',
                'destination_options'   =>  json_encode([
                    'destinations_menu'     =>  'false',
                    'include_posts_home'    =>  'true',
                    'show_link_submenus'    =>  'false',
                    'include_posts_child'   =>  'true',
                    'blog_categories'       =>  [],
                    'guide_lists'           =>  'true',
                    'google_map'            =>  [
                        'address'               =>  $name,
                        'longitude'             =>  $coordinates->getLongitude(),
                        'latitude'              =>  $coordinates->getLatitude(),
                        'zoom'                  =>  '6',
                        'show_directory_pins'   =>  'true',
                        'show_child_pins'       =>  'false',
                        'show_current_pin'      =>  'false',
                        'show_map_on_load'      =>  'true',
                    ]
                ]),
                'destination_order'                 =>  0,
                'theme_custom_sidebar_options_left' =>  'default',
                'theme_custom_sidebar_options_right'=>  'default',
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Map Calculations
    |--------------------------------------------------------------------------
    */

    /**
     * Calculate Map Windows
     *
     * @return void
     */
    public function calculate_maps() {

        $continents = $this->get_destinations();

        foreach ($continents as $continent) {
            
            $this->header($continent->post_title);
            $this->update_map_window($continent->ID);
            $this->update_itinerary_map_windows($continent->ID);
            
            $countries = $this->get_destinations($continent->ID);
            foreach ($countries as $country) {
                $this->subheader($country->post_title);
                $this->update_map_window($country->ID);
                $this->update_itinerary_map_windows($country->ID);
            }

        }

    }

    /**
     * Get destinations
     *
     * @return void
     */
    private function get_destinations($parent_id = 0) {
        $args = [
            'post_type' => 'destination',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_parent' => $parent_id,
        ];
        return get_posts($args);
    }

    /**
     * Get itineraries
     *
     * @return void
     */
    private function get_itineraries($parent_id) {
        $args = [
            'post_type' => 'destination-page',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'meta_query' => [
                [
                    'key' => 'destination_parent_id',
                    'value' => $parent_id,
                ]
            ],
            // 'post_parent' => $parent_id,
        ];
        $itineraries = get_posts($args);
        return $itineraries;
        // return CustomRelatedPosts::get()->relations_from($parent_id);
    }

    /**
     * Update map window
     *
     * @return void
     */
    private function update_map_window($post_id, $display_status = true) {
        if ($display_status) {
            $this->partial('Setting map window ... ');
        }
        $sites = unherit_get_map_pins($post_id);
        /*
        $latitudes = array_map('floatval', array_column($sites, 'latitude'));
        $longitudes = array_map('floatval', array_column($sites, 'longitude'));
        $window_sites = [
            [
                'latitude' => min($latitudes),
                'longitude' => min($longitudes),
            ],
            [
                'latitude' => max($latitudes),
                'longitude' => max($longitudes),
            ]
        ];
        $coords = unherit_get_coords_midpoint($window_sites);
        $zoom = unherit_get_map_zoom($window_sites);
        */
        $midpoint = unherit_get_coords_midpoint($sites);
        $zoom = unherit_get_map_zoom($sites);
        $options = get_destination_options($post_id);
        if (!isset($options['google_map'])) {
            $options['google_map'] = [];
        }
        $options['google_map']['latitude'] = $midpoint['latitude'];
        $options['google_map']['longitude'] = $midpoint['longitude'];
        $options['google_map']['zoom'] = $zoom;
        update_post_meta($post_id, 'destination_options', json_encode($options));
        if ($display_status) {
            $this->success("OK: {$midpoint['latitude']}, {$midpoint['longitude']}, {$zoom}");
        }
    }

    /**
     * Update all itinerary map windows for passed destination
     *
     * @return void
     */
    private function update_itinerary_map_windows($post_id) {
        $itineraries = $this->get_itineraries($post_id);
        if (!empty($itineraries)) {
            $this->line('Itineraries:');
            foreach ($itineraries as $itinerary) {
                $this->partial('&nbsp;&nbsp;&nbsp;&nbsp;- ' . $itinerary->post_title . ' ... ');
                $this->update_map_window($itinerary->ID, false);
                $this->success('OK');
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Output
    |--------------------------------------------------------------------------
    */

    /**
     * Output a string w/o newline
     *
     * @param string $str
     * @return void
     */
    private function partial($str) {
        echo $str;
    }

    /**
     * Output an unformatted line
     *
     * @param string $str
     * @param string $color
     * @return void
     */
    private function line($str, $color = null) {
        $ret = '';
        if ($color) {
            $ret .= '<span style="color:' . $color . ';font-weight:bold">';
        }
        $ret .= $str;
        if ($color) {
            $ret .= '</span>';
        }
        return $this->partial($ret . '<br>');
    }

    /**
     * Output a success line
     *
     * @param string $str
     * @return void
     */
    private function success($str) {
        return $this->line($str, 'green');
    }

    /**
     * Output an error line
     *
     * @param string $str
     * @return void
     */
    private function error($str) {
        return $this->line($str, 'red');
    }

    /**
     * Output a warning line
     *
     * @param string $str
     * @return void
     */
    private function warning($str) {
        return $this->line($str, 'orange');
    }

    /**
     * Output a header
     *
     * @param string $str
     * @return void
     */
    private function header($str) {
        return $this->partial('<h3>' . $str . '</h3>');
    }

    /**
     * Output a subheader
     *
     * @param string $str
     * @return void
     */
    private function subheader($str) {
        return $this->partial('<h4>' . $str . '</h4>');
    }

}
