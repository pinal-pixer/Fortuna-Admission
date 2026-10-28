<?php
namespace GutSlider\Blocks;

use WP_REST_Request;
use WP_REST_Server;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Pattern')) {

    class Pattern
    {

        const TRANSIENT_KEY = 'gutslider_patterns_cache';
        const TRANSIENT_EXPIRY = HOUR_IN_SECONDS * 12;
        const REMOTE_URL       = 'https://gutslider.com/wp-json/divo-patterns/v1/patterns';
        const NAMESPACE = 'gutslider/v1';
        const ROUTE = '/patterns';

        private static $instance = null;

        public function __construct()
        {
            add_action('rest_api_init', [$this, 'register_routes']);
        }

        public function register_routes()
        {
            register_rest_route(
                self::NAMESPACE,
                self::ROUTE,
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_patterns'],
                    'permission_callback' => [$this, 'permissions_check'],
                    'args' => [
                        'refresh' => [
                            'type' => 'boolean',
                            'default' => false,
                        ],
                        'page' => [
                            'type' => 'integer',
                            'default' => 1,
                        ],
                        'per_page' => [
                            'type' => 'integer',
                            'default' => 20,
                        ],
                        'category' => [
                            'type'    => 'string',
                            'default' => '',
                        ],
                    ],
                ]
            );
        }

        /**
         * Only logged-in users with edit_posts capability can call this endpoint.
         */
        public function permissions_check()
        {
            return current_user_can('edit_posts');
        }

        public function get_patterns(WP_REST_Request $request)
        {
            $force_refresh = (bool) $request->get_param('refresh');
            $page          = (int) $request->get_param('page');
            $per_page      = (int) $request->get_param('per_page');
            $category      = (string) $request->get_param('category');

            if (!$force_refresh) {
                $cached = get_transient(self::TRANSIENT_KEY);
                if (false !== $cached) {
                    return $this->paginate_patterns($cached, $page, $per_page, $category);
                }
            }

            $chunks          = [];
            $remote_page     = 1;
            $per_page_remote = 100;
            $max_pages       = 5;

            while ($remote_page <= $max_pages) {
                $url = add_query_arg(
                    ['page' => $remote_page, 'per_page' => $per_page_remote],
                    self::REMOTE_URL
                );

                $response = wp_remote_get($url, [
                    'timeout'    => 10,
                    'user-agent' => 'GutSliderBlocks/' . GUTSLIDER_VERSION,
                ]);

                if (is_wp_error($response)) {
                    if (!empty($chunks)) break;
                    return new WP_Error('gutslider_fetch_failed', $response->get_error_message(), ['status' => 502]);
                }

                $code = wp_remote_retrieve_response_code($response);
                if (200 !== (int) $code) {
                    if (!empty($chunks)) break;
                    return new WP_Error('gutslider_fetch_failed', sprintf('Remote API returned HTTP %d.', $code), ['status' => 502]);
                }

                $data = json_decode(wp_remote_retrieve_body($response), true);

                if (!is_array($data) || empty($data)) {
                    break;
                }

                $chunks[] = $data;

                if (count($data) < $per_page_remote) {
                    break;
                }

                $remote_page++;
            }

            $all_patterns = $chunks ? array_merge(...$chunks) : [];
            $categories   = $this->extract_categories($all_patterns);
            $all_total    = count($all_patterns);

            $cache_data = [
                'patterns'   => $all_patterns,
                'categories' => $categories,
                'all_total'  => $all_total,
            ];

            set_transient(self::TRANSIENT_KEY, $cache_data, self::TRANSIENT_EXPIRY);

            return $this->paginate_patterns($cache_data, $page, $per_page, $category);
        }

        /**
         * Paginate patterns array, optionally filtering by category.
         * Accepts either the structured cache array or a raw patterns array (backwards compat).
         */
        private function paginate_patterns($cache, $page = 1, $per_page = 20, $category = '')
        {
            if (isset($cache['patterns'])) {
                $all_patterns = $cache['patterns'];
                $categories   = $cache['categories'];
                $all_total    = $cache['all_total'];
            } else {
                $all_patterns = $cache;
                $categories   = $this->extract_categories($all_patterns);
                $all_total    = count($all_patterns);
            }

            if ($category !== '') {
                $cat_lower    = strtolower($category);
                $all_patterns = array_values(array_filter($all_patterns, function ($p) use ($cat_lower) {
                    foreach ((array) ($p['categories'] ?? []) as $cat) {
                        $name = is_array($cat) ? ($cat['name'] ?? '') : (string) $cat;
                        if (strtolower(trim($name)) === $cat_lower) {
                            return true;
                        }
                    }
                    return false;
                }));
            }

            $total  = count($all_patterns);
            $offset = ($page - 1) * $per_page;
            $items  = array_slice($all_patterns, $offset, $per_page);

            return rest_ensure_response([
                'items'       => $items,
                'total'       => $total,
                'all_total'   => $all_total,
                'page'        => $page,
                'per_page'    => $per_page,
                'total_pages' => $total > 0 ? (int) ceil($total / $per_page) : 1,
                'categories'  => $categories,
            ]);
        }

        private function extract_categories($patterns)
        {
            $counts = [];
            foreach ($patterns as $pattern) {
                foreach ((array) ($pattern['categories'] ?? []) as $cat) {
                    $name = is_array($cat) ? ($cat['name'] ?? '') : (string) $cat;
                    $name = trim($name);
                    if ($name !== '') {
                        $counts[$name] = ($counts[$name] ?? 0) + 1;
                    }
                }
            }
            $result = [];
            foreach ($counts as $name => $count) {
                $result[] = ['name' => $name, 'count' => $count];
            }
            usort($result, fn($a, $b) => strcmp($a['name'], $b['name']));
            return $result;
        }

        public static function instance()
        {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }
    }
}