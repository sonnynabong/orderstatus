# 🚚 WC Order Status Tracker

[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-6.0%2B-96588a.svg)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

> A beautiful, secure WooCommerce plugin that lets customers track their order status with a simple shortcode.

![Plugin Screenshot](https://via.placeholder.com/800x400/3b82f6/ffffff?text=WC+Order+Status+Tracker)

## ✨ Features

### 🎨 Beautiful Design
- **Modern card-based UI** with smooth animations
- **Visual timeline** showing order progress
- **Responsive design** - works perfectly on mobile and desktop
- **Color-coded status badges** for quick recognition

### 🔒 Security First
- ✅ CSRF protection with nonce verification
- ✅ Rate limiting (5 attempts per 15 minutes)
- ✅ Timing-safe comparison to prevent order enumeration
- ✅ XSS protection with URL protocol validation
- ✅ All inputs sanitized and outputs escaped

### ⚡ AJAX Powered
- **No page reload** required
- **Instant feedback** with loading states
- **Smooth slide animations**

### 🔗 Smart Features
- **Auto-link detection** - Converts URLs in customer notes to clickable links
- **Protocol validation** - Only allows safe `http://` and `https://` URLs
- **Security attributes** - Links open in new tab with `rel="noopener noreferrer"`

## 📸 Screenshots

| Tracking Form | Order Status Result |
|:-------------:|:-------------------:|
| ![Form](https://via.placeholder.com/350x300/f3f4f6/374151?text=Tracking+Form) | ![Result](https://via.placeholder.com/350x300/d1fae5/065f46?text=Status+Result) |

## 🚀 Installation

### Automatic (Recommended)

1. Go to **Plugins > Add New** in your WordPress admin
2. Search for "WC Order Status Tracker"
3. Click **Install Now** then **Activate**

### Manual

```bash
# Download and extract to your plugins folder
wp-content/plugins/wc-order-status-tracker/
```

Then activate through the WordPress admin.

### Requirements

- WordPress 5.8+
- WooCommerce 6.0+
- PHP 7.4+

## 📖 Usage

### Basic Shortcode

```
[wc_order_tracker]
```

### With Custom Title

```
[wc_order_tracker title="Track Your Package"]
```

### With Custom Description

```
[wc_order_tracker description="Enter your details below to track your package."]
```

### Full Customization

```
[wc_order_tracker 
    title="Track Your Order" 
    description="Enter your Order ID and Email from your confirmation email."
]
```

## 🎯 What Customers See

1. **📋 Order Header** - Order number with status badge
2. **👤 Order Info** - Date, customer name, total amount
3. **📍 Status Timeline** - Visual progress through:
   ```
   Pending → Processing → Completed → Shipped → Delivered
   ```
4. **📝 Customer Notes** - Shop manager notes with clickable links
5. **📦 Order Items** - List of products ordered

## 🔐 Security

This plugin implements enterprise-grade security:

| Feature | Implementation |
|---------|---------------|
| **CSRF Protection** | `wp_nonce` verification on all AJAX requests |
| **Input Sanitization** | `sanitize_text_field()`, `sanitize_email()` |
| **Output Escaping** | `esc_html()`, `esc_attr()`, `wp_kses_post()` |
| **SQL Injection Prevention** | `$wpdb->prepare()` with placeholders |
| **Rate Limiting** | 5 attempts per IP per 15 minutes |
| **Timing Attack Prevention** | `hash_equals()` for string comparison |
| **XSS Prevention** | URL protocol validation (http/https only) |

## ❓ FAQ

### What Order ID should customers enter?

The WooCommerce Order ID or Order Number from their confirmation email (e.g., "123" or "#123").

### What email should customers use?

The billing email used during checkout.

### Does it work with custom order number plugins?

Yes! The plugin searches by both Order ID and the `_order_number` meta field.

### Can I customize the styling?

Absolutely! All CSS classes use the `wc-ost-` prefix. Override in your theme's stylesheet:

```css
.wc-order-tracker-wrapper {
    max-width: 700px;
}

.wc-ost-button {
    background: your-brand-color;
}
```

### Is the order information secure?

Yes. The plugin verifies email ownership before displaying any data and includes multiple security layers.

### Does it support translations?

Yes! Fully translatable using WordPress standard gettext functions.

## 📝 Changelog

### 1.0.0 - 2026-03-13
- ✨ Initial release
- 🔒 Security hardening with rate limiting, CSRF protection, XSS prevention
- 🎨 Beautiful visual timeline
- 🔗 Auto-link detection in customer notes
- 📱 Fully responsive design

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

---

<p align="center">
  Built with ❤️ for the WooCommerce community
</p>
