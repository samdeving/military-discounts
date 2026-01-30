# Military Discounts Plugin - Form Text Customization Analysis

## 1. Current Settings Management

### Settings Structure ([`admin/class-md-settings.php`](admin/class-md-settings.php))

The plugin uses the WordPress Settings API with 6 main settings sections:

1. **General Settings** (`md_settings_general`)
   - Enable Plugin
   - Reverification Interval
   - Reverification Behavior
   - Disable Encryption (testing only)
   - Success Redirect URL
   - Redirect Delay (ms)
   - Page Title
   - Menu Order

2. **VA API Settings** (`md_settings_va_api`)
   - Enable VA API
   - API Key
   - Custom API URL
   - Sandbox Mode

3. **Military OTP Settings** (`md_settings_military_otp`)
   - Enable Military OTP
   - Email Whitelist
   - Email Blacklist
   - OTP Expiry (minutes)
   - Require Name Match
   - Lock Billing First Name
   - Lock Billing Last Name

4. **Queue Settings** (`md_settings_queue`)
   - Retry Interval (hours)
   - Maximum Retries

5. **Log Settings** (`md_settings_logs`)
   - Log Retention (days)

6. **Security Settings** (`md_settings_security`)
   - Enable Lockout Feature
   - Max Failed Veteran Attempts
   - Veteran Lockout Duration
   - Max Failed Military Attempts
   - Military Lockout Duration
   - Lockout Notification Email

### Settings API Usage

The settings are registered using `register_setting()` and `add_settings_field()` functions, with sanitization callbacks for each section. Settings are stored as serialized arrays in the WordPress options table.

## 2. Form Construction and Rendering

### Form Builder ([`admin/class-md-form-builder.php`](admin/class-md-form-builder.php))

The plugin uses a visual form builder to configure verification form fields:

- **Field Types Available**: Text Input, Email Input, Date Picker, Select Dropdown, State/Province, Country
- **Default Veteran Fields** (maps to VA API):
  - First Name (required)
  - Middle Name (optional)
  - Last Name (required)
  - Date of Birth (required)
  - Street Address (optional)
  - City (optional)
  - State (optional)
  - ZIP Code (required)
  - Country (optional)
- **Default Military Fields**:
  - Military Email Address (required) with placeholder "your.name@mail.mil"

Form fields are saved in `md_form_fields_veteran` and `md_form_fields_military` options.

### Form Rendering ([`public/class-md-my-account.php`](public/class-md-my-account.php))

The verification form is rendered in the WooCommerce My Account page with a 3-step process:

1. **Step 1: Select Verification Type**
   - Veteran (VA API) - "Verify through VA records"
   - Active Military (OTP) - "Verify with .mil email"

2. **Step 2: Enter Information**
   - Fields loaded dynamically via AJAX based on selected type
   - Fields are rendered by `render_form_fields()` method in [`public/class-md-ajax.php`](public/class-md-ajax.php)

3. **Step 3: Submit/Verify**
   - For Veteran: Confirmation of information before submission
   - For Military: OTP (One-Time Passcode) verification

### Frontend AJAX Handling ([`public/class-md-ajax.php`](public/class-md-ajax.php))

Key AJAX actions:
- `md_get_form_fields`: Load form fields for selected verification type
- `md_submit_veteran_verification`: Submit veteran verification to VA API
- `md_send_military_otp`: Send OTP to military email
- `md_verify_military_otp`: Verify OTP code
- `md_validate_military_email`: Validate military email format
- `md_check_billing_name`: Check if user has billing name set
- `md_check_email_name_match`: Check if email matches billing name

### Frontend JavaScript ([`assets/js/md-public.js`](assets/js/md-public.js))

Handles form navigation, validation, and AJAX communication:
- Step navigation with progress indicators
- Field validation
- Dynamic content loading
- OTP sending and verification
- Success/failure message display
- Failed attempts UI updates

## 3. Form Text Contents to Be Customized

