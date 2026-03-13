<?php
/**
 * Feedback Email Handler for WC Order Status Tracker
 * 
 * Handles scheduling and sending of feedback emails 7 days after order completion.
 * Only sends to orders created after the feature activation date.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC_OST_Feedback class
 */
class WC_OST_Feedback {

    /**
     * Option name for storing activation timestamp
     */
    const ACTIVATION_OPTION = 'wc_ost_feedback_activation_time';

    /**
     * Option name for feedback enabled status
     */
    const ENABLED_OPTION = 'wc_ost_feedback_enabled';

    /**
     * Cron hook name
     */
    const CRON_HOOK = 'wc_ost_send_feedback_email';

    /**
     * Meta key to track if feedback email was scheduled
     */
    const SCHEDULED_META_KEY = '_wc_ost_feedback_scheduled';

    /**
     * Meta key to track if feedback email was sent
     */
    const SENT_META_KEY = '_wc_ost_feedback_sent';

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Get instance
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
        // Schedule feedback email when order is completed
        add_action('woocommerce_order_status_completed', array($this, 'schedule_feedback_email'), 10, 1);
        
        // Hook for the cron job
        add_action(self::CRON_HOOK, array($this, 'send_feedback_email'), 10, 1);

        // Add settings (after WooCommerce is loaded)
        add_action('woocommerce_loaded', array($this, 'init_settings'));
    }

    /**
     * Initialize settings page
     */
    public function init_settings() {
        add_filter('woocommerce_get_settings_pages', array($this, 'add_settings_page'));
    }

    /**
     * Check if feedback feature is enabled
     */
    public static function is_enabled() {
        return get_option(self::ENABLED_OPTION, 'no') === 'yes';
    }

    /**
     * Enable the feedback feature
     */
    public static function enable() {
        update_option(self::ENABLED_OPTION, 'yes');
        // Store activation time only if not already set
        if (!get_option(self::ACTIVATION_OPTION)) {
            update_option(self::ACTIVATION_OPTION, time());
        }
    }

    /**
     * Disable the feedback feature
     */
    public static function disable() {
        update_option(self::ENABLED_OPTION, 'no');
    }

    /**
     * Get activation timestamp
     */
    public static function get_activation_time() {
        return get_option(self::ACTIVATION_OPTION, 0);
    }

    /**
     * Schedule feedback email when order is marked as completed
     * 
     * @param int $order_id Order ID
     */
    public function schedule_feedback_email($order_id) {
        // Check if feature is enabled
        if (!self::is_enabled()) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Check if email was already scheduled or sent
        if ($order->get_meta(self::SCHEDULED_META_KEY) || $order->get_meta(self::SENT_META_KEY)) {
            return;
        }

        // Check if order was created after activation
        $activation_time = self::get_activation_time();
        $order_date = $order->get_date_created()->getTimestamp();
        
        if ($order_date < $activation_time) {
            // Order is from before activation, skip
            return;
        }

        // Schedule email for 7 days from now
        $send_time = time() + (7 * DAY_IN_SECONDS);
        wp_schedule_single_event($send_time, self::CRON_HOOK, array($order_id));

        // Mark as scheduled
        $order->update_meta_data(self::SCHEDULED_META_KEY, time());
        $order->save();
    }

    /**
     * Send the feedback email
     * 
     * @param int $order_id Order ID
     */
    public function send_feedback_email($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Check if already sent
        if ($order->get_meta(self::SENT_META_KEY)) {
            return;
        }

        // Verify order is still completed
        if ($order->get_status() !== 'completed') {
            return;
        }

        $customer_email = $order->get_billing_email();
        $customer_name = $order->get_billing_first_name();
        $order_number = $order->get_order_number();

        if (empty($customer_email)) {
            return;
        }

        // Send the email
        $sent = $this->send_email($customer_email, $customer_name, $order_number);

        if ($sent) {
            $order->update_meta_data(self::SENT_META_KEY, time());
            $order->save();
        }
    }

    /**
     * Send feedback email to customer
     * 
     * @param string $to Email address
     * @param string $customer_name Customer first name
     * @param string $order_number Order number
     * @return bool Whether email was sent
     */
    public function send_email($to, $customer_name, $order_number) {
        $subject = sprintf(
            /* translators: %s: Site name */
            __('How was your experience with %s? Share your feedback!', 'wc-order-status-tracker'),
            get_bloginfo('name')
        );

        $feedback_url = 'https://pendragonpeptides.co.uk/feedback-submission/';
        $site_name = get_bloginfo('name');
        $site_url = get_home_url();

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        );

        $message = $this->get_email_template($customer_name, $order_number, $feedback_url, $site_name, $site_url);

        return wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Get the HTML email template
     * 
     * @param string $customer_name Customer name
     * @param string $order_number Order number
     * @param string $feedback_url Feedback form URL
     * @param string $site_name Site name
     * @param string $site_url Site URL
     * @return string HTML email content
     */
    public function get_email_template($customer_name, $order_number, $feedback_url, $site_name, $site_url) {
        $greeting = $customer_name ? sprintf(__('Hi %s,', 'wc-order-status-tracker'), esc_html($customer_name)) : __('Hi there,', 'wc-order-status-tracker');
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php _e('We value your feedback', 'wc-order-status-tracker'); ?></title>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                    background-color: #f5f5f5;
                    line-height: 1.6;
                }
                .email-wrapper {
                    max-width: 600px;
                    margin: 0 auto;
                    background-color: #ffffff;
                }
                .email-header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    padding: 40px 30px;
                    text-align: center;
                }
                .email-header h1 {
                    color: #ffffff;
                    margin: 0;
                    font-size: 28px;
                    font-weight: 600;
                }
                .email-body {
                    padding: 40px 30px;
                }
                .greeting {
                    font-size: 18px;
                    color: #333333;
                    margin-bottom: 20px;
                }
                .content {
                    color: #555555;
                    font-size: 16px;
                    margin-bottom: 25px;
                }
                .order-info {
                    background-color: #f8f9fa;
                    border-left: 4px solid #667eea;
                    padding: 15px 20px;
                    margin: 25px 0;
                    border-radius: 0 4px 4px 0;
                }
                .order-info strong {
                    color: #333333;
                }
                .cta-container {
                    text-align: center;
                    margin: 35px 0;
                }
                .cta-button {
                    display: inline-block;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: #ffffff !important;
                    text-decoration: none;
                    padding: 15px 40px;
                    border-radius: 50px;
                    font-size: 16px;
                    font-weight: 600;
                    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
                    transition: all 0.3s ease;
                }
                .cta-button:hover {
                    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
                    transform: translateY(-2px);
                }
                .feedback-reasons {
                    background-color: #f8f9fa;
                    padding: 25px;
                    border-radius: 8px;
                    margin: 25px 0;
                }
                .feedback-reasons h3 {
                    margin-top: 0;
                    color: #333333;
                    font-size: 18px;
                }
                .feedback-reasons ul {
                    margin: 15px 0 0 0;
                    padding-left: 20px;
                    color: #555555;
                }
                .feedback-reasons li {
                    margin-bottom: 10px;
                }
                .divider {
                    border: none;
                    border-top: 1px solid #e0e0e0;
                    margin: 30px 0;
                }
                .closing {
                    color: #555555;
                    font-size: 16px;
                }
                .signature {
                    margin-top: 25px;
                    color: #333333;
                    font-weight: 500;
                }
                .email-footer {
                    background-color: #f8f9fa;
                    padding: 25px 30px;
                    text-align: center;
                    border-top: 1px solid #e0e0e0;
                }
                .email-footer p {
                    margin: 5px 0;
                    color: #888888;
                    font-size: 14px;
                }
                .email-footer a {
                    color: #667eea;
                    text-decoration: none;
                }
                @media only screen and (max-width: 600px) {
                    .email-body {
                        padding: 30px 20px;
                    }
                    .email-header {
                        padding: 30px 20px;
                    }
                    .email-header h1 {
                        font-size: 24px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="email-wrapper">
                <div class="email-header">
                    <h1><?php _e('We Value Your Feedback', 'wc-order-status-tracker'); ?></h1>
                </div>
                
                <div class="email-body">
                    <p class="greeting"><?php echo $greeting; ?></p>
                    
                    <p class="content">
                        <?php 
                        printf(
                            /* translators: %s: Site name */
                            __('We hope you are enjoying your recent purchase from %s! It has been a week since your order was delivered, and we would love to hear about your experience.', 'wc-order-status-tracker'),
                            esc_html($site_name)
                        ); 
                        ?>
                    </p>
                    
                    <div class="order-info">
                        <strong><?php _e('Order:', 'wc-order-status-tracker'); ?></strong> #<?php echo esc_html($order_number); ?>
                    </div>
                    
                    <p class="content">
                        <?php _e('Your feedback helps us improve our products and services for customers like you. It only takes a minute to share your thoughts, and it makes a real difference!', 'wc-order-status-tracker'); ?>
                    </p>
                    
                    <div class="cta-container">
                        <a href="<?php echo esc_url($feedback_url); ?>" class="cta-button">
                            <?php _e('Share Your Feedback', 'wc-order-status-tracker'); ?>
                        </a>
                    </div>
                    
                    <div class="feedback-reasons">
                        <h3><?php _e('What we would love to know:', 'wc-order-status-tracker'); ?></h3>
                        <ul>
                            <li><?php _e('How satisfied are you with your purchase?', 'wc-order-status-tracker'); ?></li>
                            <li><?php _e('How was your shopping experience?', 'wc-order-status-tracker'); ?></li>
                            <li><?php _e('How likely are you to recommend us to others?', 'wc-order-status-tracker'); ?></li>
                            <li><?php _e('Any suggestions for how we can improve?', 'wc-order-status-tracker'); ?></li>
                        </ul>
                    </div>
                    
                    <hr class="divider">
                    
                    <p class="closing">
                        <?php _e('Thank you for being a valued customer. We appreciate your time and look forward to serving you again!', 'wc-order-status-tracker'); ?>
                    </p>
                    
                    <p class="signature">
                        <?php 
                        printf(
                            /* translators: %s: Site name */
                            __('Warm regards,<br>The %s Team', 'wc-order-status-tracker'),
                            esc_html($site_name)
                        ); 
                        ?>
                    </p>
                </div>
                
                <div class="email-footer">
                    <p><?php echo esc_html($site_name); ?></p>
                    <p><a href="<?php echo esc_url($site_url); ?>"><?php echo esc_url($site_url); ?></a></p>
                    <p style="font-size: 12px; margin-top: 15px;">
                        <?php _e('You received this email because you made a purchase from our store.', 'wc-order-status-tracker'); ?>
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Manually send a test feedback email
     * 
     * @param string $to Email address to send test to
     * @param string $customer_name Test customer name
     * @param string $order_number Test order number
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function send_test_email($to, $customer_name = 'John', $order_number = 'TEST-123') {
        if (!is_email($to)) {
            return new WP_Error('invalid_email', __('Invalid email address.', 'wc-order-status-tracker'));
        }

        $sent = $this->send_email($to, $customer_name, $order_number);

        if ($sent) {
            return true;
        } else {
            return new WP_Error('send_failed', __('Failed to send email. Please check your WordPress email settings.', 'wc-order-status-tracker'));
        }
    }

    /**
     * Add settings page to WooCommerce
     * 
     * @param array $settings Array of settings page instances
     * @return array Modified settings array
     */
    public function add_settings_page($settings) {
        $settings[] = include WC_OST_PLUGIN_DIR . 'includes/class-wc-ost-settings.php';
        return $settings;
    }
}

// Initialize
WC_OST_Feedback::get_instance();
