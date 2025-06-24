<?php
// helpers.php

function sanitizeInput(array $input, array $allowedFields): array {
    $clean = [];

    foreach ($allowedFields as $field => $type) {
        $value = trim($input[$field] ?? '');

        switch ($type) {
            case 'email':
                $value = filter_var($value, FILTER_SANITIZE_EMAIL);
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Invalid email format for $field.");
                }
                break;

            case 'text':
                // Basic alphanumeric text, strip tags, prevent XSS
                $value = strip_tags($value);
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                break;

            case 'int':
                if (!ctype_digit($value)) {
                    throw new Exception("Invalid integer value for $field.");
                }
                $value = (int)$value;
                break;

            case 'password':
                // Don't sanitize passwords (to preserve special chars), just trim
                $value = $input[$field] ?? '';
                break;

            default:
                throw new Exception("Unknown validation type: $type");
        }

        $clean[$field] = $value;
    }

    return $clean;
}
