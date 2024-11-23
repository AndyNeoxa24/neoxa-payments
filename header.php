<?php
/**
 * Neoxa Payments - WordPress Plugin Header
 * 
 * This file contains the standard header information and coding standards
 * for the Neoxa Payments WordPress plugin.
 *
 * @package     NeoxaPayments
 * @author      Andy Niemand - Neoxa Founder
 * @copyright   2024 Neoxa
 * @license     GPL-2.0+
 * @link        https://github.com/AndyNeoxa24/neoxa-payments
 *
 * Coding Standards:
 * - This plugin follows WordPress Coding Standards: https://make.wordpress.org/core/handbook/best-practices/coding-standards/
 * - PSR-4 autoloading compliant structure
 * - WPCS: WordPress Coding Standards
 * - WPCS: WordPress Core
 *
 * File Structure:
 * /neoxa-payments
 * ├── admin/                  # Admin-specific functionality
 * │   ├── css/               # Admin CSS files
 * │   ├── js/                # Admin JavaScript files
 * │   └── class-neoxa-admin.php
 * ├── assets/                # Frontend assets
 * │   └── images/            # Images including logo
 * ├── includes/              # Plugin core functionality
 * │   ├── class-neoxa-payments.php
 * │   ├── class-neoxa-rpc.php
 * │   └── class-wc-neoxa-gateway.php
 * ├── .github/               # GitHub specific files
 * │   └── workflows/         # GitHub Actions workflows
 * ├── languages/             # Translation files (if needed)
 * ├── .gitignore            # Git ignore rules
 * ├── LICENSE               # GPL v2 license file
 * ├── README.md             # Plugin documentation
 * ├── header.php            # This file
 * ├── neoxa-payments.php    # Main plugin file
 * └── uninstall.php         # Cleanup on uninstall
 *
 * Naming Conventions:
 * - Class names: Capitalized with underscores (e.g., Class_Name)
 * - Function names: Lowercase with underscores (e.g., function_name)
 * - Variable names: Lowercase with underscores (e.g., $variable_name)
 * - Constants: Uppercase with underscores (e.g., CONSTANT_NAME)
 * - File names: Lowercase with hyphens (e.g., file-name.php)
 *
 * Security Measures:
 * - ABSPATH checking in all PHP files
 * - Nonce verification for forms
 * - Capability checking for admin actions
 * - Data sanitization and validation
 * - Prepared SQL statements
 * - XSS prevention
 * - CSRF protection
 *
 * WordPress Integration:
 * - Uses WordPress hooks and filters
 * - Follows WordPress plugin API
 * - Integrates with WooCommerce
 * - Uses WordPress settings API
 * - Follows WordPress security best practices
 *
 * Version Control:
 * - Hosted on GitHub
 * - Automated releases via GitHub Actions
 * - Semantic versioning (MAJOR.MINOR.PATCH)
 *
 * Testing:
 * - Manual testing on multiple WordPress versions
 * - WooCommerce compatibility testing
 * - Payment flow testing
 * - RPC communication testing
 *
 * Support:
 * - GitHub Issues for bug reports and feature requests
 * - Documentation in README.md
 * - Code comments for developer reference
 *
 * @since      1.0.0
 * @version    1.0.0
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}
