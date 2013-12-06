<?php

class FormValidator {

    protected $messages = array();
    protected $errors = array();
    protected $rules = array();
    protected $fields = array();
    protected $has_post_data = FALSE;

    function __construct($use_get_action = FALSE) {
        $this->has_post_data = $_SERVER['REQUEST_METHOD'] == 'POST' || $use_get_action && $_SERVER['REQUEST_METHOD'] == 'GET';
    }

    /*     * * ADD NEW RULE FUNCTIONS BELOW THIS LINE ** */

    /**
     * email
     * @param string $message
     * @return FormValidator
     */
    public function email($message = '') {
        $message = ( empty($message) ) ? '%s is an invalid email address.' : $message;
        $this->set_rule(__FUNCTION__, function($email) {
            return ( filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE ) ? FALSE : TRUE;
        }, $message);
        return $this;
    }

    /**
     * required
     * @param string $message
     * @return FormValidator
     */
    public function required($message = '') {
        $message = ( empty($message) ) ? '%s is required.' : $message;
        $this->set_rule(__FUNCTION__, function($string) {
            return ( empty($string) ) ? FALSE : TRUE;
        }, $message);
        return $this;
    }

    /**
     * numbersonly
     * @param string $message
     * @return FormValidator
     */
    public function numeric($message = '') {
        $message = ( empty($message) ) ? '%s must consist of numbers only.' : $message;
        $this->set_rule(__FUNCTION__, function($string) {
            return ( preg_match('/^[-]?[0-9.]+$/', $string) ) ? TRUE : FALSE;
        }, $message);
        return $this;
    }

    /**
     *
     * @param int $len
     * @param string $message
     * @return FormValidator
     */
    public function minlength($minlen, $message = '') {
        $message = ( empty($message) ) ? '%s must be at least ' . $minlen . ' characters or longer.' : $message;
        $this->set_rule(__FUNCTION__, function($string) use ($minlen) {
            return ( strlen(trim($string)) < $minlen ) ? FALSE : TRUE;
        }, $message);
        return $this;
    }

    /**
     *
     * @param int $len
     * @param string $message
     * @return FormValidator
     */
    public function maxlength($maxlen, $message = '') {
        $message = ( empty($message) ) ? '%s must be no longer than ' . $maxlen . ' characters.' : $message;
        $this->set_rule(__FUNCTION__, function($string) use ($maxlen) {
            return ( strlen(trim($string)) > $maxlen ) ? FALSE : TRUE;
        }, $message);
        return $this;
    }

    /**
     *
     * @param int $len
     * @param string $message
     * @return FormValidator
     */
    public function length($len, $message = '') {
        $message = ( empty($message) ) ? '%s must be exactly ' . $len . ' characters in length.' : $message;
        $this->set_rule(__FUNCTION__, function($string) use ($len) {
            return ( strlen(trim($string)) == $len ) ? TRUE : FALSE;
        }, $message);
        return $this;
    }

    /**
     *
     * @param string $field
     * @param string $label
     * @param string $message
     * @return FormValidator
     */
    public function matches($field, $label, $message = '') {
        $message = ( empty($message) ) ? '%s must match ' . $label . '.' : $message;

        $matchvalue = $this->getval($field);

        $this->set_rule(__FUNCTION__, function($string) use ($matchvalue) {
            return ( (string)$matchvalue == (string)$string ) ? TRUE : FALSE;
        }, $message);
        return $this;
    }

    /**
     *
     * @param string $field
     * @param string $label
     * @param string $message
     * @return FormValidator
     */
    public function not_matches($field, $label, $message = '') {
        $message = ( empty($message) ) ? '%s must not match ' . $label . '.' : $message;

        $matchvalue = $this->getval($field);

        $this->set_rule(__FUNCTION__, function($string) use ($matchvalue) {
            return ( (string)$matchvalue == (string)$string ) ? FALSE : TRUE;
        }, $message);
        return $this;
    }

    /*     * * ADD NEW RULE FUNCTIONS ABOVE THIS LINE ** */

    /**
     * callback
     * @param string $name
     * @param mixed $function
     * @param string $message
     * @return FormValidator
     */
    public function callback($name, $function, $message = '') {
        if (is_callable($function)) {
            // set rule and function
            $this->set_rule($name, $function, $message);
        } elseif (is_string($function) && preg_match($function, 'callback') !== FALSE) {
            // we can parse this as a regexp. set rule function accordingly.
            $this->set_rule($name, function($value) use ($function) {
                return ( preg_match($function, $value) ) ? TRUE : FALSE;
            }, $message);
        } else {
            // just set a rule function to check equality.
            $this->set_rule($name, function($value) use ( $function) {
                return ( (string)$value === (string)$function ) ? TRUE : FALSE;
            }, $message);
        }
        return $this;
    }

    /**
     * validate
     * @param string $key
     * @param string $label
     * @return bool
     */
    public function validate($key, $label = '') {
        // do not attempt to validate when no post data is present
        if ($this->has_post_data) {
            // set up field name for error message
            if (!empty($label)) {
                $this->fields[$key] = $label;
            }
            // try each rule function
            foreach ($this->rules as $rule => $function) {
                if (is_callable($function)) {
                    if ($function($this->getval($key)) === FALSE) {
                        $this->register_error($rule, $key);
                        // reset rules
                        $this->rules = array();
                        return FALSE;
                    }
                } else {
                    $this->register_error($rule, $key, 'Invalid function for rule');
                    $this->rules = array();
                    return FALSE;
                }
            }
            // reset rules
            $this->rules = array();
            return TRUE;
        }
    }

    /**
     * has_errors
     * @return bool
     */
    public function has_errors() {
        return ( count($this->errors) > 0 ) ? TRUE : FALSE;
    }

    /**
     * set_error_message
     * @param string $rule
     * @param string $message
     */
    public function set_error_message($rule, $message) {
        $this->messages[$rule] = $message;
    }

    /**
     * get_error
     * @param string $field
     * @return string
     */
    public function get_error($field) {
        return $this->errors[$field];
    }

    /**
     * get_all_errors
     * @return array
     */
    public function get_all_errors() {
        return $this->errors;
    }

    /* public function __set($key, $value) {
      $this->messages[$key] = $value;
      }

      public function __get($key) {
      return $this->messages[$key];
      } */

    /**
     * getval
     * @param string $key
     * @return mixed
     */
    protected function getval($key) {
        return ( isset($_POST[$key]) ) ? $_POST[$key] : FALSE;
    }

    /**
     * register_error
     * @param string $rule
     * @param string $key
     * @param string $msg_override
     */
    protected function register_error($rule, $key, $msg_override = '') {
        $message = ( empty($msg_override) ) ? $this->messages[$rule] : $msg_override;
        $field = $this->fields[$key];

        if (empty($message)) $message = '%s has an error.';

        if (empty($field)) $field = "Field with the name of '$key'";

        $this->errors[$key] = sprintf($message, $field);
    }

    /**
     * set_rule
     * @param string $rule
     * @param closure $function
     * @param string $message
     */
    protected function set_rule($rule, $function, $message = '') {
        // do not attempt to validate when no post data is present
        if ($this->has_post_data) {
            if (is_callable($function)) {
                $this->rules[$rule] = $function;
                if (!empty($message)) {
                    $this->messages[$rule] = $message;
                }
            }
        }
    }

}

// end of file