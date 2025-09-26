<?php

namespace CSIG;

if (!defined('ABSPATH')) {
    exit;
}

class Job_Post_Type {
    
    const POST_TYPE = 'csig_job';
    
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_job_meta'));
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_filter('post_row_actions', array($this, 'add_run_job_action'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_job_scripts'));
        add_action('edit_form_after_title', array($this, 'add_job_interface'));
    }
    
    public function register_post_type() {
        $labels = array(
            'name'                  => __('Image Jobs', 'csig'),
            'singular_name'         => __('Image Job', 'csig'),
            'menu_name'             => __('Image Jobs', 'csig'),
            'name_admin_bar'        => __('Image Job', 'csig'),
            'add_new'               => __('Add New', 'csig'),
            'add_new_item'          => __('Add New Image Job', 'csig'),
            'new_item'              => __('New Image Job', 'csig'),
            'edit_item'             => __('Edit Image Job', 'csig'),
            'view_item'             => __('View Image Job', 'csig'),
            'all_items'             => __('All Image Jobs', 'csig'),
            'search_items'          => __('Search Image Jobs', 'csig'),
            'parent_item_colon'     => __('Parent Image Jobs:', 'csig'),
            'not_found'             => __('No image jobs found.', 'csig'),
            'not_found_in_trash'    => __('No image jobs found in Trash.', 'csig'),
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __('Image generation jobs', 'csig'),
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => 'client-side-image-generator',
            'query_var'          => true,
            'rewrite'            => array('slug' => 'csig-job'),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor'),
            'menu_icon'          => 'dashicons-camera-alt',
        );

        register_post_type(self::POST_TYPE, $args);
    }
    
    public function add_meta_boxes() {
        add_meta_box(
            'csig_job_settings',
            __('Job Settings', 'csig'),
            array($this, 'job_settings_meta_box'),
            self::POST_TYPE,
            'normal',
            'high'
        );
        
        /*add_meta_box(
            'csig_job_capture',
            __('Live Preview & Capture', 'csig'),
            array($this, 'job_capture_meta_box'),
            self::POST_TYPE,
            'side',
            'high'
        );*/
        
        add_meta_box(
            'csig_job_stats',
            __('Job Statistics', 'csig'),
            array($this, 'job_stats_meta_box'),
            self::POST_TYPE,
            'side',
            'default'
        );
        
        // Remove the default editor since we don't need it for jobs
        remove_post_type_support(self::POST_TYPE, 'editor');
        
        // Move settings to top priority
        add_action('edit_form_after_title', array($this, 'add_settings_before_editor'), 5);
    }
    
    public function add_settings_before_editor($post) {
        if ($post->post_type !== self::POST_TYPE) {
            return;
        }
        
        // Render the settings metabox content directly here
        echo '<div style="margin: 20px 0;">';
        echo '<div class="postbox" style="margin-bottom: 0;">';
        echo '<div class="postbox-header"><h2 class="hndle ui-sortable-handle">' . __('Job Settings', 'csig') . '</h2></div>';
        echo '<div class="inside">';
        $this->job_settings_meta_box($post);
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // Hide the duplicate settings metabox that will appear below
        echo '<style>#csig_job_settings { display: none !important; }</style>';
    }
    
    public function enqueue_job_scripts($hook) {
        global $post_type, $post;
        
        if ($post_type !== self::POST_TYPE) {
            return;
        }
        
        // Only enqueue on edit pages for published jobs
        if (($hook === 'post.php' || $hook === 'post-new.php') && 
            ($post && $post->post_status === 'publish')) {
            
            wp_enqueue_script('html-to-image', 'https://cdn.jsdelivr.net/npm/html-to-image@1.11.11/dist/html-to-image.js', [], CSIG_VERSION, true);
            wp_enqueue_script('jspdf', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/3.0.3/jspdf.umd.min.js', [], CSIG_VERSION, true);
            wp_enqueue_script('csig-job-editor', CSIG_PLUGIN_URL . 'assets/js/csig-job-editor.js', ['html-to-image', 'jspdf'], CSIG_VERSION, true);
            
            $job_settings = self::get_job_settings($post->ID);
            
            wp_localize_script('csig-job-editor', 'csigJobData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('csig_save_image'),
                'settings' => $job_settings,
                'jobId' => $post->ID,
                'jobUrl' => get_post_meta($post->ID, '_csig_url', true)
            ));
        }
    }
    
    public function add_job_interface($post) {
        if ($post->post_type !== self::POST_TYPE) {
            return;
        }
        
        if ($post->post_status !== 'publish') {
            ?>
            <div id="csig-publish-notice" class="notice notice-info" style="margin: 20px 0;">
                <p><?php _e('Publish this job to enable the capture functionality.', 'csig'); ?></p>
            </div>
            <?php
            return;
        }
        
        $url = get_post_meta($post->ID, '_csig_url', true);
        $selector = get_post_meta($post->ID, '_csig_selector', true) ?: '.csig-card';
        $output_format = get_post_meta($post->ID, '_csig_output_format', true) ?: 'raster';
        $image_quality = get_post_meta($post->ID, '_csig_image_quality', true) ?: 'high';
        $quality_map = array('low' => 1, 'high' => 2, 'ultra' => 3);
        $pixel_ratio = $quality_map[$image_quality] ?? 2;
        $job_settings = self::get_job_settings($post->ID);
        
        ?>
        <div id="csig-job-interface" style="margin: 20px 0;">
            <?php if (!$url): ?>
                <div class="notice notice-error inline" style="margin-bottom: 20px;">
                    <p><?php _e('Please add a URL in the Job Settings above before running this job.', 'csig'); ?></p>
                </div>
            <?php else: ?>
                <!-- Job Summary -->
                <div id="csig-job-summary" style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                    <h2 style="margin-top: 0; margin-bottom: 10px;"><?php _e('Live Preview & Capture', 'csig'); ?></h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; font-size: 14px; margin-bottom: 15px;">
                        <div>
                            <strong><?php _e('URL:', 'csig'); ?></strong><br>
                            <a href="<?php echo esc_url($url); ?>" target="_blank" style="word-break: break-all;"><?php echo esc_html($url); ?></a>
                        </div>
                        <div>
                            <strong><?php _e('Selector:', 'csig'); ?></strong><br>
                            <code><?php echo esc_html($selector); ?></code>
                        </div>
                        <div>
                            <strong><?php _e('Format:', 'csig'); ?></strong><br>
                            <?php echo esc_html(ucfirst($output_format)); ?>
                        </div>
                        <div>
                            <strong><?php _e('Quality:', 'csig'); ?></strong><br>
                            <?php echo esc_html(ucfirst($image_quality)); ?> (<?php echo $pixel_ratio; ?>x)
                        </div>
                    </div>
                    
                    <!-- Performance warning -->
                    <?php if ($pixel_ratio >= 3): ?>
                    <div class="notice notice-warning inline" style="margin-bottom: 15px;">
                        <p><strong><?php _e('Performance Note:', 'csig'); ?></strong> <?php _e('This job uses Ultra quality (3x) which may take significantly longer to process.', 'csig'); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Run Job Button -->
                    <button type="button" class="button button-primary button-large" id="csig-run-job">
                        <?php _e('Generate Images Now', 'csig'); ?>
                    </button>
                    
                    <div id="csig-status" style="margin-top: 15px; display: none;">
                        <div class="notice notice-info inline">
                            <p id="csig-status-text"><?php _e('Processing...', 'csig'); ?></p>
                            <div style="width: 100%; background-color: #f0f0f0; border-radius: 3px; margin-top: 10px;">
                                <div id="csig-progress-bar" style="width: 0%; height: 20px; background-color: #0073aa; border-radius: 3px; transition: width 0.3s;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="csig-results" style="margin-top: 15px; display: none;">
                        <h4><?php _e('Generated Files:', 'csig'); ?></h4>
                        <div id="csig-file-list"></div>
                    </div>
                </div>
                
                <!-- Preview Section with Dynamic Resizing -->
                <div style="background: white; border: 1px solid #ddd; border-radius: 4px; padding: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3 style="margin: 0;"><?php _e('Live Preview', 'csig'); ?></h3>
                        <div id="csig-viewport-controls" style="display: flex; align-items: center; gap: 10px;">
                            <label for="csig-preview-mode" style="font-weight: 600;"><?php _e('Viewport:', 'csig'); ?></label>
                            <select id="csig-preview-mode" style="min-width: 140px;">
                                <option value="fixed" <?php selected($job_settings['iframeMode'], 'fixed'); ?>><?php _e('Fixed (1200×800)', 'csig'); ?></option>
                                <option value="desktop" <?php selected($job_settings['iframePreset'], 'desktop'); ?>><?php _e('Desktop (1200×800)', 'csig'); ?></option>
                                <option value="tablet" <?php selected($job_settings['iframePreset'], 'tablet'); ?>><?php _e('Tablet (768×1024)', 'csig'); ?></option>
                                <option value="mobile" <?php selected($job_settings['iframePreset'], 'mobile'); ?>><?php _e('Mobile (375×667)', 'csig'); ?></option>
                                <option value="custom" <?php selected($job_settings['iframePreset'], 'custom'); ?>><?php _e('Custom', 'csig'); ?></option>
                            </select>
                            <div id="csig-custom-dimensions" style="<?php echo $job_settings['iframePreset'] === 'custom' ? '' : 'display: none;'; ?>">
                                <input type="number" id="csig-custom-width" value="<?php echo $job_settings['iframeWidth']; ?>" style="width: 70px;" placeholder="W" />
                                ×
                                <input type="number" id="csig-custom-height" value="<?php echo $job_settings['iframeHeight']; ?>" style="width: 70px;" placeholder="H" />
                            </div>
                            <div id="csig-viewport-size" style="font-size: 12px; color: #666;">
                                <?php echo $job_settings['iframeWidth']; ?>×<?php echo $job_settings['iframeHeight']; ?>px
                            </div>
                        </div>
                    </div>
                    
                    <div id="csig-preview" style="overflow-x: auto; max-width: 100%; border: 1px solid #ddd; border-radius: 4px; min-height: 400px; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                        <div id="csig-loading" style="text-align: center; color: #666;">
                            <div class="spinner is-active" style="float: none; margin-bottom: 10px;"></div>
                            <p><?php _e('Loading preview...', 'csig'); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
        #csig-job-interface {
            max-width: calc(100% - 320px); /* Account for sidebar */
        }
        @media (max-width: 1200px) {
            #csig-job-interface {
                max-width: 100%;
            }
        }
        #csig-preview iframe {
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        </style>
        
