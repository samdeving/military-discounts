# Military Discounts

**Plugin Name:** Military Discounts
**Version:** 1.0.0-beta
**Author:** https://github.com/samdeving
**License:** GPL V3

## Description

This plugin is in beta, and should not be used in production at this time. Use at your own risk.

This is a WordPress WooCommerce plugin that verifies customers who are currently serving in the US military or are veterans. Veteran verification is done via the **U.S. Department of Veterans Affairs (VA) Lighthouse API**, and active-duty military verification is performed by sending a one-time passcode (OTP) to their official military email address. Once verified, you can automatically issue customers a discount coupon through WooCommerce.

## Features

- **VA Lighthouse API Integration:** Verify veteran status using the official U.S. Department of Veterans Affairs Lighthouse API.
- **Military Email OTP:** Verify active-duty military status using OTP sent to official military email addresses.
- **Coupon Validation:** Restrict WooCommerce coupons to verified veterans and military personnel.
- **Reverification:** Automatically reverify status at configurable intervals.
- **Logging:** Track verification attempts and outcomes for auditing.

## Requirements

- **WordPress:** 6.0 or higher
- **PHP:** 7.4 or higher
- **WooCommerce:** Must be installed and active

## Installation

### Automatic Installation

1. Log in to your WordPress admin dashboard.
2. Navigate to **Plugins > Add New**.
3. Click **Upload Plugin** and select the `military-discounts.zip` file.
4. Click **Install Now** and then **Activate Plugin**.

### Manual Installation

1. Download the `military-discounts` plugin folder.
2. Upload the folder to the `/wp-content/plugins/` directory on your server.
3. Log in to your WordPress admin dashboard.
4. Navigate to **Plugins** and locate **Military Discounts**.
5. Click **Activate** to enable the plugin.

## Configuration

After activation, configure the plugin settings:

1. Navigate to **WooCommerce > Settings > Military Discounts**.
2. Configure the following settings:
   - **General Settings:** Enable/disable the plugin, set reverification intervals, and configure encryption.
   - **VA Lighthouse API Settings:** Enter your VA Lighthouse API key and configure sandbox mode.
   - **Military OTP Settings:** Configure email patterns for military email verification and OTP expiry.
   - **Queue Settings:** Configure retry intervals and maximum retries for failed verifications.
   - **Logs Settings:** Set log retention days.

3. Save your settings.

## VA Lighthouse API Setup

To use the VA Lighthouse API for veteran verification, you'll need to obtain API credentials. The VA provides both sandbox (testing) and production (live) environments.

### What is the VA Lighthouse API?

The **VA Lighthouse API** is a modern, RESTful API provided by the U.S. Department of Veterans Affairs that allows developers to access VA services programmatically. The Veteran Confirmation API endpoint is used by this plugin to verify veteran status.

### Obtaining a Sandbox API Key (for testing)

