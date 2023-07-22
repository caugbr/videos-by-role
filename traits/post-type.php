<?php

trait post_type {

    public function register_post_type() {
        if (!post_type_exists('video')) {
            $labels = array(
                'name' => $this->post_type_plural,
                'singular_name' => $this->post_type_name,
                'menu_name' => $this->post_type_plural,
                'add_new' => __('Add new', 'vbr'),
                'add_new_item' => sprintf(__('Add new %s', 'vbr'), $this->post_type),
                'edit_item' => sprintf(__('Edit %s', 'vbr'), $this->post_type),
                'new_item' => sprintf(__('New %s', 'vbr'), $this->post_type),
                'view_item' => sprintf(__('View %s', 'vbr'), $this->post_type),
                'search_items' => sprintf(__('Search %s', 'vbr'), strtolower($this->post_type_plural)),
                'not_found' => sprintf(__('No %s found', 'vbr'), strtolower($this->post_type_plural)),
                'not_found_in_trash' => sprintf(__('No %s found in trash', 'vbr'), strtolower($this->post_type_plural)),
                'parent_item_colon' => '',
            );
    
            $args = array(
                'labels' => $labels,
                'public' => true,
                'has_archive' => true,
                'publicly_queryable' => true,
                'query_var' => true,
                'rewrite' => array('slug' => strtolower($this->post_type_plural)),
                'capability_type' => 'post',
                'map_meta_cap' => true,
                'supports' => array('title', 'editor', 'thumbnail'),
                'menu_icon' => 'dashicons-video-alt3',
                'show_in_rest' => true // must be false to use the old editor
            );
            register_post_type($this->post_type, $args);
        }

        if (!taxonomy_exists('video_category')) {
            $taxonomy_args = array(
                'hierarchical' => true,
                'labels' => array(
                    'name' => __('Categories', 'vbr'),
                    'singular_name' => __('Category', 'vbr'),
                    'menu_name' => __('Categories', 'vbr'),
                    'all_items' => __('All Categories', 'vbr'),
                    'edit_item' => __('Edit Category', 'vbr'),
                    'view_item' => __('View Category', 'vbr'),
                    'update_item' => __('Update Category', 'vbr'),
                    'add_new_item' => __('Add New Category', 'vbr'),
                    'new_item_name' => __('New Category Name', 'vbr'),
                    'parent_item' => __('Parent Category', 'vbr'),
                    'parent_item_colon' => __('Parent Category:', 'vbr'),
                    'search_items' => __('Search Categories', 'vbr'),
                    'popular_items' => __('Popular Categories', 'vbr'),
                    'separate_items_with_commas' => __('Separate categories with commas', 'vbr'),
                    'add_or_remove_items' => __('Add or remove categories', 'vbr'),
                    'choose_from_most_used' => __('Choose from the most used categories', 'vbr'),
                    'not_found' => __('No categories found', 'vbr'),
                ),
                'public' => true,
                'rewrite' => array('slug' => $this->post_type . '-category'),
                'show_admin_column' => true,
                'show_in_rest' => true,
                'query_var' => true,
                'show_in_menu' => false // hide categories from user
            );
            register_taxonomy('video_category', $this->post_type, $taxonomy_args);
        }
    }
    
    public function add_video_meta_box() {
        $providers = $this->get_providers();
        if (count($providers) > 0) {
            add_meta_box('vbr-video', __('Import video', 'vbr'), [$this, 'video_meta_box_html'], $this->post_type, 'advanced');
        }
    }

    public function video_meta_box_html($post) {
        wp_nonce_field('vbr_video_nonce', 'vbr_video_nonce');
        $value = get_post_meta($post->ID, '_vbr_video', true);
        ?>
        <label for="vbr_video" style="padding-bottom: 0.25rem; display: block;">
            <?php _e('Video full URL (from: %providers_list%)', 'vbr'); ?>
        </label>
        <input 
            type="text" 
            style="width:100%" 
            id="vbr_video" 
            name="vbr_video" 
            value="<?php print esc_attr($value) ?>" 
            placeholder="<?php _e('Video URL', 'vbr'); ?>" 
        />
        <div style="margin-top: 0.5rem;">
            <div class="cols">
                <div class="col">
                    <button type="button" id="use-title" class="button button-secondary" disabled>
                        <?php _e('Use title', 'vbr'); ?>
                    </button>
                    <button type="button" id="use-image" class="button button-secondary" disabled>
                        <?php _e('Use thumbnail', 'vbr'); ?>
                    </button>
                </div>
                <div class="col">
                    <div class="video-size disabled">
                        <label for="video_size-responsive" style="padding-right: 1rem; font-weight: 600;">
                            <?php _e('Video size', 'vbr'); ?>
                        </label>
                        <div class="size responsive">
                            <input type="radio" name="video_size" id="video_size-responsive" checked>
                            <label for="video_size-responsive"><?php _e('responsive', 'vbr'); ?></label>
                        </div>
                        <div class="size fixed">
                            <input type="radio" name="video_size" id="video_size-fixed">
                            <label for="video_size-fixed"><?php _e('fixed size:', 'vbr'); ?></label>
                            <div class="video-w-h">
                                <input type="number" name="video_width" id="video_width" placeholder="width" disabled> X
                                <input type="number" name="video_height" id="video_height" placeholder="height" disabled>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="add-video" class="button button-secondary" disabled>
                        <?php _e('Embed video', 'vbr'); ?>
                    </button>
                </div>
            </div>
        </div>
        <div class="save-post-msg" style="display: none; padding-top: 0.5rem; text-align: center; color: #CCC;">
            <?php _e('You must save the post before you can insert the thumbnail', 'vbr'); ?>
        </div>
        <style>
            .video-size {
                display: inline-block;
                margin: 0;
                vertical-align: middle;
            }
            .video-size div {
                display: inline-block;
            }
            .video-size .size {
                margin-right: 0.5rem;
            }
            .video-size input {
                vertical-align: middle;
            }
            .video-size input[type="radio"] {
                margin-top: 0.08rem;
            }
            .video-w-h input {
                width: 4.5rem;
                text-align: center;
                padding-right: 0;
            }
            .cols {
                display: flex;
                flex-direction: row;
                flex-wrap: nowrap;
            }
            .col {
                flex-grow: 2;
                flex-shrink: 2;
            }
            .col:last-child {
                width: 50%;text-align: right;
            }
            .disabled {
                opacity: 0.6;
                cursor: default;
                pointer-events: none;
            }
            #vbr_video.error {
                border-color: red;
                color: red;
            }
        </style>
        <?php
    }

    public function save_video_metadata($post_id) {
        if (!isset($_POST['vbr_video_nonce']) || ! wp_verify_nonce($_POST['vbr_video_nonce'], 'vbr_video_nonce')) {
            return;
        }
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !isset($_POST['vbr_video'])) {
            return;
        }
    
        $video = sanitize_text_field($_POST['vbr_video']);
        update_post_meta($post_id, '_vbr_video', $video);
    }

    public function disable_gutenberg($current_status, $post_type) {
        return 'video' == $post_type ? false : $current_status;
    }
}