Below is a comprehensive list of all user-facing text strings in the verification forms and process:

### My Account Page ([`public/class-md-my-account.php`](public/class-md-my-account.php))

1. **Page Header**
   - "Military Discounts" (page title, customizable via settings)
   - "Verify your veteran or active military status to access exclusive discounts."

2. **Verified Status Display**
   - "Veteran Status Verified"
   - "Active Military Status Verified"
   - "Valid until %s" (with expiry date)
   - "No expiration"
   - "You are eligible for military discounts on applicable products and coupons."

3. **Pending Status Display**
   - "Verification Pending"
   - "Your verification request has been submitted and is being processed. You will receive an email notification once complete."
   - "Type:"
   - "Submitted:"
   - "Status:"

4. **Lockout Status Display**
   - "Verification Locked"
   - "Too many failed %s verification attempts. Please try again in %d minutes."

5. **Failed Attempts Display**
   - "Veteran verification: %d/%d failed attempts"
   - "Military verification: %d/%d failed attempts"

6. **Form Steps**
   - "1" (step number)
   - "Select Type" (step label)
   - "2" (step number)
   - "Enter Info" (step label)
   - "3" (step number)
   - "Submit" (step label)

7. **Step 1: Type Selection**
   - "Select Verification Type" (heading)
   - "I am a Veteran" (radio button label)
   - "Verify through VA records" (description)
   - "I am Active Military" (radio button label)
   - "Verify with .mil email" (description)
   - "Next" (button text)

8. **Step 2: Enter Information**
   - "Back" (button text)
   - "Next" (button text)

9. **Step 3: Submit/Verify**
   - "Back" (button text)

### Form Fields ([`admin/class-md-form-builder.php`](admin/class-md-form-builder.php) and [`public/class-md-ajax.php`](public/class-md-ajax.php))

1. **Veteran Form Fields (Default)**
   - "First Name" (required)
   - "Middle Name" (optional)
   - "Last Name" (required)
   - "Date of Birth" (required)
   - "Street Address" (optional)
   - "City" (optional)
   - "State" (optional)
   - "ZIP Code" (required)
   - "Country" (optional)

2. **Military Form Fields (Default)**
   - "Military Email Address" (required)
   - "your.name@mail.mil" (placeholder)

3. **Select Dropdowns**
   - "Select..." (default option)
   - "Select State..." (state dropdown)
   - "Select Country..." (country dropdown)
   - "United States" (default country option)

### AJAX Responses ([`public/class-md-ajax.php`](public/class-md-ajax.php))

1. **Error Messages**
   - "Please log in to continue."
   - "Invalid verification type."
   - "Please enter a valid email address."
   - "This email address is not recognized as a valid military email. Please use your official .mil email address."
   - "Please update your billing first and last name in your account before verifying."
   - "The email username does not match your billing name (%1$s %2$s). The email local part must contain your first and last name (e.g., firstname.lastname@mail.mil)."
   - "The email username does not match your billing first name (%1$s). The email local part must contain your first name (e.g., firstname@mail.mil)."
   - "The email username does not match your billing name."
   - "First name and last name are required."
   - "You are already verified as a veteran."
   - "You already have a pending verification request."
   - "Too many failed verification attempts. Please try again in %d minutes."
   - "Your veteran status has been verified! You can now use veteran discounts."
   - "Your verification request has been submitted and is being processed. You will receive an email notification once complete."
   - "Failed attempt %d/%d. %s"
   - "Failed to send verification code. Please try again."
   - "Please enter the verification code."
   - "Your active military status has been verified! You can now use military discounts."
   - "Invalid or expired verification code. Please try again."
   - "Please wait %d seconds before requesting a new code."

### JavaScript Strings ([`public/class-md-public.php`](public/class-md-public.php) and [`assets/js/md-public.js`](assets/js/md-public.js))

1. **Loading/Processing Messages**
   - "Loading..."
   - "Submitting..."
   - "Sending code..."
   - "Verifying..."

