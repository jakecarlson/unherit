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

use Http\Adapter\Guzzle6\Client as HttpClient;
use Geocoder\Provider\GoogleMaps\GoogleMaps as GeocodeProvider;
use Geocoder\StatefulGeocoder;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Geocoder\Exception\InvalidArgument;

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
class WHS_Importer_ImporterBase {

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
    private $site_img_suffix = ' Image';

    /**
     * @var string
     */
    private $whs_meta_key_prefix = 'whs_';

    /**
     * @var int
     */
    protected $num_imported = 0;

    /**
     * @var int
     */
    protected $num_processed = 0;

    /**
     * Initialize the class and set its properties.
     *
     * @return WHS_Importer_ImporterBase
     */
    public function __construct() {
        $this->continents_url = dirname(__FILE__) . '/' . $this->continents_url;
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
    public function setup() {

        $this->header("Preparing Geocoder");

        // Prepare Geocoder
        $this->set_geocoder();

        // Get continents
        $this->set_continents_map();
        $this->set_countries_map();
        $this->set_categories_map();

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
     * Get WHS meta data.
     *
     * @param int $site_id
     * @param string $key
     * @return mixed
     */
    protected function get_whs_meta(int $site_id, string $key) {
        return get_post_meta($site_id, 'whs_' . $key, true);
    }

    /**
     * Generate directory meta
     *
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    protected function generate_directory_site_meta($latitude, $longitude) {
        return [
            'guide_lists_details'   =>  json_encode([
                'address'       =>  '',
                'google_map'    =>  [
                    'address'   =>  '',
                    'longitude' =>  $longitude,
                    'latitude'  =>  $latitude,
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
    protected function get_site_content_placeholder() {
        return $this->site_content_placeholder;
    }

    /**
     * Prefix a string with WHS meta key prefix
     *
     * @param string $str
     * @return array|string
     */
    protected function prefix_whs_meta_key(string $str) {
        if (is_array($str)) {
            foreach ($str as $key => $val) {
                $str[$this->prefix_whs_meta_key($key)] = $val;
                unset($str[$key]);
            }
            return $str;
        } else {
            return $this->whs_meta_key_prefix . $str;
        }
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
    protected function set_categories_map() {
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
    protected function get_categories_map() {
        return $this->categories;
    }

    /**
     * Get the ID of the passed category name
     *
     * @param string $category
     * @return mixed
     */
    protected function get_category_id(string $category) {
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
    protected function set_geocoder() {
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
    protected function get_geocode_api_key() {
        return $this->geocode_api_key;
    }

    /**
     * Set the continents map
     *
     * @return void
     */
    protected function set_continents_map() {
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
    protected function get_continents_map() {
        return $this->continents;
    }

    /**
     * Get countries map.
     *
     * @return array
     */
    protected function get_countries() {
        return $this->countries;
    }

    /**
     * Get continents JSON URL.
     *
     * @return string
     */
    protected function get_continents_url() {
        return $this->continents_url;
    }

    /**
     * Find an existing country post or create it if it doesn't exist
     *
     * @param string $name
     * @param string $code
     * @return integer
     */
    protected function find_or_create_country(string $name, string $code) {

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
                $this->get_coordinates($name)
            );

        }
    }

    /**
     * Set countries continents map.
     *
     * @return void
     */
    protected function set_countries_map() {
        $this->partial("Importing country-to-continent map ...");
        $this->countries = json_decode(file_get_contents($this->get_continents_url()), true);
        ksort($this->countries);
        $this->success("OK");
    }

    /**
     * Get continent name from country continent map.
     *
     * @param string $code
     * @return string
     */
    protected function get_continent_from_country_code(string $code) {
        return $this->countries[$code];
    }

    /**
     * Figure out which continent the country belongs to
     *
     * @param string $code
     * @return int
     */
    protected function get_continent_id(string $code) {
        return array_search(
            $this->get_continent_from_country_code($code),
            $this->get_continents_map()
        );
    }

    /**
     * Get a country's coordinates
     *
     * @param string $str
     * @return \Geocoder\Model\Coordinates|null
     */
    protected function get_coordinates(string $str) {
        $location = $this->geocoder->geocodeQuery(GeocodeQuery::create($str));
        if ($location->count() == 0) {
            return false;
        } else {
            return $location->first()->getCoordinates();
        }
    }

    /**
     * Get the destination ID of the item's country
     *
     * @param array $item
     * @return int
     */
    protected function get_country_destination_id(array $item) {
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
     * @param string $name
     * @param int $continent_id
     * @param array $coordinates
     * @return int|WP_Error
     */
    protected function insert_new_country(string $name, int $continent_id, array $coordinates) {
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
    | Output
    |--------------------------------------------------------------------------
    */

    /**
     * Output a string w/o newline
     *
     * @param string $str
     * @return void
     */
    protected function partial(string $str) {
        echo $str;
    }

    /**
     * Output an unformatted line
     *
     * @param string $str
     * @param string $color
     * @return void
     */
    protected function line(string $str, string $color = null) {
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
    protected function success(string $str) {
        return $this->line($str, 'green');
    }

    /**
     * Output an error line
     *
     * @param string $str
     * @return void
     */
    protected function error(string $str) {
        return $this->line($str, 'red');
    }

    /**
     * Output a warning line
     *
     * @param string $str
     * @return void
     */
    protected function warning(string $str) {
        return $this->line($str, 'orange');
    }

    /**
     * Output a header
     *
     * @param string $str
     * @return void
     */
    protected function header(string $str) {
        return $this->partial('<h3>' . $str . '</h3>');
    }

}
