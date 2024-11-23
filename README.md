# Neoxa Payments for WordPress/WooCommerce

Accept Neoxa cryptocurrency and Neoxa assets as payment methods in your WooCommerce store.

## Description

Neoxa Payments is a WordPress plugin that integrates with WooCommerce to allow your store to accept Neoxa cryptocurrency and Neoxa assets as payment methods. The plugin connects directly to your Neoxa wallet through RPC, providing a secure and reliable payment solution.

## Features

- Accept Neoxa (NEOX) cryptocurrency payments
- Support for Neoxa assets as payment methods
- Real-time payment verification
- Automatic order status updates
- QR code generation for payment addresses
- Configurable confirmation threshold
- Detailed payment instructions in orders and emails
- Admin dashboard for managing accepted assets
- Secure RPC communication with your Neoxa wallet

## Requirements

- WordPress 5.0 or higher
- WooCommerce 3.0 or higher
- PHP 7.0 or higher
- Neoxa wallet with RPC access enabled
- SSL certificate (recommended for security)

## Installation

1. Upload the plugin files to the `/wp-content/plugins/neoxa-payments` directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure your Neoxa wallet with the required settings (see Wallet Configuration below).
4. Use the Neoxa Payments settings page to configure your RPC connection and select accepted assets.

## Wallet Configuration

Your Neoxa wallet's configuration file (neoxa.conf) must include the following settings:

```conf
daemon=1
txindex=1
assetindex=1
addressindex=1
spentindex=1
rpcuser=your_username
rpcpassword=your_secure_password
rpcport=8332
rpcworkqueue=64
rpcthreads=16
rpcallowip=0.0.0.0/0
rpcbind=0.0.0.0
dbcache=2048
maxmempool=512
mempoolexpiry=72
rpcworkqueue=25000
rpcthreads=16
maxconnections=125
maxuploadtarget=5000
```

Replace `your_username` and `your_secure_password` with your own secure credentials.

## Configuration

1. Go to WooCommerce → Settings → Payments
2. Click on "Neoxa Payments" to configure the payment gateway
3. Enter your RPC connection details:
   - RPC Host (usually localhost or your server IP)
   - RPC Port (default: 8332)
   - RPC Username
   - RPC Password
4. Select which Neoxa assets you want to accept as payment
5. Save changes

## Security Recommendations

1. Always use strong, unique passwords for your RPC credentials
2. Use SSL/TLS encryption for your WordPress site
3. Restrict RPC access to your server's IP if possible
4. Regularly update WordPress, WooCommerce, and this plugin
5. Keep your Neoxa wallet up to date
6. Regularly backup your wallet and WordPress database

## Frequently Asked Questions

### How are payments verified?

The plugin monitors the payment address for incoming transactions. When a payment is detected, it verifies:
1. The correct amount was sent
2. The correct asset was used
3. The transaction has the required number of confirmations

### Can I accept multiple Neoxa assets?

Yes, you can select which Neoxa assets to accept in the plugin settings. NEOXA (the main currency) is always accepted by default.

### How long does payment verification take?

Payment detection is near-instant, but confirmation time depends on the Neoxa network and your configured confirmation threshold.

## Support

For support, please visit the [GitHub repository](https://github.com/AndyNeoxa24/neoxa-payments) or contact the Neoxa team.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## Credits

Created by Andy Niemand - Neoxa Founder
GitHub: [AndyNeoxa24](https://github.com/AndyNeoxa24)

## Changelog

### 1.0.0
- Initial release
- Basic payment processing functionality
- WooCommerce integration
- Asset selection support
- Admin interface
- Payment verification system
