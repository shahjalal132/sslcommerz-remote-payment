<?php

/**
 * Payment Decryption Service
 *
 * Handles decryption of Laravel-encrypted payment data
 */

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Singleton;
use BOILERPLATE\Inc\Traits\Program_Logs;

class Payment_Decryption {

    use Singleton;
    use Program_Logs;

    /**
     * Decrypt Laravel-encrypted payment data
     *
     * @param string $encryptedPayload Base64-encoded encrypted JSON string
     * @param string $encryptionKey 32-character encryption key
     * @return array Decrypted payment data array
     * @throws \Exception If decryption fails
     */
    public function decrypt_payment_data( $encryptedPayload, $encryptionKey ) {
        try {
            // Step 1: Decode base64 to get JSON string
            $jsonPayload = base64_decode( $encryptedPayload, true );
            if ( $jsonPayload === false ) {
                throw new \Exception( 'Invalid base64 encoded data' );
            }

            // Step 2: Decode JSON to get encryption components
            $payload = json_decode( $jsonPayload, true );
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                throw new \Exception( 'Invalid JSON format: ' . json_last_error_msg() );
            }

            // Step 3: Validate required fields
            if ( ! isset( $payload['iv'] ) || ! isset( $payload['value'] ) || ! isset( $payload['mac'] ) ) {
                throw new \Exception( 'Missing required encryption fields' );
            }

            // Step 4: Decode IV and encrypted value
            $iv = base64_decode( $payload['iv'], true );
            $encryptedValue = base64_decode( $payload['value'], true );

            if ( $iv === false || $encryptedValue === false ) {
                throw new \Exception( 'Invalid base64 encoded IV or value' );
            }

            // Step 5: Verify HMAC for integrity
            $calculatedMac = hash_hmac( 'sha256', $payload['iv'] . $payload['value'], hash( 'sha256', $encryptionKey, true ), true );
            $providedMac = base64_decode( $payload['mac'], true );

            if ( ! hash_equals( $calculatedMac, $providedMac ) ) {
                throw new \Exception( 'HMAC verification failed - data may be tampered' );
            }

            // Step 6: Derive encryption key from provided key
            // Laravel uses the first 32 bytes of SHA256 hash of the key
            $key = hash( 'sha256', $encryptionKey, true );

            // Step 7: Decrypt the data
            $decrypted = openssl_decrypt(
                $encryptedValue,
                'AES-256-CBC',
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ( $decrypted === false ) {
                $error = openssl_error_string();
                throw new \Exception( 'Decryption failed: ' . ( $error ? $error : 'Unknown error' ) );
            }

            // Step 8: Decode JSON to get original array
            $data = json_decode( $decrypted, true );
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                throw new \Exception( 'Failed to decode decrypted data: ' . json_last_error_msg() );
            }

            return $data;

        } catch ( \Exception $e ) {
            // Log error without sensitive data
            $this->put_program_logs( 'Payment decryption error: ' . $e->getMessage() );
            throw $e;
        }
    }

}
