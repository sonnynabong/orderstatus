=== WC Order Status Tracker ===
Contributors: yourname
Tags: woocommerce, order tracking, order status, shortcode, customer
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
WC requires at least: 6.0
WC tested up to: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WooCommerce plugin that allows customers to track their order status using Order ID and Email via shortcode.

== Description ==

WC Order Status Tracker is a simple and elegant WooCommerce plugin that enables your customers to check their order status. 

Simply add the `[wc_order_tracker]` shortcode to any page or post, and customers can enter their Order ID and Email to view:

* Current order status with visual timeline
* Order details (date, customer name, total)
* Customer notes with clickable links
* Order items summary

= Features =

* **Easy Shortcode**: Use `[wc_order_tracker]` on any page
* **AJAX Powered**: No page reload required
* **Visual Timeline**: Beautiful status history with progress indicator
* **Link Detection**: Automatically converts URLs in customer notes to clickable links
* **Responsive Design**: Works perfectly on mobile and desktop
* **Secure**: Validates order ownership via email matching
* **Customizable**: Use shortcode attributes to customize title and description

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/wc-order-status-tracker/`, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Make sure WooCommerce is installed and activated.
4. Add the `[wc_order_tracker]` shortcode to any page or post.

== Usage ==

= Basic Usage =

Add the shortcode to any page or post:

```
[wc_order_tracker]
```

= With Custom Title =

```
[wc_order_tracker title="Check Your Order Status"]
```

= With Custom Description =

```
[wc_order_tracker description="Enter your details below to track your package."]
```

= With Both Attributes =

```
[wc_order_tracker title="Track Your Package" description="Enter your order details below"]
```

== How It Works ==

1. Customer enters their Order ID (order number)
2. Customer enters the email address used during checkout
3. If the Order ID and Email match, the order details are displayed
4. The customer sees:
   - Order information header with status badge
   - Visual timeline showing order progress
   - Customer notes (with clickable links if URLs are present)
   - List of ordered items

== Status Timeline ==

The plugin displays a visual timeline showing the order's journey:

* **Pending** - Order received, awaiting payment
* **Processing** - Payment received, order being prepared
* **Completed** - Order fulfilled and complete
* **Shipped** - Order has been shipped
* **Delivered** - Order has been delivered

Alternative flows are shown for:
* Cancelled orders
* Refunded orders
* On-hold orders
* Failed orders

== Frequently Asked Questions ==

= What Order ID should customers enter? =

Customers should enter their WooCommerce Order ID or Order Number. This is typically found in their order confirmation email.

= What email should customers use? =

Customers must use the same email address they used when placing the order (billing email).

= Will this work with custom order number plugins? =

Yes, the plugin attempts to find orders by both ID and the `_order_number` meta field, which many custom order number plugins use.

= Can I customize the styling? =

Yes, you can override the CSS by adding your own styles to your theme's stylesheet. The plugin uses specific class prefixes (`wc-ost-`) to avoid conflicts.

= Is the order information secure? =

Yes, the plugin validates that the provided email matches the order's billing email before displaying any information.

= Does it support translations? =

Yes, the plugin is fully translatable using WordPress standard gettext functions.

== Screenshots ==

1. Order tracking form
2. Order status results with timeline
3. Mobile responsive view

== Changelog ==

= 1.0.0 =
* Initial release
* Shortcode support with customizable attributes
* AJAX-powered order lookup
* Visual status timeline
* Customer note display with auto-link detection
* Responsive design

== Upgrade Notice ==

= 1.0.0 =
Initial release.

== Credits ==

* Built for WooCommerce
* Icons via inline SVG
