<?php
/**
 * Address Helper Functions
 * TrackSite Construction Management System
 * 
 * Helper functions for handling Philippines addresses
 */

if (!defined('TRACKSITE_INCLUDED')) {
    define('TRACKSITE_INCLUDED', true);
}

/**
 * Parse address JSON from database
 * 
 * @param string $addresses_json JSON string from database
 * @return array Parsed addresses array
 */
function parseAddresses($addresses_json) {
    if (empty($addresses_json)) {
        return [
            'current' => [
                'address' => '',
                'province' => '',
                'city' => '',
                'barangay' => ''
            ],
            'permanent' => [
                'address' => '',
                'province' => '',
                'city' => '',
                'barangay' => ''
            ]
        ];
    }
    
    $addresses = json_decode($addresses_json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Address JSON parse error: " . json_last_error_msg());
        return [
            'current' => ['address' => '', 'province' => '', 'city' => '', 'barangay' => ''],
            'permanent' => ['address' => '', 'province' => '', 'city' => '', 'barangay' => '']
        ];
    }
    
    return $addresses;
}

/**
 * Format address for display
 * 
 * @param array $address Address array
 * @return string Formatted address string
 */
function formatAddress($address) {
    if (empty($address)) {
        return 'N/A';
    }
    
    $parts = [];
    
    if (!empty($address['address'])) {
        $parts[] = $address['address'];
    }
    if (!empty($address['barangay'])) {
        $parts[] = 'Brgy. ' . $address['barangay'];
    }
    if (!empty($address['city'])) {
        $parts[] = $address['city'];
    }
    if (!empty($address['province'])) {
        $parts[] = $address['province'];
    }
    
    return !empty($parts) ? implode(', ', $parts) : 'N/A';
}

/**
 * Get full current address
 * 
 * @param string $addresses_json JSON string from database
 * @return string Formatted current address
 */
function getCurrentAddress($addresses_json) {
    $addresses = parseAddresses($addresses_json);
    return formatAddress($addresses['current']);
}

/**
 * Get full permanent address
 * 
 * @param string $addresses_json JSON string from database
 * @return string Formatted permanent address
 */
function getPermanentAddress($addresses_json) {
    $addresses = parseAddresses($addresses_json);
    return formatAddress($addresses['permanent']);
}

/**
 * Parse identification data JSON
 * 
 * @param string $ids_json JSON string from database
 * @return array Parsed IDs array
 */
function parseIdentificationData($ids_json) {
    if (empty($ids_json)) {
        return [
            'primary' => ['type' => '', 'number' => ''],
            'additional' => []
        ];
    }
    
    $ids = json_decode($ids_json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("IDs JSON parse error: " . json_last_error_msg());
        return [
            'primary' => ['type' => '', 'number' => ''],
            'additional' => []
        ];
    }
    
    return $ids;
}

/**
 * Get primary ID formatted
 * 
 * @param string $ids_json JSON string from database
 * @return string Formatted primary ID string
 */
function getPrimaryId($ids_json) {
    $ids = parseIdentificationData($ids_json);
    
    if (empty($ids['primary']['type']) || empty($ids['primary']['number'])) {
        return 'N/A';
    }
    
    return $ids['primary']['type'] . ': ' . $ids['primary']['number'];
}

/**
 * Get all additional IDs
 * 
 * @param string $ids_json JSON string from database
 * @return array Array of additional IDs
 */
function getAdditionalIds($ids_json) {
    $ids = parseIdentificationData($ids_json);
    return $ids['additional'] ?? [];
}

/**
 * Format phone number for display (Philippines)
 * 
 * @param string $phone Phone number
 * @return string Formatted phone number
 */