2. **Validation Messages**
   - "An error occurred. Please try again."
   - "This field is required."
   - "Please enter a valid email address."
   - "Please select a verification type."

3. **OTP Messages**
   - "Verification code sent to your email."
   - "We've sent another code to your email."
   - "Verify Code" (button text)
   - "Resend Code" (button text)

4. **Button Text**
   - "Back"
   - "Next"
   - "Submit"

5. **Step 2 Dynamic Titles**
   - "Enter Your Information" (veteran)
   - "Enter Your Military Email" (military)

6. **Step 3 Content**
   - "Confirm Your Information" (veteran heading)
   - "Please review your information before submitting."
   - "Verification Details" (section heading)
   - "Verify Your Military Email" (military heading)
   - "We'll send a verification code to:"
   - "Resend code" (link text)
   - "Please enter a 6-digit code." (OTP validation)

7. **Success/Redirect Messages**
   - "Redirecting..."
   - "Refreshing page..."

## 4. Existing Form Templates and Files with Text Strings

### Main Form Files:

1. **[`public/class-md-my-account.php`](public/class-md-my-account.php:123-417)** - Main endpoint and form structure
2. **[`public/class-md-ajax.php`](public/class-md-ajax.php:179-334)** - Form fields rendering and AJAX responses
3. **[`admin/class-md-form-builder.php`](admin/class-md-form-builder.php:60-152)** - Default form fields definition
4. **[`assets/js/md-public.js`](assets/js/md-public.js)** - Frontend form interaction

### Other Relevant Files:

1. **[`public/class-md-public.php`](public/class-md-public.php:119-143)** - Script localization with string definitions
2. **[`admin/class-md-settings.php`](admin/class-md-settings.php)** - Settings page and field labels
3. **[`templates/emails/`](templates/emails/)** - Email templates (OTP, verification approved/denied, reverification, lockout)

## 5. Customization Approach

### Recommended Strategy

To make all form text elements customizable, we should:

1. **Create a new settings section** for "Form Text Customization" in `admin/class-md-settings.php`

2. **Group settings logically** by form section:
   - Page Header
   - Verified Status
   - Pending Status
   - Lockout Status
   - Failed Attempts
   - Step 1: Type Selection
   - Step 2: Enter Information
   - Step 3: Veteran Confirmation
   - Step 3: Military OTP
   - Form Fields (labels and placeholders)
   - Buttons and Actions
   - Error/Validation Messages

3. **Add settings fields** for each text string using appropriate field types:
   - Text fields for single-line text
   - Textarea fields for multi-line text
   - Placeholder fields for input placeholders

4. **Modify the rendering code** in:
   - `public/class-md-my-account.php` to use settings instead of hardcoded strings
   - `public/class-md-ajax.php` to use settings for field labels and AJAX responses
   - `public/class-md-public.php` to localize settings strings for JavaScript
   - `assets/js/md-public.js` to use localized strings

5. **Update the form builder** to allow editing field labels and placeholders via the admin interface

6. **Maintain backward compatibility** by providing default values for all new settings

### Benefits of This Approach

- All form text is centralized in one settings section for easy management
- Users can customize every aspect of the form's language without touching code
- Default values ensure the form remains functional if no customizations are made
- The existing architecture supports this type of customization
- Changes are minimal and focused on the requirements

## 6. Implementation Steps

1. Analyze all current text strings in the form and process
2. Create a comprehensive list of customizable strings with default values
3. Add a new settings section in `admin/class-md-settings.php`
4. Implement settings fields for each customizable string
5. Modify the rendering code to use the new settings
6. Update the JavaScript localization to include new strings
7. Test the functionality with various customizations
8. Document the new settings for users

## 7. Summary

The Military Discounts plugin has a well-structured form system that is ripe for customization. By creating a new settings section for form text, we can allow users to tailor every aspect of the verification process to their specific needs. This will make the plugin more flexible and adaptable to different use cases, while maintaining the existing functionality and architecture.
