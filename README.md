# Military Discounts

**Plugin Name:** Military Discounts
**Version:** 1.0.0-beta
**Author:** https://github.com/samdeving
**License:** GPL V3

## Description

This plugin is in beta, and should not be used in production at this time. Use at your own risk.

This is a WordPress WooCommerce plugin that verifies customers who are currently in the US military or a veteran. Veteran verification is done via the V.A. LightHouse API, or current military by sending a one-time passcode to their official email address. Once verified, you may automatically issue customers a coupon through WooCommerce.

## Features

- **VA API Integration:** Verify veteran status using the VA API.
- **Military Email OTP:** Verify military status using OTP sent to military email addresses.
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
   - **VA API Settings:** Enter your VA API key and configure sandbox mode.
   - **Military OTP Settings:** Configure email patterns for military email verification and OTP expiry.
   - **Queue Settings:** Configure retry intervals and maximum retries for failed verifications.
   - **Logs Settings:** Set log retention days.

3. Save your settings.

### Setting Up the .env File

The plugin supports using a `.env` file for securely storing sensitive configuration, such as the encryption key. This is optional but recommended for enhanced security.

#### Steps to Set Up the .env File:

1. **Create the `.env` File:**
   - Navigate to the root directory of your WordPress installation (or its parent directories).
   - Copy the `.env.example` file provided in the plugin directory to `.env`.
   - Open the `.env` file in a text editor.

2. **Configure the Encryption Key:**
   - Update the `MD_ENCRYPTION_KEY` value with a secure, randomly generated key (e.g., a 64-character hexadecimal string).
   - Example: `MD_ENCRYPTION_KEY="your-encryption-key-here"`

3. **Configure Additional Settings (Optional):**
   - Update the VA API settings (`MD_VA_API_KEY`, `MD_VA_API_URL`, `MD_VA_API_SANDBOX`) if you are using the VA API integration.
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

For support, please contact the plugin author or visit the [plugin support page](https://example.com).

## License

This plugin is licensed under the GPL v2 or later. See the [LICENSE](LICENSE) file for details.

## Changelog

### 1.0.0

- Initial release.