1. **Create a VA Lighthouse Developer Account:**
   - Visit the [VA Lighthouse Developer Portal](https://developer.va.gov/)
   - Click "Sign In" or "Create Account" to register
   - Complete the registration process and verify your email

2. **Register Your Application:**
   - After logging in, navigate to "My Applications"
   - Click "Create New Application"
   - Provide a name for your application (e.g., "Military Discounts Plugin")
   - Select "VA Lighthouse API" as the API you want to use
   - Choose "Sandbox" environment for testing purposes
   - Complete the application registration

3. **Get Your Sandbox API Key:**
   - Once your application is registered, you'll be given a sandbox API key
   - Copy this key for use in the plugin settings

### Obtaining a Production API Key (for live use)

1. **Complete Sandbox Testing:**
   - Ensure your application works correctly with the sandbox API
   - Test various verification scenarios to ensure reliability

2. **Request Production Access:**
   - In the VA Lighthouse Developer Portal, navigate to your application
   - Click "Request Production Access"
   - Provide information about your use case, including:
     - How you'll use the API
     - Estimated API usage volume
     - Security measures you've implemented
   - Complete the production access request form

3. **Await Approval:**
   - Production access requests are reviewed by the VA
   - Approval times can vary (typically a few business days to a week)
   - You'll receive an email notification when your request is approved

4. **Get Your Production API Key:**
   - Once approved, you'll receive a production API key
   - Replace your sandbox key with the production key in the plugin settings

### API Key Security Best Practices

- Always store your API keys securely using the `.env` file (never commit them to version control)
- Restrict API key access to only the necessary IP addresses
- Monitor API usage regularly for unusual activity
- Rotate your API keys periodically

### Setting Up the .env File

The plugin supports using a `.env` file for securely storing sensitive configuration, such as the encryption key and API credentials. This is optional but recommended for enhanced security.

#### Steps to Set Up the .env File:

1. **Create the `.env` File:**
   - Navigate to the root directory of your WordPress installation (or its parent directories).
   - Copy the `.env.example` file provided in the plugin directory to `.env`.
   - Open the `.env` file in a text editor.

2. **Configure the Encryption Key:**
   - Update the `MD_ENCRYPTION_KEY` value with a secure, randomly generated key (e.g., a 64-character hexadecimal string).
   - Example: `MD_ENCRYPTION_KEY="your-encryption-key-here"`

3. **Configure VA Lighthouse API Settings (Optional):**
   - Update the VA Lighthouse API settings (`MD_VA_API_KEY`, `MD_VA_API_URL`, `MD_VA_API_SANDBOX`) if you are using the VA API integration.
   - Configure military OTP settings (`MD_MILITARY_OTP_WHITELIST_PATTERNS`, `MD_MILITARY_OTP_BLACKLIST_PATTERNS`, `MD_MILITARY_OTP_EXPIRY`) as needed.
   - Adjust logging and queue settings (`MD_LOG_RETENTION_DAYS`, `MD_QUEUE_RETRY_INTERVAL`, `MD_QUEUE_MAX_RETRIES`) if necessary.

4. **Save the File:**
   - Save the `.env` file and ensure it is placed in one of the following locations:
     - WordPress root directory (same level as `wp-config.php`)
     - Parent directory of the WordPress root
     - Grandparent directory of the WordPress root

5. **Verify the Setup:**
   - After saving the `.env` file, navigate to **WooCommerce > Settings > Military Discounts**.
   - If the encryption key is successfully loaded from the `.env` file, you will see a notice indicating that the encryption key is detected from the `.env` file.

#### Notes:

- The `.env` file should be kept secure and not exposed publicly. Ensure it is not accessible via the web.
- If the `.env` file is not detected or the `MD_ENCRYPTION_KEY` is not defined, the plugin will automatically generate and store an encryption key in the WordPress database.
- For better security, it is recommended to use the `.env` file for managing sensitive configuration.
- Refer to the `.env.example` file in the plugin directory for a complete list of available configuration options.

## Usage

### For Customers

1. **Verification Process:**
   - Customers can verify their veteran or military status during checkout or from their account page.
   - For VA API verification, they will need to provide their VA credentials.
   - For military email verification, they will receive an OTP sent to their military email address.

2. **Applying Discounts:**
   - Once verified, customers can apply military-specific coupons during checkout.

### For Administrators

1. **Monitoring Verifications:**
   - View verification logs under **WooCommerce > Military Discounts > Logs**.
   - Monitor pending verifications and retry failed attempts.

2. **Managing Coupons:**
   - Create coupons in WooCommerce and restrict them to verified veterans or military personnel using the plugin settings.

## Support

For support, please contact the plugin author or visit the https://github.com/samdeving/military-discounts.

## License

This plugin is licensed under the GPL v3. See the [LICENSE](LICENSE) file for details.

## Changelog

### 1.0.0-beta

- Initial release.

