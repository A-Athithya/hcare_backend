<?php
/**
 * Validator Helper
 * 
 * Provides validation methods for request data
 */

class Validator {
    
    /**
     * Validate email format
     */
    public static function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate required fields
     */
    public static function required($value) {
        if (is_string($value)) {
            return trim($value) !== '';
        }
        return !empty($value);
    }
    
    /**
     * Validate minimum length
     */
    public static function minLength($value, $min) {
        return strlen($value) >= $min;
    }
    
    /**
     * Validate maximum length
     */
    public static function maxLength($value, $max) {
        return strlen($value) <= $max;
    }
    
    /**
     * Validate numeric value
     */
    public static function numeric($value) {
        return is_numeric($value);
    }
    
    /**
     * Validate date format
     */
    public static function date($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * Validate datetime format
     */
    public static function datetime($datetime) {
        return self::date($datetime, 'Y-m-d H:i:s');
    }
    
    /**
     * Validate phone number (basic)
     */
    public static function phone($phone) {
        return preg_match('/^[0-9\s\-\+\(\)]+$/', $phone) === 1;
    }
    
    /**
     * Validate that value is in array
     */
    public static function in($value, $array) {
        return in_array($value, $array);
    }
    
    /**
     * Sanitize string input
     */
    public static function sanitize($value) {
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate multiple fields at once
     * 
     * @param array $data The data to validate
     * @param array $rules The validation rules
     * @return array|true Returns true if valid, or array of errors
     */
    public static function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $rulesArray = explode('|', $fieldRules);
            
            foreach ($rulesArray as $rule) {
                // Parse rule and parameters
                $params = [];
                if (strpos($rule, ':') !== false) {
                    list($rule, $paramString) = explode(':', $rule, 2);
                    $params = explode(',', $paramString);
                }
                
                // Execute validation
                switch ($rule) {
                    case 'required':
                        if (!self::required($value)) {
                            $errors[$field][] = "$field is required";
                        }
                        break;
                        
                    case 'email':
                        if ($value && !self::email($value)) {
                            $errors[$field][] = "$field must be a valid email";
                        }
                        break;
                        
                    case 'min':
                        if ($value && !self::minLength($value, $params[0])) {
                            $errors[$field][] = "$field must be at least {$params[0]} characters";
                        }
                        break;
                        
                    case 'max':
                        if ($value && !self::maxLength($value, $params[0])) {
                            $errors[$field][] = "$field must not exceed {$params[0]} characters";
                        }
                        break;
                        
                    case 'numeric':
                        if ($value && !self::numeric($value)) {
                            $errors[$field][] = "$field must be numeric";
                        }
                        break;
                        
                    case 'date':
                        if ($value && !self::date($value)) {
                            $errors[$field][] = "$field must be a valid date";
                        }
                        break;
                        
                    case 'in':
                        if ($value && !self::in($value, $params)) {
                            $errors[$field][] = "$field must be one of: " . implode(', ', $params);
                        }
                        break;
                }
            }
        }
        
        return empty($errors) ? true : $errors;
    }
}
