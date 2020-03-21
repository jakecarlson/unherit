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
use PHPHtmlParser\Dom as DomParser;

require_once(dirname(__FILE__) . '/ImporterBase.php');

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
class WHS_Importer_SiteImporter extends WHS_Importer_ImporterBase {

    /**
     * @var string $feed_url
     */
	private $feed_url = 'http://whc.unesco.org/en/list/xml';
//    private $feed_url = '../whs.xml';

    /**
     * @var int
     */
    private $batch_import_limit = 15;

    /**
     * @var string
     */
    private $gallery_suffix = '/gallery';

    /**
     * @var string
     */
    private $gallery_img_prefix = 'http://whc.unesco.org/document/';

    /**
     * Initialize the class and set its properties.
     *
     * @return WHS_Importer_SiteImporter
     */
    public function __construct() {
        parent::__construct();
        $this->feed_url = dirname(__FILE__) . '/' . $this->feed_url;
        return $this;
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

        $this->setup();

        $this->header("Importing XML Feed");

        // Load source XML
        $parsed = $this->load_src_xml();

        // Loop through sites and import to database
        foreach ($parsed['sites'] as $item) {

            // If the threshold of imports have been processed, kill the loop and abort
            if ($this->num_imported >= $this->batch_import_limit) {
                $this->error("The import limit of <strong>{$this->batch_import_limit}</strong> has been met, aborting. Please import again to continue processing.");
                break;
            }

            // Import the site
            $this->import_site($item);

        }

    }

    /**
     * Import a site
     *
     * @param array $item
     * @return void
     */
    private function import_site(array $item) {

        ++$this->num_processed;
        $this->partial("{$this->num_processed}. [{$item['id_number']}]: {$item['site']} ... ");
        $site = $this->get_site_by_whs_id($item['id_number']);

        // If the site is already in the system, check if this is a more recent revision
        $parsed_revision = $this->get_parsed_revision($item);
        if ($site) {

            $current_revision = $this->get_whs_revision($site->ID);
            if ($parsed_revision > $current_revision) {
                $destination_id = $this->get_country_destination_id($item);
                $this->update_existing_site($site->ID, $item, $destination_id);
                $this->success("REVISION UPDATED (v{$current_revision} --&gt; v{$parsed_revision})");
                ++$this->num_imported;
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

            ++$this->num_imported;

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
     * Find a site that matches the parsed item using the 'whs_id' unique site ID meta key
     *
     * @param int $id
     * @return WP_Post
     */
    private function get_site_by_whs_id(int $id) {
        $sites = get_posts([
            'posts_per_page'    =>  1,
            'post_type'         =>  'travel-directory',
            'meta_key'          =>  $this->prefix_whs_meta_key('id'),
            'meta_value'        =>  strval($id),
        ]);
        if (count($sites) > 0) {
            return $sites[0];
        } else {
            return false;
        }
    }

    /**
     * Get the site's revision number in the system
     *
     * @param int $site_id
     * @return int
     */
    private function get_whs_revision(int $site_id) {
        return intval($this->get_whs_meta($site_id, 'revision'));
    }

    /**
     * Get the XML parsed revision
     *
     * @param array $item
     * @return int
     */
    private function get_parsed_revision(array $item) {
        return intval($item['revision']);
    }

    /**
     * Insert a new site
     *
     * @param array $item
     * @param int $destination_id
     * @return int|WP_Error
     */
    private function insert_new_site(array $item, int $destination_id) {

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
    private function update_existing_site(int $site_id, array $item, int $destination_id) {
        $meta_keys = $this->generate_site_meta($item, $destination_id);
        foreach ($meta_keys as $key=>$val) {
            update_post_meta($site_id, $key, $val);
        }
    }

    /**
     * Add categories to the site
     *
     * @param int $site_id
     * @param array $item
     * @return bool
     */
    protected function add_site_categories(int $site_id, array $item) {
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
     * Generate site meta
     *
     * @param array $item
     * @param int $destination_id
     * @param bool $new
     * @return array
     */
    private function generate_site_meta(array $item, int $destination_id, bool $new = false) {
        $meta = $this->generate_whs_site_meta($item, $destination_id);
        if ($new) {
            $meta = array_merge($meta, $this->generate_directory_site_meta($item['latitude'], $item['longitude']));
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
    private function generate_whs_site_meta(array $item, int $destination_id) {
        return array_merge(
            $this->prefix_whs_meta_key([
                'id'                =>  $item['id_number'],
                'name'              =>  $item['site'],
                'category'          =>  $item['category'],
                'endangered'        =>  (!is_null($item['danger']) && ($item['danger'] != '0')),
                'unique_id'         =>  $item['unique_number'],
                'url'               =>  $item['http_url'],
                'image'             =>  $item['image_url'],
                'summary'           =>  $item['short_description'],
                'description'       =>  $item['long_description'],
                'justification'     =>  $item['justification'],
                'historical'        =>  $item['historical_description'],
                'year_inscribed'    =>  $item['date_inscribed'],
                'location'          =>  $item['location'],
                'region'            =>  $item['region'],
                'transboundary'     =>  ($item['transboundary'] != '0'),
                'extension'         =>  ($item['extension'] != '0'),
                'criteria_txt'      =>  $item['criteria_txt'],
                'iso_code'          =>  $item['iso_code'],
                'secondary_dates'   =>  $item['secondary_dates'],
                'revision'          =>  $item['revision'],
            ]),
            [
                'guide_lists_intro'     =>  wp_strip_all_tags($item['short_description']),
                'destination_parent_id' =>  $destination_id,
            ]
        );
    }

    /**
     * Get the WHS image
     *
     * @param array $item
     * @return string
     */
    private function get_site_img_url(array $item) {
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
    private function get_site_gallery_url(array $item) {
        return $item['http_url'] . $this->gallery_suffix;
    }

    /**
     * Get the URL of a gallery image
     *
     * @param int $id
     * @return string
     */
    private function get_site_gallery_img_url(int $id) {
        return $this->gallery_img_prefix . $id;
    }

}
