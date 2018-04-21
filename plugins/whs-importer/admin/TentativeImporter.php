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
class WHS_Importer_TentativeImporter extends WHS_Importer_ImporterBase {

    /**
     * @var string $feed_url
     */
    private $url_host = 'http://whc.unesco.org';

    /**
     * @var string $feed_url
     */
	private $url_prefix = '/en/tentativelists/state=';

    /**
     * @var string
     */
	private $site_url_regex = "/\/en\/tentativelists\/[0-9]+/";

    /**
     * @var array
     */
	private $meta = [
	    'submission_date'   =>  'Date of Submission',
        'category'          =>  'Category',
        'submitted_by'      =>  'Submitted by',
        'coordinates'       =>  'Coordinates',
        'ref_num'           =>  'Ref.',
    ];

    /**
     * @var int
     */
    private $batch_import_limit = 15;

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

        $this->header("Scraping Tentative List");

        foreach ($this->get_countries() as $country=>$continent) {
            $this->header($country);
            var_dump($this->get_country_url($country));
            $html = file_get_contents($this->get_country_url($country));
            preg_match_all($this->site_url_regex, $html, $matches);
            var_dump($matches[0]);
            foreach ($matches[0] as $url) {
                $url = $this->url_host . $url;
                if (!$this->site_exists($url)) {
                    $this->import_site($url, $country);
                }
                exit;

            }
            flush();
            exit;
        }


    }

    /**
     * Whether the site exists
     *
     * @param string $url
     * @return bool
     */
    private function site_exists(string $url) {
        $sites = get_posts([
            'posts_per_page'    =>  1,
            'post_type'         =>  'travel-directory',
            'meta_key'          =>  $this->prefix_whs_meta_key('url'),
            'meta_value'        =>  $url,
        ]);
        return (count($sites) > 0);
    }

    /**
     * Import a site
     *
     * @param string $url
     * @param string $country_code
     * @return int
     */
    protected function import_site(string $url, string $country_code) {
        $dom = new DomParser();
        $dom->loadFromUrl($url);
        $name = $this->get_site_name($dom);
        $description = $this->get_site_description($dom);
        $meta = $this->get_site_meta($dom, $url, $name, $description, $country_code);
        return $this->insert_new_site($name, $meta);
    }

    /**
     * Get the site name
     *
     * @param DomParser $dom
     * @return string
     */
    private function get_site_name(DomParser $dom) {
        $h1 = $dom->find('h1', 0);
        return $h1->text;
    }

    /**
     * Get the site description
     *
     * @param DomParser $dom
     * @return string
     */
    private function get_site_description(DomParser $dom) {
        $box = $dom->find('.ym-g66', 0);
        return $box->find('.box', 0)->find('.box', 0);
    }

    /**
     * Get the site country
     *
     * @param DomParser $dom
     * @return string
     */
    private function get_site_country(DomParser $dom) {
        $box = $dom->find('.alternate', 0);
        return trim($box->find('strong', 0)->find('a', 0)->text);
    }

    /**
     * Get the site meta
     *
     * @param DomParser $dom
     * @param string $url
     * @param string $name
     * @param string $description
     * @param string $country_code
     * @return array
     */
    private function get_site_meta(DomParser $dom, string $url, string $name, string $description, string $country_code) {
        $box = $dom->find('.alternate', 0);
        $metas = $box->find('strong');
        $site_meta = $this->prefix_whs_meta_key([
            'name'  =>  $name,
            'url'   =>  $url,
        ]);
        foreach ($metas as $meta) {
            $key = array_search(substr(trim($meta->text), 0, -1), $this->meta);
            $val = trim($meta->nextSibling()->text);
            $this->line($meta->text);
            $this->line($val);
            if (!empty($key) && !empty($val)) {
                $site_meta[$this->prefix_whs_meta_key($key)] = $val;
            }
        }
        $country = $this->get_site_country($dom);
        $coords = $this->get_best_site_coordinates($site_meta[$this->prefix_whs_meta_key('coordinates')], $country);
        return array_merge(
            $site_meta,
            [
                'guide_lists_intro'     =>  wp_strip_all_tags($description),
                'destination_parent_id' =>  $this->find_or_create_country($country, $country_code),
            ],
            $this->generate_directory_site_meta($coords->getLatitude(), $coords->getLongitude())
        );
    }

    /**
     * Insert a new site
     *
     * @param string $name
     * @param array $meta
     * @return int|WP_Error
     */
    private function insert_new_site(string $name, array $meta) {

        $site_id = wp_insert_post([
            'post_title'    =>  wp_strip_all_tags($name),
            'post_content'  =>  $this->get_site_content_placeholder(),
            'post_type'     =>  'travel-directory',
            'post_status'   =>  'publish',
            'meta_input'    =>  $meta,
        ]);

        // Add categories
        if ($site_id) {
            $this->add_site_categories($site_id, $meta[$this->prefix_whs_meta_key('category')]);
        }

        return $site_id;

    }

    /**
     * Add categories to the site
     *
     * @param int $site_id
     * @param string $category
     * @return bool
     */
    protected function add_site_categories(int $site_id, string $category) {
        $terms = [$this->get_category_id('Tentative')];
        if ($category == 'Mixed') {
            $terms[] = $this->get_category_id('Mixed');
        }
        if (in_array($category, ['Natural','Mixed'])) {
            $terms[] = $this->get_category_id('Natural');
        }
        if (in_array($category, ['Cultural','Mixed'])) {
            $terms[] = $this->get_category_id('Cultural');
        }
        return wp_set_post_terms($site_id, $terms, 'travel-dir-category');
    }

    /**
     * Get the best coordinates possible
     *
     * @param string $coords
     * @param string $country
     * @return \Geocoder\Model\Coordinates|null|string
     */
    private function get_best_site_coordinates(string $coords, string $country) {
        $coords = $this->get_coordinates($coords);
        if (!$coords) {
            $coords = $this->get_coordinates($country);
        }
        return $coords;
    }

    /**
     * Get the feed URL
     *
     * @return string
     */
    protected function get_url_prefix() {
        return $this->url_host . $this->url_prefix;
    }

    /**
     * Get the feed URL
     *
     * @param string $code
     * @return string
     */
    protected function get_country_url(string $code) {
        return $this->get_url_prefix() . strtolower($code);
    }

}