function formatPhoneNumber($phone) {
    if (empty($phone)) {
        return 'N/A';
    }
    
    // Remove any non-numeric characters except +
    $phone = preg_replace('/[^\d+]/', '', $phone);
    
    // Format: +63 912 345 6789
    if (preg_match('/^\+63(\d{3})(\d{3})(\d{4})$/', $phone, $matches)) {
        return '+63 ' . $matches[1] . ' ' . $matches[2] . ' ' . $matches[3];
    }
    
    // Format: 0912 345 6789
    if (preg_match('/^0(\d{3})(\d{3})(\d{4})$/', $phone, $matches)) {
        return '0' . $matches[1] . ' ' . $matches[2] . ' ' . $matches[3];
    }
    
    return $phone;
}

/**
 * Validate Philippines phone number
 * 
 * @param string $phone Phone number to validate
 * @return array ['valid' => bool, 'message' => string, 'formatted' => string]
 */
function validatePhilippinePhone($phone) {
    $result = ['valid' => false, 'message' => '', 'formatted' => ''];
    
    if (empty($phone)) {
        $result['message'] = 'Phone number is required';
        return $result;
    }
    
    // Remove spaces and dashes
    $phone_cleaned = preg_replace('/[\s\-]/', '', $phone);
    
    // Check if it starts with +63 and has 12 digits total
    if (preg_match('/^\+63\d{10}$/', $phone_cleaned)) {
        $result['valid'] = true;
        $result['formatted'] = $phone_cleaned;
        return $result;
    }
    
    // Check if it's exactly 11 digits starting with 0
    if (preg_match('/^0\d{10}$/', $phone_cleaned)) {
        $result['valid'] = true;
        $result['formatted'] = $phone_cleaned;
        return $result;
    }
    
    // Check if it's exactly 11 digits
    if (preg_match('/^\d{11}$/', $phone_cleaned)) {
        $result['valid'] = true;
        $result['formatted'] = $phone_cleaned;
        return $result;
    }
    
    $result['message'] = 'Phone number must be exactly 11 digits (e.g., 09123456789 or +639123456789)';
    return $result;
}

/**
 * Get relationship badge HTML
 * 
 * @param string $relationship Relationship type
 * @return string HTML for relationship badge
 */
function getRelationshipBadge($relationship) {
    if (empty($relationship)) {
        return '<span class="badge badge-secondary">N/A</span>';
    }
    
    $colors = [
        'Parent' => 'primary',
        'Sibling' => 'info',
        'Spouse' => 'success',
        'Child' => 'warning',
        'Guardian' => 'primary',
        'Friend' => 'secondary',
        'Relative' => 'info'
    ];
    
    $color = $colors[$relationship] ?? 'secondary';
    
    return '<span class="badge badge-' . $color . '">' . htmlspecialchars($relationship) . '</span>';
}

/**
 * Validate address completeness
 * 
 * @param array $address Address array
 * @param bool $required Whether all fields are required
 * @return array ['valid' => bool, 'missing' => array]
 */
function validateAddress($address, $required = true) {
    $result = ['valid' => true, 'missing' => []];
    
    if (!$required) {
        return $result;
    }
    
    $required_fields = ['address', 'province', 'city', 'barangay'];
    
    foreach ($required_fields as $field) {
        if (empty($address[$field])) {
            $result['valid'] = false;
            $result['missing'][] = ucfirst(str_replace('_', ' ', $field));
        }
    }
    
    return $result;
}

/**
 * Compare two addresses
 * 
 * @param array $address1 First address
 * @param array $address2 Second address
 * @return bool True if addresses are the same
 */
function addressesMatch($address1, $address2) {
    return (
        $address1['address'] === $address2['address'] &&
        $address1['province'] === $address2['province'] &&
        $address1['city'] === $address2['city'] &&
        $address1['barangay'] === $address2['barangay']
    );
}

/**
 * Get address summary (short version)
 * 
 * @param array $address Address array
 * @return string Short address summary
 */
function getAddressSummary($address) {
    if (empty($address['city']) && empty($address['province'])) {
        return 'N/A';
    }
    
    $parts = [];
    
    if (!empty($address['city'])) {
        $parts[] = $address['city'];
    }
    if (!empty($address['province'])) {
        $parts[] = $address['province'];
    }
    
    return implode(', ', $parts);
}
?>