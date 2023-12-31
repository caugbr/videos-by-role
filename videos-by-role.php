<?php
/*
Plugin Name: Videos by role
Description: A plugin to organize videos in categories and restrict access to them by user roles.
Version: 1.0
Author: Cau Guanabara
Author URI: 
Text Domain: vbr
Domain Path: /langs
*/

include "traits/admin-page.php";
include "traits/post-type.php";

class VideosByRole {

    use admin_page;
    use post_type;

    var $post_type = 'video';
    var $post_type_name = 'Video';
    var $post_type_plural = 'Videos';

    public function __construct() {
        $this->set_post_type_names();
        add_action('init', [$this, 'load_textdomain']);
        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes_' . $this->post_type, [$this, 'add_video_meta_box']);
        add_action('save_post_' . $this->post_type, [$this, 'save_video_metadata']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_custom_script']);
        add_action('admin_menu', [$this, 'videos_admin_page']);
        add_action('wp_ajax_thumbnail_url', [$this, 'thumbnail_url']);
        add_action('pre_get_posts', [$this, 'modify_video_query']);
        add_action('wp_head', [$this, 'responsive_video_css']);
        add_filter('use_block_editor_for_post_type', [$this, 'disable_gutenberg'], 10, 2);
    }

    private function set_post_type_names() {
        $post_type = get_option('vbr_post_type');
        if ($post_type) {
            $this->post_type = $post_type;
        }
        $post_type_name = get_option('vbr_post_type_name');
        if ($post_type_name) {
            $this->post_type_name = $post_type_name;
        }
        $post_type_plural = get_option('vbr_post_type_plural');
        if ($post_type_plural) {
            $this->post_type_plural = $post_type_plural;
        }
    }

    function load_textdomain() {
        load_plugin_textdomain('vbr', false, dirname(plugin_basename(__FILE__)) . '/langs'); 
    }
    
    public function enqueue_custom_script() {
        $screen = get_current_screen();
        if ($screen->id === $this->post_type) {
            wp_enqueue_script('axios', 'https://unpkg.com/axios/dist/axios.min.js');
            wp_enqueue_script('videos-by-role', plugins_url("js/videos-by-role.js", __FILE__));
            wp_localize_script('videos-by-role', 'vbrInfo',
                [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('videos-by-role'),
                    'post_id' => $_GET['post'] ?? 0,
                    'providers' => $this->get_providers(),
                    'post_type' => $this->post_type
                ]
            );
            $providers_path = plugin_dir_path(__FILE__) . '/oembed-providers';
            $providers = array_diff(scandir($providers_path), ['..', '.']);
            foreach ($providers as $prov) {
                $url = plugins_url('oembed-providers/' . $prov, __FILE__);
                wp_enqueue_script('videos-by-role-' . str_replace('.js', '', $prov), $url);
            }
        }
        if ($screen->id === 'settings_page_video-options') {
            wp_enqueue_script('videos-by-role-admin', plugins_url('js/videos-by-role-admin.js', __FILE__));
        }
    }

    private function get_roles() {
        return get_option('vbr_roles', []);
    }

    private function get_capabilities() {
        $roles = $this->get_roles();
        $caps = [];
        foreach ($roles as $info) {
            foreach ($info['capabilities'] as $cap) {
                if (false === array_search($cap, $caps)) {
                    array_push($caps, $cap);
                }
            }
        }
        return $caps;
    }

    private function remove_role($role) {
        $roles = $this->get_roles();
        $slug = sanitize_title($role);
        unset($roles[$slug]);
        update_option('vbr_roles', $roles);
        remove_role($slug);
    }

    private function add_role($role, $caps) {
        $roles = $this->get_roles();
        $new_role_slug = sanitize_title($role);
        $new_caps = [];
        foreach ($caps as $cap) {
            $new_caps[$cap] = true;
        }
        $new_role = [ "name" => $role, "capabilities" => $caps ];
        $roles[$new_role_slug] = $new_role;
        update_option('vbr_roles', $roles);

        if (!$this->role_exists($role)) {
            add_role($new_role_slug, $role, $new_caps);
        }
    }

    private function role_exists($role) {
        if (!empty($role)) {
            return wp_roles()->is_role($role);
        }
        return false;
    }

    private function get_option_array($name) {
        $opt = get_option($name);
        if (!$opt) {
            return [];
        }
        return $opt;
    }

    private function get_categories() {
        $opt = get_option('vbr_categories');
        if (!$opt) {
            return [];
        }
        return $opt;
    }

    private function get_providers() {
        $opt = get_option('vbr_providers');
        if (!$opt) {
            return [];
        }
        return $opt;
    }

    private function add_category($cat) {
        $cats = $this->get_categories();
        $new_cat_slug = sanitize_title($cat);
        if (false === array_search($new_cat_slug, $cats)) {
            $cats[] = $new_cat_slug;
            update_option('vbr_categories', $cats);
            if (!term_exists($new_cat_slug, 'video_category')) {
                $result = wp_insert_term($cat, 'video_category', ['slug' => $new_cat_slug]);
                return (!is_wp_error($result));
            }
        }
        return false;
    }

    private function remove_category($role) {
        $cats = $this->get_categories();
        $slug = sanitize_title($role) . "-videos";
        unset($cats[array_search($slug, $cats)]);
        update_option('vbr_categories', $cats);
        $term = get_term_by('slug', $slug, "video_category");
        if ($term) {
            wp_delete_term($term->term_id, "video_category");
        }
    }

    private function remove_capability($role) {
        $cap = "watch_" . sanitize_title($role) . "_videos";
        $roles = $this->get_roles();
        foreach ($roles as $index => $role) {
            $ind = array_search($cap, $role['capabilities']);
            if (false !== $ind) {
                unset($role['capabilities'][$ind]);
                $roles[$index] = $role;
            }
        }
        update_option('vbr_roles', $roles);
    }

    public function thumbnail_url() {
        if (!wp_verify_nonce($_POST['nonce'], 'videos-by-role')) {
            wp_die('Invalid token');
        }
        $meta = $this->add_image($_POST['post_id'], $_POST['url']);
        wp_die(json_encode($meta));
    }

    private function add_image($post_id, $url, $desc = '') {
        if (!wp_verify_nonce($_POST['nonce'], 'videos-by-role')) {
            wp_die ('Invalid token');
        }
        $image = media_sideload_image($url, $post_id, $desc, 'id');
        set_post_thumbnail($post_id, $image);
        $id = get_post_thumbnail_id($post_id);
        $meta = wp_get_attachment_metadata($id);
        return $meta;
    }

    private function get_allowed_cats() {
        $user = wp_get_current_user();
        $allowed = [];
        foreach(array_keys($user->allcaps) as $cap) {
            if (preg_match("/^watch_([^_]+)_videos$/", $cap, $matches)) {
                $allowed[] = "{$matches[1]}-videos";
            }
        }
        return $allowed;
    }

    private function is_site_admin() {
        return in_array('administrator', wp_get_current_user()->roles);
    }

    public function modify_video_query($query) {
        if (!is_admin() && !$this->is_site_admin() && (is_singular($this->post_type) || is_post_type_archive($this->post_type))) {
            $allowed = $this->get_allowed_cats();
            if (!empty($allowed)) {
                $tax_query = [
                    [
                        'taxonomy' => 'video_category',
                        'field' => 'slug',
                        'terms' => $allowed,
                        'operator' => 'IN'
                    ]
                ];
                $query->set('tax_query', $tax_query);
            } else {
                $query->set('post__in', [0]);
            }
        }
    }

    public function responsive_video_css() {
    ?>
    <style>
        .responsive-video { 
            position: relative; 
            height: 0; 
            padding-bottom: calc(var(--aspect-ratio, .5625) * 100%); 
        } 
        .responsive-video iframe, 
        .responsive-video embed, 
        .responsive-video object { 
            position: absolute; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
        }
    </style>
    <?php
    }
}

global $videos_by_role;
$videos_by_role = new VideosByRole();