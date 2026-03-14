=== WC Order Status Tracker ===
Contributors: sonnynabong
Donate link: https://sonnynabong.dev/donate
Tags: woocommerce, order tracking, order status, shortcode, customer, tracking, feedback, email
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
WC requires at least: 6.0
WC tested up to: 8.0
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A beautiful, secure WooCommerce plugin that lets customers track their order status using Order ID and Email.

== Description ==

**WC Order Status Tracker** is a lightweight, secure, and elegant solution for WooCommerce stores that want to provide order tracking functionality to their customers.

Simply add the `[wc_order_tracker]` shortcode to any page, and your customers can instantly view their order status, track progress through a visual timeline, and read customer notes with clickable links.

= 🌟 Features =

**✨ Beautiful Design**
* Modern, card-based UI with smooth animations
* Visual timeline showing order progress
* Responsive design - works perfectly on mobile and desktop
* Color-coded status badges for quick recognition

**📧 Automated Feedback Emails**
* Automatically send feedback request emails 7 days after order completion
* Beautiful, professional email template
* Test email functionality in admin
* Only sends to new orders (prevents spamming old customers)
* One email per order - no duplicates

**🔒 Security First**
* CSRF protection with nonce verification
* Rate limiting (5 attempts per 15 minutes)
* Timing-safe comparison to prevent order enumeration
* XSS protection with URL protocol validation
* All inputs sanitized and outputs escaped

**⚡ AJAX Powered**
* No page reload required
* Instant feedback with loading states
* Smooth slide animations

**🔗 Smart Link Detection**
* Automatically converts URLs in customer notes to clickable links
* Only allows safe http:// and https:// protocols
* Opens links in new tab with security attributes

= 📋 What Customers See =

1. **Order Header** - Order number, current status badge
2. **Order Info** - Date, customer name, total amount
3. **Status Timeline** - Visual progress through order stages:
   - Pending Payment → Processing → Completed → Shipped → Delivered
4. **Customer Notes** - Any notes added by the shop manager
5. **Order Items** - List of products in the order

= 📧 Feedback Email Feature =

Automatically request customer feedback 7 days after their order is marked as completed.

**Key Features:**
* **Smart Activation**: Records when you enable the feature - only sends to orders placed after activation
* **Automatic Scheduling**: Emails are scheduled when an order is marked "Completed"
* **7-Day Delay**: Gives customers time to receive and evaluate their purchase
* **One Per Order**: Each customer receives only one feedback request
* **Test Functionality**: Send test emails to preview how they look

**Setup:**
1. Go to **WooCommerce > Settings > Feedback Emails**
2. Check "Enable Feedback Emails"
3. Save changes
4. Use the "Send Test Email" tab to preview the email

= 🎯 Use Cases =

* **Order Tracking Page** - Create a dedicated "Track Your Order" page
* **Customer Support** - Reduce support tickets by letting customers self-serve
* **Post-Purchase Experience** - Improve customer satisfaction with transparency

== Installation ==

= Automatic Installation =

1. Go to **Plugins > Add New** in your WordPress admin
2. Search for "WC Order Status Tracker"
3. Click **Install Now** then **Activate**

= Manual Installation =

1. Download the plugin zip file
2. Go to **Plugins > Add New > Upload Plugin**
3. Choose the zip file and click **Install Now**
4. Click **Activate**

= Requirements =

* WordPress 5.8 or higher
* WooCommerce 6.0 or higher
* PHP 7.4 or higher

== Usage ==

= Basic Shortcode =

Add this to any page or post:

```
[wc_order_tracker]
```

= Custom Title =

```
[wc_order_tracker title="Track Your Package"]
```

= Custom Description =

```
[wc_order_tracker description="Enter your order details to check the current status."]
```

= Full Customization =

```
[wc_order_tracker title="Order Status Lookup" description="Find your order using the information from your confirmation email."]
```

= Creating a Tracking Page =

1. Go to **Pages > Add New**
2. Add a title like "Track Your Order"
3. Paste the shortcode: `[wc_order_tracker]`
4. Publish the page
5. Add the page link to your navigation menu

