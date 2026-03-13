<?php
/**
 * Feedback Settings Page for WC Order Status Tracker
 * 
 * Provides admin interface to enable/disable feedback emails and send test emails.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC_OST_Settings class
 */
class WC_OST_Settings extends WC_Settings_Page {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'wc_ost_feedback';
        $this->label = __('Feedback Emails', 'wc-order-status-tracker');

        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_page'), 20);
        add_action('woocommerce_settings_' . $this->id, array($this, 'output'));
        add_action('woocommerce_settings_save_' . $this->id, array($this, 'save'));
        add_action('woocommerce_sections_' . $this->id, array($this, 'output_sections'));
        
        // Handle test email submission
        add_action('admin_init', array($this, 'handle_test_email'));
        
        // Add admin notices
        add_action('admin_notices', array($this, 'show_admin_notices'));
    }

    /**
     * Get sections
     */
    public function get_sections() {
        $sections = array(
            '' => __('General Settings', 'wc-order-status-tracker'),
            'test' => __('Send Test Email', 'wc-order-status-tracker'),
        );
        return $sections;
    }

    /**
     * Get settings array
     */
    public function get_settings($current_section = '') {
        if ($current_section === 'test') {
            return array();
        }

        $settings = array(
            array(
                'title' => __('Feedback Email Settings', 'wc-order-status-tracker'),
                'type' => 'title',
                'desc' => sprintf(
                    /* translators: %s: Date string */
                    __('Configure automated feedback emails that are sent 7 days after an order is marked as completed. Feature activation date: %s', 'wc-order-status-tracker'),
                    WC_OST_Feedback::get_activation_time() ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), WC_OST_Feedback::get_activation_time()) : __('Not yet activated', 'wc-order-status-tracker')
                ),
                'id' => 'wc_ost_feedback_settings',
            ),
            array(
                'title' => __('Enable Feedback Emails', 'wc-order-status-tracker'),
                'desc' => __('Automatically send feedback request emails 7 days after order completion', 'wc-order-status-tracker'),
                'id' => WC_OST_Feedback::ENABLED_OPTION,
                'default' => 'no',
                'type' => 'checkbox',
                'autoload' => false,
            ),
            array(
                'title' => __('Feedback Form URL', 'wc-order-status-tracker'),
                'desc' => __('The URL where customers can submit their feedback', 'wc-order-status-tracker'),
                'id' => 'wc_ost_feedback_url',
                'default' => 'https://pendragonpeptides.co.uk/feedback-submission/',
                'type' => 'text',
                'css' => 'width: 400px;',
            ),
            array(
                'type' => 'sectionend',
                'id' => 'wc_ost_feedback_settings',
            ),
            array(
                'title' => __('How It Works', 'wc-order-status-tracker'),
                'type' => 'title',
                'desc' => $this->get_how_it_works_description(),
                'id' => 'wc_ost_feedback_how_it_works',
            ),
            array(
                'type' => 'sectionend',
                'id' => 'wc_ost_feedback_how_it_works',
            ),
        );

        return apply_filters('woocommerce_' . $this->id . '_settings', $settings, $current_section);
    }

    /**
     * Get "How It Works" description
     */
    private function get_how_it_works_description() {
        return '<p>' . __('The feedback email system works as follows:', 'wc-order-status-tracker') . '</p>' .
            '<ol>' .
            '<li>' . __('<strong>Activation:</strong> When you enable this feature, the current date/time is recorded. Only orders placed after this date will receive feedback emails.', 'wc-order-status-tracker') . '</li>' .
            '<li>' . __('<strong>Trigger:</strong> When an order is marked as "Completed", a feedback email is automatically scheduled for 7 days later.', 'wc-order-status-tracker') . '</li>' .
            '<li>' . __('<strong>Delivery:</strong> After 7 days, the email is sent automatically via WordPress cron.', 'wc-order-status-tracker') . '</li>' .
            '<li>' . __('<strong>One-time:</strong> Each order receives only one feedback email. The system tracks which orders have been sent emails to prevent duplicates.', 'wc-order-status-tracker') . '</li>' .
            '</ol>' .
            '<p><strong>' . __('Important:', 'wc-order-status-tracker') . '</strong> ' . __('Old orders (placed before activation) will NOT receive emails. This prevents spamming customers with feedback requests for past orders when you first enable this feature.', 'wc-order-status-tracker') . '</p>';
    }

    /**
     * Output the settings page
     */
    public function output() {
        global $current_section;

        if ($current_section === 'test') {
            $this->output_test_email_section();
        } else {
            $settings = $this->get_settings($current_section);
            WC_Admin_Settings::output_fields($settings);
        }
    }

    /**
     * Output the test email section
     */
    private function output_test_email_section() {
        ?>
        <h2><?php _e('Send Test Feedback Email', 'wc-order-status-tracker'); ?></h2>
        <p><?php _e('Use this form to send a test feedback email to yourself or a team member. This helps you preview how the email will look to customers.', 'wc-order-status-tracker'); ?></p>
        
        <form method="post" action="">
            <?php wp_nonce_field('wc_ost_send_test_email', 'wc_ost_test_email_nonce'); ?>
            <input type="hidden" name="wc_ost_action" value="send_test_email">
            
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                        <label for="test_email_to"><?php _e('Send To', 'wc-order-status-tracker'); ?></label>
                    </th>
                    <td>
                        <input type="email" 
                               name="test_email_to" 
                               id="test_email_to" 
                               class="regular-text" 
                               value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>"
                               required>
                        <p class="description">
                            <?php _e('The email address to send the test to.', 'wc-order-status-tracker'); ?>
                        </p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="test_customer_name"><?php _e('Test Customer Name', 'wc-order-status-tracker'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               name="test_customer_name" 
                               id="test_customer_name" 
                               class="regular-text" 
                               value="John"
                               required>
                        <p class="description">
                            <?php _e('The first name that will appear in the email greeting.', 'wc-order-status-tracker'); ?>
                        </p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="test_order_number"><?php _e('Test Order Number', 'wc-order-status-tracker'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               name="test_order_number" 
                               id="test_order_number" 
                               class="regular-text" 
                               value="TEST-123"
                               required>
                        <p class="description">
                            <?php _e('The order number that will appear in the email.', 'wc-order-status-tracker'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Send Test Email', 'wc-order-status-tracker')); ?>
        </form>

        <hr style="margin: 30px 0;">
        
        <h3><?php _e('Email Preview', 'wc-order-status-tracker'); ?></h3>
        <p><?php _e('Below is a preview of how the email will look:', 'wc-order-status-tracker'); ?></p>
        
        <div style="border: 1px solid #ccd0d4; padding: 0; margin-top: 15px; max-width: 600px;">
            <div style="background: #f0f0f0; padding: 10px; border-bottom: 1px solid #ccd0d4; font-weight: bold;">
                <?php _e('Email Preview', 'wc-order-status-tracker'); ?>
            </div>
            <div style="padding: 0;">
                <?php
                $feedback = WC_OST_Feedback::get_instance();
                $preview_html = $feedback->get_email_template('John', 'TEST-123', 'https://pendragonpeptides.co.uk/feedback-submission/', get_bloginfo('name'), get_home_url());
                // Strip HTML tags for a simple text preview, or show in iframe
                echo '<iframe srcdoc="' . esc_attr($preview_html) . '" style="width: 100%; height: 600px; border: none;"></iframe>';
                ?>
            </div>
        </div>

        <hr style="margin: 30px 0;">

        <h3><?php _e('Debug Information', 'wc-order-status-tracker'); ?></h3>
        <table class="widefat striped" style="max-width: 600px;">
            <tbody>
                <tr>
                    <td><strong><?php _e('Feedback Emails Enabled:', 'wc-order-status-tracker'); ?></strong></td>
                    <td><?php echo WC_OST_Feedback::is_enabled() ? __('Yes', 'wc-order-status-tracker') : __('No', 'wc-order-status-tracker'); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Feature Activated:', 'wc-order-status-tracker'); ?></strong></td>
                    <td>
                        <?php 
                        $activation_time = WC_OST_Feedback::get_activation_time();
                        if ($activation_time) {
                            echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $activation_time);
                        } else {
                            _e('Not yet activated', 'wc-order-status-tracker');
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php _e('Current Time:', 'wc-order-status-tracker'); ?></strong></td>
                    <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format')); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('WordPress Cron:', 'wc-order-status-tracker'); ?></strong></td>
                    <td><?php echo (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) ? __('Disabled', 'wc-order-status-tracker') : __('Enabled', 'wc-order-status-tracker'); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Scheduled Events:', 'wc-order-status-tracker'); ?></strong></td>
                    <td>
                        <?php
                        $cron_array = _get_cron_array();
                        $count = 0;
                        if ($cron_array) {
                            foreach ($cron_array as $timestamp => $cron) {
                                if (isset($cron[WC_OST_Feedback::CRON_HOOK])) {
                                    $count++;
                                }
                            }
                        }
                        printf(
                            /* translators: %d: Number of scheduled events */
                            _n('%d feedback email scheduled', '%d feedback emails scheduled', $count, 'wc-order-status-tracker'),
                            $count
                        );
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    /**
     * Handle test email form submission
     */
    public function handle_test_email() {
        if (!isset($_POST['wc_ost_action']) || $_POST['wc_ost_action'] !== 'send_test_email') {
            return;
        }

        if (!wp_verify_nonce($_POST['wc_ost_test_email_nonce'], 'wc_ost_send_test_email')) {
            wp_die(__('Security check failed', 'wc-order-status-tracker'));
        }

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to send test emails', 'wc-order-status-tracker'));
        }

        $to = isset($_POST['test_email_to']) ? sanitize_email($_POST['test_email_to']) : '';
        $customer_name = isset($_POST['test_customer_name']) ? sanitize_text_field($_POST['test_customer_name']) : 'John';
        $order_number = isset($_POST['test_order_number']) ? sanitize_text_field($_POST['test_order_number']) : 'TEST-123';

        $feedback = WC_OST_Feedback::get_instance();
        $result = $feedback->send_test_email($to, $customer_name, $order_number);

        if (is_wp_error($result)) {
            set_transient('wc_ost_test_email_notice', array('type' => 'error', 'message' => $result->get_error_message()), 30);
        } else {
            set_transient('wc_ost_test_email_notice', array('type' => 'success', 'message' => sprintf(__('Test email sent successfully to %s', 'wc-order-status-tracker'), esc_html($to))), 30);
        }

        // Redirect to avoid form resubmission
        wp_redirect(admin_url('admin.php?page=wc-settings&tab=wc_ost_feedback&section=test'));
        exit;
    }

    /**
     * Show admin notices
     */
    public function show_admin_notices() {
        $notice = get_transient('wc_ost_test_email_notice');
        if ($notice) {
            delete_transient('wc_ost_test_email_notice');
            $class = $notice['type'] === 'success' ? 'notice-success' : 'notice-error';
            echo '<div class="notice ' . esc_attr($class) . ' is-dismissible"><p>' . esc_html($notice['message']) . '</p></div>';
        }

        // Show notice if feedback emails are enabled but activation time is not set
        if (WC_OST_Feedback::is_enabled() && !WC_OST_Feedback::get_activation_time()) {
            echo '<div class="notice notice-warning is-dismissible"><p>' . 
                sprintf(
                    /* translators: %s: Settings URL */
                    __('<strong>WC Order Status Tracker:</strong> Feedback emails are enabled but the activation time was not recorded. Please <a href="%s">visit the settings page</a> to ensure proper configuration.', 'wc-order-status-tracker'),
                    admin_url('admin.php?page=wc-settings&tab=wc_ost_feedback')
                ) . 
                '</p></div>';
        }
    }

    /**
     * Save settings
     */
    public function save() {
        global $current_section;

        if ($current_section === 'test') {
            return;
        }

        $settings = $this->get_settings($current_section);
        WC_Admin_Settings::save_fields($settings);

        // If enabling for the first time, set activation time
        if (isset($_POST[WC_OST_Feedback::ENABLED_OPTION]) && $_POST[WC_OST_Feedback::ENABLED_OPTION] === 'yes') {
            if (!WC_OST_Feedback::get_activation_time()) {
                WC_OST_Feedback::enable();
            }
        }
    }
}

return new WC_OST_Settings();