        <script>
        // Load iframe immediately and handle viewport changes
        document.addEventListener('DOMContentLoaded', function() {
            const previewDiv = document.getElementById('csig-preview');
            const loadingDiv = document.getElementById('csig-loading');
            const previewModeSelect = document.getElementById('csig-preview-mode');
            const customDimensions = document.getElementById('csig-custom-dimensions');
            const customWidth = document.getElementById('csig-custom-width');
            const customHeight = document.getElementById('csig-custom-height');
            const viewportSize = document.getElementById('csig-viewport-size');
            
            let currentIframe = null;
            
            function getDimensions(mode) {
                switch(mode) {
                    case 'tablet':
                        return { width: 768, height: 1024 };
                    case 'mobile':
                        return { width: 375, height: 667 };
                    case 'custom':
                        return { 
                            width: parseInt(customWidth.value) || 1200, 
                            height: parseInt(customHeight.value) || 800 
                        };
                    default: // fixed, desktop
                        return { width: 1200, height: 800 };
                }
            }
            
            function updateViewportSize(mode) {
                const dims = getDimensions(mode);
                viewportSize.textContent = dims.width + '×' + dims.height + 'px';
            }
            
            function createIframe(dimensions) {
                if (currentIframe) {
                    currentIframe.remove();
                }
                
                loadingDiv.style.display = 'block';
                
                const iframe = document.createElement('iframe');
                iframe.src = '<?php echo esc_js($url); ?>';
                iframe.style.width = dimensions.width + 'px';
                iframe.style.height = dimensions.height + 'px';
                iframe.style.border = 'none';
                iframe.style.borderRadius = '4px';
                iframe.style.display = 'none';
                
                iframe.onload = function() {
                    loadingDiv.style.display = 'none';
                    iframe.style.display = 'block';
                    
                    // Inject CSS to hide admin bar
                    try {
                        const styleElement = document.createElement('style');
                        styleElement.textContent = '#wpadminbar { display: none !important; }';
                        iframe.contentDocument.head.appendChild(styleElement);
                    } catch(e) {
                        console.log('Could not inject styles (CORS)');
                    }
                };
                
                iframe.onerror = function() {
                    loadingDiv.innerHTML = '<p style="color: #d63638;">Failed to load preview</p>';
                };
                
                previewDiv.appendChild(iframe);
                currentIframe = iframe;
                
                return iframe;
            }
            
            function handlePreviewModeChange() {
                const mode = previewModeSelect.value;
                customDimensions.style.display = mode === 'custom' ? 'flex' : 'none';
                
                const dimensions = getDimensions(mode);
                updateViewportSize(mode);
                
                // Update iframe size
                createIframe(dimensions);
            }
            
            // Initial load
            handlePreviewModeChange();
            
            // Handle changes
            previewModeSelect.addEventListener('change', handlePreviewModeChange);
            customWidth.addEventListener('input', handlePreviewModeChange);
            customHeight.addEventListener('input', handlePreviewModeChange);
            
            // Make iframe available to the capture script
            window.csigCurrentIframe = function() {
                return currentIframe;
            };
        });
        </script>
        <?php
    }
    
    public function job_settings_meta_box($post) {
        wp_nonce_field('csig_save_job_meta', 'csig_job_meta_nonce');
        
        // Get current values
        $url = get_post_meta($post->ID, '_csig_url', true);
        $selector = get_post_meta($post->ID, '_csig_selector', true) ?: '.csig-card';
        $output_format = get_post_meta($post->ID, '_csig_output_format', true) ?: 'raster';
        $image_quality = get_post_meta($post->ID, '_csig_image_quality', true) ?: 'high';
        $save_folder = get_post_meta($post->ID, '_csig_save_folder', true) ?: 'csig-images';
        $retina_support = get_post_meta($post->ID, '_csig_retina_support', true);
        $iframe_mode = get_post_meta($post->ID, '_csig_iframe_mode', true) ?: 'fixed';
        $iframe_preset = get_post_meta($post->ID, '_csig_iframe_preset', true) ?: 'desktop';
        $iframe_width = get_post_meta($post->ID, '_csig_iframe_width', true) ?: 1200;
        $iframe_height = get_post_meta($post->ID, '_csig_iframe_height', true) ?: 800;
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="csig_url"><?php _e('URL to Capture', 'csig'); ?></label></th>
                <td><input type="url" id="csig_url" name="csig_url" value="<?php echo esc_attr($url); ?>" class="regular-text" required /></td>
            </tr>
            <tr>
                <th><label for="csig_selector"><?php _e('CSS Selector', 'csig'); ?></label></th>
                <td>
                    <input type="text" id="csig_selector" name="csig_selector" value="<?php echo esc_attr($selector); ?>" class="regular-text" />
                    <p class="description"><?php _e('CSS selector for elements to capture (e.g., .csig-card, #my-id)', 'csig'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="csig_output_format"><?php _e('Output Format', 'csig'); ?></label></th>
                <td>
                    <select id="csig_output_format" name="csig_output_format">
                        <option value="raster" <?php selected($output_format, 'raster'); ?>><?php _e('Raster (PNG)', 'csig'); ?></option>
                        <option value="vector" <?php selected($output_format, 'vector'); ?>><?php _e('Vector (PDF)', 'csig'); ?></option>
                        <option value="both" <?php selected($output_format, 'both'); ?>><?php _e('Both', 'csig'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="csig_image_quality"><?php _e('Image Quality', 'csig'); ?></label></th>
                <td>
                    <select id="csig_image_quality" name="csig_image_quality">
                        <option value="low" <?php selected($image_quality, 'low'); ?>><?php _e('Low (1x)', 'csig'); ?></option>
                        <option value="high" <?php selected($image_quality, 'high'); ?>><?php _e('High (2x)', 'csig'); ?></option>
                        <option value="ultra" <?php selected($image_quality, 'ultra'); ?>><?php _e('Ultra (3x)', 'csig'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="csig_save_folder"><?php _e('Save Folder', 'csig'); ?></label></th>
                <td>
                    <input type="text" id="csig_save_folder" name="csig_save_folder" value="<?php echo esc_attr($save_folder); ?>" class="regular-text" />
                    <p class="description"><?php _e('Folder within uploads directory', 'csig'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="csig_retina_support"><?php _e('Retina Support', 'csig'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="csig_retina_support" name="csig_retina_support" value="1" <?php checked($retina_support, '1'); ?> />
                        <?php _e('Enable Retina/High-DPI support', 'csig'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Viewport Mode', 'csig'); ?></label></th>
                <td>
                    <label>
                        <input type="radio" name="csig_iframe_mode" value="fixed" <?php checked($iframe_mode, 'fixed'); ?> /> 
                        <strong><?php _e('Fixed Size', 'csig'); ?></strong>
                        <p class="description" style="margin: 5px 0 10px 0;"><?php _e('For layouts with fixed pixel dimensions. Uses a standard desktop viewport.', 'csig'); ?></p>
                    </label>
                    
                    <label>
                        <input type="radio" name="csig_iframe_mode" value="responsive" <?php checked($iframe_mode, 'responsive'); ?> /> 
                        <strong><?php _e('Responsive', 'csig'); ?></strong>
                        <p class="description" style="margin: 5px 0 10px 0;"><?php _e('For responsive layouts that change based on viewport size.', 'csig'); ?></p>
                    </label>
                    
                    <!-- Responsive options -->
                    <div id="csig-responsive-options" style="margin-left: 20px; <?php echo $iframe_mode === 'responsive' ? '' : 'display: none;'; ?>">
                        <label for="csig_iframe_preset"><?php _e('Viewport Size:', 'csig'); ?></label>
                        <select id="csig_iframe_preset" name="csig_iframe_preset" style="margin-left: 10px;">
                            <option value="desktop" <?php selected($iframe_preset, 'desktop'); ?>><?php _e('Desktop (1200×800)', 'csig'); ?></option>
                            <option value="tablet" <?php selected($iframe_preset, 'tablet'); ?>><?php _e('Tablet (768×1024)', 'csig'); ?></option>
                            <option value="mobile" <?php selected($iframe_preset, 'mobile'); ?>><?php _e('Mobile (375×667)', 'csig'); ?></option>
                            <option value="custom" <?php selected($iframe_preset, 'custom'); ?>><?php _e('Custom', 'csig'); ?></option>
                        </select>
                        
                        <div id="csig-custom-size" style="margin-top: 10px; <?php echo $iframe_preset === 'custom' ? '' : 'display: none;'; ?>">
                            <input type="number" name="csig_iframe_width" value="<?php echo esc_attr($iframe_width); ?>" style="width: 80px;" placeholder="Width" /> × 
                            <input type="number" name="csig_iframe_height" value="<?php echo esc_attr($iframe_height); ?>" style="width: 80px;" placeholder="Height" /> px
                        </div>
                    </div>
                </td>
            </tr>
        </table>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modeRadios = document.querySelectorAll('input[name="csig_iframe_mode"]');
            const responsiveOptions = document.getElementById('csig-responsive-options');
            const presetSelect = document.getElementById('csig_iframe_preset');
            const customSize = document.getElementById('csig-custom-size');
            
            function toggleResponsiveOptions() {
                const isResponsive = document.querySelector('input[name="csig_iframe_mode"]:checked').value === 'responsive';
                responsiveOptions.style.display = isResponsive ? 'block' : 'none';
            }
            
            function toggleCustomSize() {
                const isCustom = presetSelect.value === 'custom';
                customSize.style.display = isCustom ? 'block' : 'none';
            }
            
            modeRadios.forEach(radio => {
                radio.addEventListener('change', toggleResponsiveOptions);
            });
            
            presetSelect.addEventListener('change', toggleCustomSize);
        });
        </script>
        <?php
    }
    
    public function job_stats_meta_box($post) {
        ?>
        <div class="misc-pub-section">
            <strong><?php _e('Last Run:', 'csig'); ?></strong><br>
            <?php
            $last_run = get_post_meta($post->ID, '_csig_last_run', true);
            if ($last_run) {
                echo esc_html(date('M j, Y g:i A', strtotime($last_run)));
            } else {
                _e('Never', 'csig');
            }
            ?>
        </div>
        
        <div class="misc-pub-section">
            <strong><?php _e('Total Runs:', 'csig'); ?></strong><br>
            <?php
            $run_count = get_post_meta($post->ID, '_csig_run_count', true) ?: 0;
            echo esc_html($run_count);
            ?>
        </div>
        
        <div class="misc-pub-section">
            <strong><?php _e('Last Generated Files:', 'csig'); ?></strong><br>
            <?php
            $last_files = get_post_meta($post->ID, '_csig_last_files', true);
            if ($last_files && is_array($last_files)) {
                echo '<ul style="margin: 5px 0 0 0; padding-left: 15px; font-size: 12px;">';
                foreach ($last_files as $file) {
                    echo '<li><a href="' . esc_url($file) . '" target="_blank">' . esc_html(basename($file)) . '</a></li>';
                }
                echo '</ul>';
            } else {
                _e('None yet', 'csig');
            }
            ?>
        </div>
        <?php
    }
    
    public function job_actions_meta_box($post) {
        if ($post->post_status === 'publish') {
            ?>
            <div class="misc-pub-section">
                <a href="<?php echo admin_url('admin.php?page=csig-capture&job_id=' . $post->ID); ?>" class="button button-primary button-large" style="width: 100%; text-align: center; margin-bottom: 10px;">
                    <?php _e('Run This Job', 'csig'); ?>
                </a>
            </div>
            
            <div class="misc-pub-section">
                <strong><?php _e('Last Run:', 'csig'); ?></strong><br>
                <?php
                $last_run = get_post_meta($post->ID, '_csig_last_run', true);
                if ($last_run) {
                    echo esc_html(date('M j, Y g:i A', strtotime($last_run)));
                } else {
                    _e('Never', 'csig');
                }
                ?>
            </div>
            
            <div class="misc-pub-section">
                <strong><?php _e('Total Runs:', 'csig'); ?></strong><br>
                <?php
                $run_count = get_post_meta($post->ID, '_csig_run_count', true) ?: 0;
                echo esc_html($run_count);
                ?>
            </div>
            <?php
        } else {
            ?>
            <p><?php _e('Publish this job to run it.', 'csig'); ?></p>
            <?php
        }
    }
    
    public function save_job_meta($post_id) {
        if (!isset($_POST['csig_job_meta_nonce']) || !wp_verify_nonce($_POST['csig_job_meta_nonce'], 'csig_save_job_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (get_post_type($post_id) !== self::POST_TYPE) {
            return;
        }
        
        // Save meta fields
        $meta_fields = array(
            '_csig_url' => 'esc_url_raw',
            '_csig_selector' => 'sanitize_text_field',
            '_csig_output_format' => 'sanitize_text_field',
            '_csig_image_quality' => 'sanitize_text_field',
            '_csig_save_folder' => 'sanitize_text_field',
            '_csig_retina_support' => 'sanitize_text_field',
            '_csig_iframe_mode' => 'sanitize_text_field',
            '_csig_iframe_preset' => 'sanitize_text_field',
            '_csig_iframe_width' => 'intval',
            '_csig_iframe_height' => 'intval',
        );
        
        foreach ($meta_fields as $meta_key => $sanitize_function) {
            $field_name = str_replace('_csig_', 'csig_', $meta_key);
            if (isset($_POST[$field_name])) {
                $value = $sanitize_function($_POST[$field_name]);
                update_post_meta($post_id, $meta_key, $value);
            } else {
                delete_post_meta($post_id, $meta_key);
            }
        }
    }
    
    public function set_custom_columns($columns) {
        $new_columns = array(
            'cb' => $columns['cb'],
            'title' => $columns['title'],
            'url' => __('URL', 'csig'),
            'selector' => __('Selector', 'csig'),
            'format' => __('Format', 'csig'),
            'quality' => __('Quality', 'csig'),
            'last_run' => __('Last Run', 'csig'),
            'date' => $columns['date'],
        );
        return $new_columns;
    }
    
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'url':
                $url = get_post_meta($post_id, '_csig_url', true);
                if ($url) {
                    echo '<a href="' . esc_url($url) . '" target="_blank">' . esc_html(wp_parse_url($url, PHP_URL_HOST)) . '</a>';
                }
                break;
                
            case 'selector':
                $selector = get_post_meta($post_id, '_csig_selector', true);
                echo '<code>' . esc_html($selector ?: '.csig-card') . '</code>';
                break;
                
            case 'format':
                $format = get_post_meta($post_id, '_csig_output_format', true);
                echo esc_html(ucfirst($format ?: 'raster'));
                break;
                
            case 'quality':
                $quality = get_post_meta($post_id, '_csig_image_quality', true);
                echo esc_html(ucfirst($quality ?: 'high'));
                break;
                
            case 'last_run':
                $last_run = get_post_meta($post_id, '_csig_last_run', true);
                if ($last_run) {
                    echo esc_html(date('M j, Y', strtotime($last_run)));
                } else {
                    echo '<em>' . __('Never', 'csig') . '</em>';
                }
                break;
        }
    }
    
    public function add_run_job_action($actions, $post) {
        if ($post->post_type === self::POST_TYPE && $post->post_status === 'publish') {
            $actions['run_job'] = '<a href="' . admin_url('admin.php?page=csig-capture&job_id=' . $post->ID) . '">' . __('Run Job', 'csig') . '</a>';
        }
        return $actions;
    }
    
    // Helper methods for other classes to use
    public static function get_job_settings($job_id) {
        $quality_map = array('low' => 1, 'high' => 2, 'ultra' => 3);
        
        $url = get_post_meta($job_id, '_csig_url', true);
        $selector = get_post_meta($job_id, '_csig_selector', true) ?: '.csig-card';
        $output_format = get_post_meta($job_id, '_csig_output_format', true) ?: 'raster';
        $image_quality = get_post_meta($job_id, '_csig_image_quality', true) ?: 'high';
        $save_folder = get_post_meta($job_id, '_csig_save_folder', true) ?: 'csig-images';
        $retina_support = get_post_meta($job_id, '_csig_retina_support', true) === '1';
        $iframe_mode = get_post_meta($job_id, '_csig_iframe_mode', true) ?: 'fixed';
        $iframe_preset = get_post_meta($job_id, '_csig_iframe_preset', true) ?: 'desktop';
        $iframe_width = get_post_meta($job_id, '_csig_iframe_width', true) ?: 1200;
        $iframe_height = get_post_meta($job_id, '_csig_iframe_height', true) ?: 800;
        
        // Determine iframe dimensions based on mode and preset
        if ($iframe_mode === 'fixed') {
            $final_width = 1200;
            $final_height = 800;
        } else {
            switch ($iframe_preset) {
                case 'tablet':
                    $final_width = 768;
                    $final_height = 1024;
                    break;
                case 'mobile':
                    $final_width = 375;
                    $final_height = 667;
                    break;
                case 'custom':
                    $final_width = $iframe_width;
                    $final_height = $iframe_height;
                    break;
                default: // desktop
                    $final_width = 1200;
                    $final_height = 800;
                    break;
            }
        }
        
        return array(
            'url' => $url,
            'selector' => $selector,
            'outputFormat' => $output_format,
            'saveFolder' => $save_folder,
            'imageQuality' => $image_quality,
            'pixelRatio' => $quality_map[$image_quality] ?? 2,
            'retinaSupport' => $retina_support,
            'iframeWidth' => $final_width,
            'iframeHeight' => $final_height,
            'iframeMode' => $iframe_mode,
            'iframePreset' => $iframe_preset,
        );
    }
    
    public static function update_job_stats($job_id, $generated_files = array()) {
        // Update last run time
        update_post_meta($job_id, '_csig_last_run', current_time('mysql'));
        
        // Increment run count
        $run_count = get_post_meta($job_id, '_csig_run_count', true) ?: 0;
        update_post_meta($job_id, '_csig_run_count', $run_count + 1);
        
        // Store last generated files
        if (!empty($generated_files)) {
            update_post_meta($job_id, '_csig_last_files', $generated_files);
        }
    }
}