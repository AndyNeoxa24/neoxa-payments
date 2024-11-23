# Contributing to Neoxa Payments WordPress Plugin

Thank you for your interest in contributing to the Neoxa Payments WordPress plugin! We welcome contributions from the community and are pleased to have you join us.

## Getting Started

1. Fork the repository on GitHub
2. Clone your fork locally:
   ```bash
   git clone https://github.com/YourUsername/neoxa-payments.git
   ```
3. Create a branch for your changes:
   ```bash
   git checkout -b feature/your-feature-name
   ```

## Development Environment Setup

1. Install WordPress locally
2. Install WooCommerce plugin
3. Set up a Neoxa wallet with RPC access
4. Configure your wallet using the settings provided in README.md
5. Link your plugin directory to WordPress plugins folder

## Coding Standards

This project follows WordPress Coding Standards. Please ensure your code adheres to:
- [WordPress PHP Coding Standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/php/)
- [WordPress JavaScript Coding Standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/javascript/)
- [WordPress CSS Coding Standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/css/)

## Pull Request Process

1. Update the README.md with details of changes if applicable
2. Update the header.php file with any new files or structural changes
3. Test your changes thoroughly
4. Create a Pull Request with a clear title and description
5. Link any relevant issues in your Pull Request

## Testing

Before submitting a Pull Request, please test:
- Plugin activation/deactivation
- Settings page functionality
- Payment processing
- WooCommerce integration
- Asset selection and verification
- RPC communication
- Error handling
- Responsive design

## Commit Messages

- Use clear and meaningful commit messages
- Start with a verb in present tense
- Keep the first line under 50 characters
- Add detailed description if needed

Example:
```
Add asset selection validation

- Implement input sanitization
- Add error handling for invalid assets
- Update admin interface feedback
```

## Documentation

- Update documentation for any new features
- Add PHPDoc blocks to new functions
- Include code comments for complex logic
- Update version numbers if applicable

## Security

- Never commit sensitive information
- Always validate and sanitize input
- Use WordPress security functions
- Implement nonce verification
- Check user capabilities

## Need Help?

- Create an issue for bug reports
- Use discussions for feature requests
- Contact the maintainers for guidance

## Code of Conduct

- Be respectful and inclusive
- Focus on constructive feedback
- Help others learn and grow
- Follow WordPress community guidelines

## License

By contributing to this project, you agree that your contributions will be licensed under the GPL v2 license.

## Contact

For major changes or questions, please contact:
- Andy Niemand (Neoxa Founder)
- GitHub: [@AndyNeoxa24](https://github.com/AndyNeoxa24)

Thank you for contributing to Neoxa Payments!
