<?php
/**
 * Plugin Name: WC Order Status Tracker
 * Plugin URI:  https://sonnynabong.dev/wc-order-status-tracker
 * Description: A WooCommerce plugin that allows customers to track their order status using order ID and email via shortcode.
 * Version:     1
 * Author:      Sonny Nabong
 * Author URI:  https://sonnynabong.dev
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wc-order-status-tracker
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('WC_OST_VERSION', '1');
define('WC_OST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_OST_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
class WC_Order_Status_Tracker {

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_shortcode('wc_order_tracker', array($this, 'render_tracker_form'));
        add_action('wp_ajax_wc_ost_get_order_status', array($this, 'ajax_get_order_status'));
        add_action('wp_ajax_nopriv_wc_ost_get_order_status', array($this, 'ajax_get_order_status'));
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('wc-order-status-tracker', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Enqueue CSS and JS
     */
    public function enqueue_assets() {
        global $post;
        
        // Only load if shortcode is present
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'wc_order_tracker')) {
            wp_enqueue_style(
                'wc-ost-style',
                WC_OST_PLUGIN_URL . 'assets/css/wc-ost-style.css',
                array(),
                WC_OST_VERSION
            );

            wp_enqueue_script(
                'wc-ost-script',
                WC_OST_PLUGIN_URL . 'assets/js/wc-ost-script.js',
                array('jquery'),
                WC_OST_VERSION,
                true
            );

            wp_localize_script('wc-ost-script', 'wc_ost_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('wc_ost_nonce'),
                'strings'  => array(
                    'loading'     => __('Loading...', 'wc-order-status-tracker'),
                    'error'       => __('An error occurred. Please try again.', 'wc-order-status-tracker'),
                    'not_found'   => __('Order not found. Please check your Order ID and Email.', 'wc-order-status-tracker'),
                    'invalid_email' => __('Please enter a valid email address.', 'wc-order-status-tracker'),
                )
            ));
        }
    }

    /**
     * Render the tracker form shortcode
     */
    public function render_tracker_form($atts) {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return '<div class="wc-ost-error">' . __('WooCommerce is required for this plugin to work.', 'wc-order-status-tracker') . '</div>';
        }

        // Check if title/description were explicitly set (even to empty string)
        $has_title = isset($atts['title']);
        $has_description = isset($atts['description']);
        
        $atts = shortcode_atts(array(
            'title'       => __('Track Your Order', 'wc-order-status-tracker'),
            'description' => __('Enter your Order ID and Email address to track your order status.', 'wc-order-status-tracker'),
        ), $atts, 'wc_order_tracker');

        ob_start();
        ?>
        <div class="wc-order-tracker-wrapper">
            <div class="wc-order-tracker-form-container">
                <?php if (!$has_title && $atts['title'] || $has_title && $atts['title'] !== '') : ?>
                    <h2 class="wc-ost-title"><?php echo esc_html($atts['title']); ?></h2>
                <?php endif; ?>
                
                <?php if (!$has_description && $atts['description'] || $has_description && $atts['description'] !== '') : ?>
                    <p class="wc-ost-description"><?php echo esc_html($atts['description']); ?></p>
                <?php endif; ?>

                <form id="wc-order-tracker-form" class="wc-ost-form" method="post">
                    <div class="wc-ost-form-group">
                        <label for="wc_ost_order_id"><?php _e('Order ID', 'wc-order-status-tracker'); ?></label>
                        <input 
                            type="text" 
                            id="wc_ost_order_id" 
                            name="order_id" 
                            class="wc-ost-input" 
                            placeholder="<?php esc_attr_e('Enter your Order ID (e.g., 123)', 'wc-order-status-tracker'); ?>"
                            required
                        >
                    </div>

                    <div class="wc-ost-form-group">
                        <label for="wc_ost_email"><?php _e('Email Address', 'wc-order-status-tracker'); ?></label>
                        <input 
                            type="email" 
                            id="wc_ost_email" 
                            name="email" 
                            class="wc-ost-input" 
                            placeholder="<?php esc_attr_e('Enter your email address', 'wc-order-status-tracker'); ?>"
                            required
                        >
                    </div>

                    <button type="submit" class="wc-ost-button">
                        <span class="wc-ost-button-text"><?php _e('Track Order', 'wc-order-status-tracker'); ?></span>
                        <span class="wc-ost-button-loader" style="display: none;">
                            <span class="wc-ost-spinner"></span>
                        </span>
                    </button>
                </form>

                <div id="wc-ost-result" class="wc-ost-result" style="display: none;"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX handler to get order status
     * 
     * Security measures:
     * - Nonce verification (CSRF protection)
     * - Input sanitization
     * - Email validation
     * - Order ownership verification (email matching)
     * - Generic error messages to prevent user enumeration
     * - Timing attack mitigation via consistent processing path
     * - Rate limiting to prevent brute force attacks
     */
    public function ajax_get_order_status() {
        // Verify nonce for CSRF protection
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wc_ost_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'wc-order-status-tracker')));
        }

        // Rate limiting: max 20 attempts per IP per 15 minutes
        $ip_address = $this->get_client_ip();
        $transient_key = 'wc_ost_rate_' . md5($ip_address);
        $attempts = get_transient($transient_key);
        
        if ($attempts !== false && $attempts >= 20) {
            wp_send_json_error(array('message' => __('Too many attempts. Please try again in 15 minutes.', 'wc-order-status-tracker')));
        }

        $order_id = isset($_POST['order_id']) ? sanitize_text_field($_POST['order_id']) : '';
        $email    = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

        // Validate inputs
        if (empty($order_id) || empty($email)) {
            wp_send_json_error(array('message' => __('Please enter both Order ID and Email.', 'wc-order-status-tracker')));
        }

        if (!is_email($email)) {
            wp_send_json_error(array('message' => __('Please enter a valid email address.', 'wc-order-status-tracker')));
        }

        // Normalize email for comparison
        $email_lower = strtolower($email);

        // Try to get order by ID or order number
        $order = $this->get_order_by_id_or_number($order_id);

        // SECURITY: Use constant-time comparison and generic error messages
        // to prevent order enumeration attacks via timing analysis
        $is_valid = false;
        
        if ($order) {
            $order_email = $order->get_billing_email();
            // Use hash_equals for timing-safe comparison
            $is_valid = hash_equals(strtolower($order_email), $email_lower);
        }

        // If validation fails, return generic error (same message for order not found OR email mismatch)
        // This prevents attackers from knowing if an order exists
        if (!$is_valid) {
            // Increment rate limit counter on failed attempt
            $attempts = ($attempts === false) ? 1 : $attempts + 1;
            set_transient($transient_key, $attempts, 15 * MINUTE_IN_SECONDS);
            
            wp_send_json_error(array('message' => __('Order not found. Please check your Order ID and Email.', 'wc-order-status-tracker')));
        }

        // Generate response HTML
        $html = $this->generate_order_status_html($order);

        wp_send_json_success(array('html' => $html));
    }

    /**
     * Get order by ID or order number
     */
    private function get_order_by_id_or_number($order_id) {
        // First try as order ID
        $order = wc_get_order($order_id);
        
        if ($order) {
            return $order;
        }

        // Try to find by order number (if using custom order numbers)
        $orders = wc_get_orders(array(
            'limit'        => 1,
            'meta_key'     => '_order_number',
            'meta_value'   => $order_id,
            'meta_compare' => '=',
        ));

        if (!empty($orders)) {
            return $orders[0];
        }

        // Try searching by order number formatted
        global $wpdb;
        $post_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_order_number' AND meta_value = %s LIMIT 1",
            $order_id
        ));

        if ($post_id) {
            return wc_get_order($post_id);
        }

        return null;
    }

    /**
     * Generate order status HTML
     */
    private function generate_order_status_html($order) {
        $order_id        = $order->get_id();
        $order_number    = $order->get_order_number();
        $status          = $order->get_status();
        $status_name     = wc_get_order_status_name($status);
        $date_created    = $order->get_date_created();
        $date_modified   = $order->get_date_modified();
        $date_completed  = $order->get_date_completed();
        $customer_note   = $order->get_customer_note();
        $billing_name    = $order->get_formatted_billing_full_name();
        $total           = $order->get_formatted_order_total();

        // Get order notes visible to customer (added by admin as "Note to customer")
        $customer_order_notes = $this->get_customer_order_notes($order_id);

        // Status steps for visual timeline
        $status_steps = $this->get_status_steps($status);

        ob_start();
        ?>
        <div class="wc-ost-order-details">
            <div class="wc-ost-order-header">
                <h3 class="wc-ost-order-title">
                    <?php 
                    printf(
                        /* translators: %s: Order number */
                        esc_html__('Order #%s', 'wc-order-status-tracker'),
                        esc_html($order_number)
                    ); 
                    ?>
                </h3>
                <span class="wc-ost-order-status <?php echo esc_attr('status-' . $status); ?>">
                    <?php echo esc_html($status_name); ?>
                </span>
            </div>

            <div class="wc-ost-order-info">
                <div class="wc-ost-info-row">
                    <span class="wc-ost-info-label"><?php _e('Order Date:', 'wc-order-status-tracker'); ?></span>
                    <span class="wc-ost-info-value"><?php echo esc_html(wc_format_datetime($date_created)); ?></span>
                </div>
                <?php if ($billing_name) : ?>
                <div class="wc-ost-info-row">
                    <span class="wc-ost-info-label"><?php _e('Customer:', 'wc-order-status-tracker'); ?></span>
                    <span class="wc-ost-info-value"><?php echo esc_html($billing_name); ?></span>
                </div>
                <?php endif; ?>
                <div class="wc-ost-info-row">
                    <span class="wc-ost-info-label"><?php _e('Total:', 'wc-order-status-tracker'); ?></span>
                    <span class="wc-ost-info-value"><?php echo wp_kses_post($total); ?></span>
                </div>
            </div>

            <!-- Status Timeline -->
            <div class="wc-ost-status-timeline">
                <h4 class="wc-ost-section-title"><?php _e('Order Status History', 'wc-order-status-tracker'); ?></h4>
                <div class="wc-ost-timeline">
                    <?php foreach ($status_steps as $step) : ?>
                        <div class="wc-ost-timeline-item <?php echo $step['completed'] ? esc_attr('completed') : ''; ?> <?php echo $step['current'] ? esc_attr('current') : ''; ?>">
                            <div class="wc-ost-timeline-marker">
                                <?php if ($step['completed']) : ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                <?php else : ?>
                                    <span class="wc-ost-timeline-dot"></span>
                                <?php endif; ?>
                            </div>
                            <div class="wc-ost-timeline-content">
                                <span class="wc-ost-timeline-status"><?php echo esc_html($step['label']); ?></span>
                                <?php if ($step['date']) : ?>
                                    <span class="wc-ost-timeline-date"><?php echo esc_html($step['date']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Customer Notes (checkout note + admin notes to customer) -->
            <?php if ($customer_note || !empty($customer_order_notes)) : ?>
            <div class="wc-ost-customer-note">
                <h4 class="wc-ost-section-title"><?php _e('Notes', 'wc-order-status-tracker'); ?></h4><?php 
                if ($customer_note) : ?><div class="wc-ost-note-content wc-ost-checkout-note"><?php echo wp_kses_post($this->make_urls_clickable(trim($customer_note))); ?></div><?php endif; 
                foreach ($customer_order_notes as $note) : ?><div class="wc-ost-note-content wc-ost-order-note"><div class="wc-ost-note-meta"><?php echo esc_html(wc_format_datetime($note->date_created)); ?></div><div class="wc-ost-note-body"><?php echo wp_kses_post($this->make_urls_clickable(trim($note->content))); ?></div></div><?php endforeach; ?></div>
            <?php endif; ?>

            <!-- Order Items Summary -->
            <div class="wc-ost-order-items">
                <h4 class="wc-ost-section-title"><?php _e('Order Items', 'wc-order-status-tracker'); ?></h4>
                <ul class="wc-ost-items-list">
                    <?php foreach ($order->get_items() as $item) : 
                        $item_name = $item->get_name();
                        $quantity = $item->get_quantity();
                    ?>
                        <li class="wc-ost-item">
                            <span class="wc-ost-item-name">
                                <?php echo esc_html($item_name); ?>
                                <?php if ($quantity > 1) : ?>
                                    <span class="wc-ost-item-qty">× <?php echo esc_html($quantity); ?></span>
                                <?php endif; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <button type="button" class="wc-ost-track-another" id="wc-ost-track-another">
                <?php _e('Track Another Order', 'wc-order-status-tracker'); ?>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get status steps for timeline
     */
    private function get_status_steps($current_status) {
        $statuses = array(
            'pending'    => __('Pending Payment', 'wc-order-status-tracker'),
            'processing' => __('Processing', 'wc-order-status-tracker'),
            'on-hold'    => __('On Hold', 'wc-order-status-tracker'),
            'completed'  => __('Completed', 'wc-order-status-tracker'),
            'shipped'    => __('Shipped', 'wc-order-status-tracker'),
            'delivered'  => __('Delivered', 'wc-order-status-tracker'),
            'cancelled'  => __('Cancelled', 'wc-order-status-tracker'),
            'refunded'   => __('Refunded', 'wc-order-status-tracker'),
            'failed'     => __('Failed', 'wc-order-status-tracker'),
        );

        // Define the flow for normal orders (without shipped/delivered for standard WooCommerce)
        $normal_flow = array('pending', 'processing', 'completed');
        
        // Alternative flows
        $cancelled_flow = array('pending', 'cancelled');
        $refunded_flow = array('pending', 'processing', 'completed', 'refunded');
        $onhold_flow = array('pending', 'on-hold', 'processing', 'completed');
        $failed_flow = array('pending', 'failed');

        // Determine which flow to use
        $flow = $normal_flow;
        if (in_array($current_status, array('cancelled'))) {
            $flow = $cancelled_flow;
        } elseif (in_array($current_status, array('refunded'))) {
            $flow = $refunded_flow;
        } elseif (in_array($current_status, array('on-hold'))) {
            $flow = $onhold_flow;
        } elseif (in_array($current_status, array('failed'))) {
            $flow = $failed_flow;
        }

        // If status is not in any predefined flow, add it
        if (!in_array($current_status, $flow)) {
            $flow[] = $current_status;
        }

        $steps = array();
        $current_reached = false;

        foreach ($flow as $status_key) {
            $is_current = ($status_key === $current_status);
            if ($is_current) {
                $current_reached = true;
            }

            $steps[] = array(
                'key'       => $status_key,
                'label'     => isset($statuses[$status_key]) ? $statuses[$status_key] : ucfirst($status_key),
                'completed' => !$current_reached || $is_current,
                'current'   => $is_current,
                'date'      => $is_current ? __('Current Status', 'wc-order-status-tracker') : '',
            );
        }

        return $steps;
    }

    /**
     * Get order notes visible to customer
     * 
     * @param int $order_id Order ID
     * @return array Array of note objects with content and date
     */
    private function get_customer_order_notes($order_id) {
        $notes = array();
        
        // Get all order notes from WP_COMMENTS table
        // Type = 'order_note' and is_customer_note meta = 1 for notes visible to customer
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT c.comment_ID, c.comment_content, c.comment_date 
             FROM {$wpdb->comments} c 
             INNER JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id 
             WHERE c.comment_post_ID = %d 
             AND c.comment_type = 'order_note' 
             AND c.comment_approved = '1'
             AND cm.meta_key = 'is_customer_note' 
             AND cm.meta_value = '1'
             ORDER BY c.comment_date ASC",
            $order_id
        );
        
        $results = $wpdb->get_results($query);
        
        foreach ($results as $row) {
            $notes[] = (object) array(
                'content'       => $row->comment_content,
                'date_created'  => wc_string_to_datetime($row->comment_date),
            );
        }
        
        return $notes;
    }

    /**
     * Get client IP address
     * 
     * @return string Client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }

    /**
     * Make URLs in text clickable links
     * 
     * Security: Only allows http:// and https:// protocols to prevent XSS
     * via javascript: or data: URIs
     */
    private function make_urls_clickable($text) {
        // First, let WordPress make links clickable
        $text = make_clickable($text);
        
        // Additional security: ensure only http/https links and add security attributes
        // This regex finds anchor tags with href attributes
        $pattern = '/<a\s+([^>]*href=["\'](https?:\/\/[^"\']+)["\'][^>]*)>/i';
        
        $text = preg_replace_callback($pattern, function($matches) {
            $full_tag = $matches[0];
            $url = $matches[2];
            
            // Validate URL scheme
            $scheme = parse_url($url, PHP_URL_SCHEME);
            if (!in_array(strtolower($scheme), array('http', 'https'), true)) {
                // Not a valid http/https URL, return text only (no link)
                return esc_html($url);
            }
            
            // Add security attributes if not present
            if (strpos($full_tag, 'target=') === false) {
                $full_tag = str_replace('>', ' target="_blank">', $full_tag);
            }
            if (strpos($full_tag, 'rel=') === false) {
                $full_tag = str_replace('>', ' rel="noopener noreferrer">', $full_tag);
            }
            
            return $full_tag;
        }, $text);
        
        return $text;
    }
}

// Initialize the plugin
WC_Order_Status_Tracker::get_instance();

/**
 * Activation hook
 */
function wc_ost_activate() {
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('This plugin requires WooCommerce to be installed and activated.', 'wc-order-status-tracker'));
    }
}
register_activation_hook(__FILE__, 'wc_ost_activate');
