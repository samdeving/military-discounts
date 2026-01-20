<?php
/**
 * Encryption class for Military Discounts plugin.
 *
 * Provides AES-256-GCM encryption/decryption for sensitive data.
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MD_Encryption
 *
 * Handles encryption and decryption of sensitive data using AES-256-GCM.
 */
class MD_Encryption {

	/**
	 * Cipher algorithm.
	 *
	 * @var string
	 */
	private const CIPHER = 'aes-256-gcm';

	/**
	 * Tag length for GCM mode.
	 *
	 * @var int
	 */
	private const TAG_LENGTH = 16;

	/**
	 * Encryption key.
	 *
	 * @var string|null
	 */
	private $key = null;

	/**
	 * Encrypt data.
	 *
	 * @param string $plaintext Data to encrypt.
	 * @return string Encrypted data (base64 encoded).
	 * @throws Exception If encryption fails.
	 */
	public function encrypt( $plaintext ) {
		// Check if encryption is disabled.
		if ( ! md_is_encryption_enabled() ) {
			return base64_encode( $plaintext );
		}

		$key = $this->get_key();
		if ( empty( $key ) ) {
			throw new Exception( esc_html__( 'Encryption key not available.', 'military-discounts' ) );
		}

		$iv_length = openssl_cipher_iv_length( self::CIPHER );
		$iv        = openssl_random_pseudo_bytes( $iv_length );
		$tag       = '';

		$ciphertext = openssl_encrypt(
			$plaintext,
			self::CIPHER,
			$key,
			OPENSSL_RAW_DATA,
			$iv,
			$tag,
			'',
			self::TAG_LENGTH
		);

		if ( false === $ciphertext ) {
			throw new Exception( esc_html__( 'Encryption failed.', 'military-discounts' ) );
		}

		// Combine IV + tag + ciphertext and base64 encode.
		return base64_encode( $iv . $tag . $ciphertext );
	}

	/**
	 * Decrypt data.
	 *
	 * @param string $encrypted Encrypted data (base64 encoded).
	 * @return string Decrypted data.
	 * @throws Exception If decryption fails.
	 */
	public function decrypt( $encrypted ) {
		// Check if encryption is disabled.
		if ( ! md_is_encryption_enabled() ) {
			return base64_decode( $encrypted );
		}

		$key = $this->get_key();
		if ( empty( $key ) ) {
			throw new Exception( esc_html__( 'Encryption key not available.', 'military-discounts' ) );
		}

		$data = base64_decode( $encrypted );
		if ( false === $data ) {
			throw new Exception( esc_html__( 'Invalid encrypted data.', 'military-discounts' ) );
		}

		$iv_length = openssl_cipher_iv_length( self::CIPHER );

		// Extract IV, tag, and ciphertext.
		$iv         = substr( $data, 0, $iv_length );
		$tag        = substr( $data, $iv_length, self::TAG_LENGTH );
		$ciphertext = substr( $data, $iv_length + self::TAG_LENGTH );

		$plaintext = openssl_decrypt(
			$ciphertext,
			self::CIPHER,
			$key,
			OPENSSL_RAW_DATA,
			$iv,
			$tag
		);

		if ( false === $plaintext ) {
			throw new Exception( esc_html__( 'Decryption failed.', 'military-discounts' ) );
		}

		return $plaintext;
	}

	/**
	 * Get the encryption key.
	 *
	 * Priority:
	 * 1. .env file (MD_ENCRYPTION_KEY)
	 * 2. WordPress option (encrypted with wp_salt)
	 * 3. Auto-generate and store
	 *
	 * @return string The encryption key.
	 */
	private function get_key() {
		if ( null !== $this->key ) {
			return $this->key;
		}

		// 1. Try .env file.
		$env_key = $this->get_key_from_env();
		if ( ! empty( $env_key ) ) {
			$this->key = $env_key;
			return $this->key;
		}

		// 2. Try WordPress option.
		$stored_key = get_option( 'md_encryption_key' );
		if ( ! empty( $stored_key ) ) {
			// Decrypt the stored key using wp_salt.
			$this->key = $this->decrypt_stored_key( $stored_key );
			return $this->key;
		}

		// 3. Auto-generate and store.
		$this->key = $this->generate_and_store_key();
		return $this->key;
	}

	/**
	 * Get encryption key from .env file.
	 *
	 * @return string|null The key or null if not found.
	 */
	private function get_key_from_env() {
		// Check for defined constant first.
		if ( defined( 'MD_ENCRYPTION_KEY' ) && ! empty( MD_ENCRYPTION_KEY ) ) {
			return MD_ENCRYPTION_KEY;
		}

		// Try to read from .env file in parent directory of wp-content.
		$env_paths = array(
			ABSPATH . '.env',
			dirname( ABSPATH ) . '/.env',
			dirname( dirname( ABSPATH ) ) . '/.env',
		);

		foreach ( $env_paths as $env_path ) {
			if ( file_exists( $env_path ) && is_readable( $env_path ) ) {
				$env_content = file_get_contents( $env_path );
				if ( preg_match( '/^MD_ENCRYPTION_KEY=(.+)$/m', $env_content, $matches ) ) {
					return trim( $matches[1], '"\'  ' );
				}
			}
		}

		return null;
	}

	/**
	 * Decrypt a stored key using wp_salt.
	 *
	 * @param string $stored_key The stored encrypted key.
	 * @return string The decrypted key.
	 */
	private function decrypt_stored_key( $stored_key ) {
		$salt = wp_salt( 'auth' );
		$data = base64_decode( $stored_key );

		$iv_length  = openssl_cipher_iv_length( 'aes-256-cbc' );
		$iv         = substr( $data, 0, $iv_length );
		$ciphertext = substr( $data, $iv_length );

		$key = openssl_decrypt(
			$ciphertext,
			'aes-256-cbc',
			hash( 'sha256', $salt ),
			OPENSSL_RAW_DATA,
			$iv
		);

		return $key ? $key : '';
	}

	/**
	 * Generate a new encryption key and store it.
	 *
	 * @return string The generated key.
	 */
	private function generate_and_store_key() {
		// Generate a 32-byte key for AES-256.
		$key = bin2hex( openssl_random_pseudo_bytes( 32 ) );

		// Encrypt the key using wp_salt before storing.
		$salt      = wp_salt( 'auth' );
		$iv_length = openssl_cipher_iv_length( 'aes-256-cbc' );
		$iv        = openssl_random_pseudo_bytes( $iv_length );

		$encrypted = openssl_encrypt(
			$key,
			'aes-256-cbc',
			hash( 'sha256', $salt ),
			OPENSSL_RAW_DATA,
			$iv
		);

		$stored_value = base64_encode( $iv . $encrypted );
		update_option( 'md_encryption_key', $stored_value );

		return $key;
	}

	/**
	 * Check if an encryption key is available from .env.
	 *
	 * @return bool True if .env key is available.
	 */
	public function has_env_key() {
		return ! empty( $this->get_key_from_env() );
	}

	/**
	 * Test encryption/decryption.
	 *
	 * @return bool True if encryption is working.
	 */
	public function test_encryption() {
		try {
			$test_string = 'Military Discounts encryption test ' . time();
			$encrypted   = $this->encrypt( $test_string );
			$decrypted   = $this->decrypt( $encrypted );

			return $test_string === $decrypted;
		} catch ( Exception $e ) {
			return false;
		}
	}
}