== Frequently Asked Questions ==

= What Order ID should customers enter? =

Customers should enter their WooCommerce Order ID or Order Number. This is found in their order confirmation email and typically looks like "#123" or just "123".

= What email should customers use? =

Customers must use the same email address they used when placing the order (the billing email).

= Will this work with custom order number plugins? =

Yes! The plugin searches by both Order ID and the `_order_number` meta field, so it works with most custom order number plugins.

= Can I customize the styling? =

Yes! The plugin uses specific CSS class prefixes (`wc-ost-`) that you can override in your theme's stylesheet. All styles are contained in `assets/css/wc-ost-style.css`.

= Is the order information secure? =

Absolutely. The plugin verifies that the provided email matches the order's billing email before displaying any information. It also includes rate limiting, CSRF protection, and XSS prevention.

= Does it support translations? =

Yes! The plugin is fully translatable using WordPress standard gettext functions. All text strings are wrapped in translation functions.

= Can I see an order status history? =

Yes! The plugin displays a visual timeline showing the order's journey through different statuses based on the current order state.

= What order statuses are supported? =

The timeline adapts to show the appropriate flow for:
* Standard orders (Pending → Processing → Completed → Shipped → Delivered)
* Cancelled orders
* Refunded orders
* On-hold orders
* Failed orders

= How do the feedback emails work? =

When you enable feedback emails, the plugin records the current date/time. From that point forward, whenever an order is marked as "Completed", a feedback email is automatically scheduled to be sent 7 days later.

**Important:** Orders placed BEFORE you enabled the feature will NOT receive emails. This prevents accidentally spamming customers who ordered in the past.

= Can I customize the feedback email? =

The email template uses the feedback form URL (set in settings) and your site's name. The template is professionally designed with a modern gradient header and clear call-to-action. For advanced customization, you can override the template in your theme.

= Will feedback emails be sent to old orders? =

No! The plugin tracks when you first enable the feature and only sends emails for orders placed after that date. This is a safety feature to prevent spamming customers with feedback requests for orders they placed months ago.

== Screenshots ==

1. Order tracking form - Clean, modern form for entering Order ID and Email
2. Order status results - Visual timeline with order details
3. Mobile view - Fully responsive design on smartphones
4. Customer note with links - URLs automatically converted to clickable links

== Changelog ==

= 1.1.0 - 2026-03-14 =
* Added automated feedback email feature
* Send feedback request emails 7 days after order completion
* Admin settings page under WooCommerce > Settings > Feedback Emails
* Test email functionality with live preview
* Smart activation - only sends to new orders
* Professional email template with gradient design
* Prevents duplicate emails with order meta tracking

= 1.0.0 - 2026-03-13 =
* Initial release
* Shortcode support with customizable attributes
* AJAX-powered order lookup with no page reload
* Visual status timeline with progress indicator
* Customer note display with auto-link detection
* Rate limiting for security (5 attempts per 15 minutes)
* CSRF protection with nonce verification
* XSS protection with URL protocol validation
* Timing attack mitigation
* Responsive design for all devices
* Full translation support

== Upgrade Notice ==

= 1.1.0 =
Added feedback email feature. Visit WooCommerce > Settings > Feedback Emails to configure.

= 1.0.0 =
Initial release. No upgrade necessary.

== Security ==

This plugin follows WordPress and WooCommerce security best practices:

* **CSRF Protection**: All AJAX requests verified with nonces
* **Input Sanitization**: All user inputs sanitized using WordPress functions
* **Output Escaping**: All output escaped to prevent XSS
* **SQL Injection Prevention**: Prepared statements for database queries
* **Rate Limiting**: Prevents brute force attacks
* **Timing Attack Mitigation**: Constant-time comparison for sensitive data

For security concerns, please contact [your-email@example.com].

== Credits ==

* Built with ❤️ for the WooCommerce community
* Icons via inline SVG
* Tested with WordPress 6.4 and WooCommerce 8.0

== License ==

This plugin is licensed under the GPL v2 or later.

WC Order Status Tracker is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
