<?php
//==============================================================================
/*
Functions for handling TOTP (time-based one time password) codes
*/
//==============================================================================
if (!defined('TOTP_FUNCT_DEFINED')):
//==============================================================================
/*
Function verify_totp_code

This function validates a 6-digit TOTP code against a Base32 secret key.

Parameters:
$secret      - Base32-encoded secret key
$code        - 6-digit code entered by the user
$discrepancy - Number of 30-second windows to check before/after (default: 1)
*/
//==============================================================================

function verify_totp_code(string $secret, string $code, int $discrepancy = 1): bool
{
    // Sanity check on the 6-digit code
    if (!preg_match('/^[0-9]{6}$/', $code)) {
        return false;
    }

    $secret = strtoupper($secret);
    $binary_secret = base32_decode($secret);

    if ($binarySecret === false) {
        return false; // Invalid Base32 string
    }

    // Current 30-second time slice
    $current_time_slice = floor(time() / 30);

    // Check adjacent time windows to account for slight clock drift
    for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
        $time_slice = $current_time_slice + $i;

        // Pack time slice into an 8-byte big-endian binary string
        $time_binary = pack('N*', 0) . pack('N*', $time_slice);

        // Generate HMAC-SHA1 hash
        $hmac = hash_hmac('sha1', $time_binary, $binary_secret, true);

        // Dynamic truncation (RFC 4226)
        $offset = ord($hmac[19]) & 0x0F;
        $hash_part = substr($hmac, $offset, 4);

        $value = unpack('N', $hash_part)[1] & 0x7FFFFFFF;
        $calculated_code = str_pad((string)($value % 1000000), 6, '0', STR_PAD_LEFT);

        // Timing-safe comparison to protect against timing attacks
        if (hash_equals($calculated_code, $code)) {
            return true;
        }
    }

    return false;
}

//==============================================================================
/*
Function base32_decode

This function decodes an RFC 4648 Base32-encoded string into raw binary data.
 */
//==============================================================================

function base32_decode(string $base32): string|false
{
    $base32_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $base32 = rtrim(strtoupper($base32), '='); // Remove padding

    $binary_string = '';
    for ($i = 0; $i < strlen($base32); $i++) {
        $position = strpos($base32_chars, $base32[$i]);
        if ($position === false) {
            return false;
        }
        $binary_string .= sprintf('%05b', $position);
    }

    $bytes = [];
    foreach (str_split($binary_string, 8) as $chunk) {
        if (strlen($chunk) === 8) {
            $bytes[] = chr(bindec($chunk));
        }
    }

    return implode('', $bytes);
}

//==============================================================================
/*
Function generate_totp_secret

This functon enerates a cryptographically secure, random Base32 secret key.
*/
//==============================================================================

function generate_totp_secret(int $length = 16): string
{
    // Standard Base32 alphabet (RFC 4648) used by authenticator apps
    $base32_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $case32_count = strlen($base32_chars);

    $secret = '';

    // Generate each character using cryptographically secure random bytes
    for ($i = 0; $i < $length; $i++) {
        // random_int() uses /dev/urandom or OS-level CSPRNG
        $random_index = random_int(0, $case32_count - 1);
        $secret .= $base32_chars[$random_index];
    }

    return $secret;
}

//==============================================================================
define ('TOTP_FUNCT_DEFINED',true);
endif;
//==============================================================================
