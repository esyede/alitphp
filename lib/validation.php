<?php
/**
*   Tiny Validation Library for Alit PHP
*   @package     Alit PHP
*   @subpackage  Alit.Validation
*   @copyright   Copyright (c) 2017-2011 Suyadi. All Rights Reserved.
*   @license     https://opensource.org/licenses/MIT The MIT License (MIT)
*   @author      Suyadi <suyadi.1992@gmail.com>
*/


class Validation extends \Factory {

    protected static
        $fields=[],
        $filter_methods=[],
        $validation_methods=[],
        $validation_methods_errors=[];
    protected
        $lang,
        $errors=[],
        $filter_rules=[],
        $validation_rules=[],
        $field_char_to_remove=['_','-'];
    static
        $basic_tags='
            <br><p><a><strong><b><i><em><img>'.
            '<blockquote><code><dd><dl><hr><h1><h2><h3>'.
            '<h4><h5><h6><label><ul><li><span><sub><sup>';
    static
        $en_noise_words="
            about,after,all,also,an,and,another,any,are,as,at,be,because,been,before,".
            "being,between,both,but,by,came,can,come,could,did,do,each,for,from,get,".
            "got,has,had,he,have,her,here,him,himself,his,how,if,in,into,is,it,its,it's,like,".
            "make,many,me,might,more,most,much,must,my,never,now,of,on,only,or,other,".
            "our,out,over,said,same,see,should,since,some,still,such,take,than,that,".
            "the,their,them,then,there,these,they,this,those,through,to,too,under,up,".
            "very,was,way,we,well,were,what,where,which,while,who,with,would,you,your,a,".
            "b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,$,1,2,3,4,5,6,7,8,9,0,_";
    static $default_error_messages=[
        'validate_required'                 => 'The {field} field is required',
        'validate_valid_email'              => 'The {field} field must be a valid email address',
        'validate_max_len'                  => 'The {field} field needs to be {param} characters or less',
        'validate_min_len'                  => 'The {field} field needs to be at least {param} characters',
        'validate_exact_len'                => 'The {field} field needs to be exactly {param} characters',
        'validate_alpha'                    => 'The {field} field may only contain letters',
        'validate_alpha_numeric'            => 'The {field} field may only contain letters and numbers',
        'validate_alpha_numeric_space'      => 'The {field} field may only contain letters, numbers and spaces',
        'validate_alpha_dash'               => 'The {field} field may only contain letters and dashes',
        'validate_alpha_space'              => 'The {field} field may only contain letters and spaces',
        'validate_numeric'                  => 'The {field} field must be a number',
        'validate_integer'                  => 'The {field} field must be a number without a decimal',
        'validate_boolean'                  => 'The {field} field has to be either true or false',
        'validate_float'                    => 'The {field} field must be a number with a decimal point (float)',
        'validate_valid_url'                => 'The {field} field has to be a URL',
        'validate_url_exists'               => 'The {field} URL does not exist',
        'validate_valid_ip'                 => 'The {field} field needs to be a valid IP address',
        'validate_valid_ipv4'               => 'The {field} field needs to contain a valid IPv4 address',
        'validate_valid_ipv6'               => 'The {field} field needs to contain a valid IPv6 address',
        'validate_guidv4'                   => 'The {field} field needs to contain a valid GUID',
        'validate_valid_cc'                 => 'The {field} is not a valid credit card number',
        'validate_valid_name'               => 'The {field} should be a full name',
        'validate_contains'                 => 'The {field} can only be one of the following: {param}',
        'validate_contains_list'            => 'The {field} is not a valid option',
        'validate_doesnt_contain_list'      => 'The {field} field contains a value that is not accepted',
        'validate_street_address'           => 'The {field} field needs to be a valid street address',
        'validate_date'                     => 'The {field} must be a valid date',
        'validate_min_numeric'              => 'The {field} field needs to be a numeric value, equal to, or higher than {param}',
        'validate_max_numeric'              => 'The {field} field needs to be a numeric value, equal to, or lower than {param}',
        'validate_min_age'                  => 'The {field} field needs to have an age greater than or equal to {param}',
        'validate_invalid'                  => 'The {field} field is invalid',
        'validate_starts'                   => 'The {field} field needs to start with {param}',
        'validate_extension'                => 'The {field} field can only have one of the following extensions: {param}',
        'validate_required_file'            => 'The {field} field is required',
        'validate_equalsfield'              => 'The {field} field does not equal {param} field',
        'validate_iban'                     => 'The {field} field needs to contain a valid IBAN',
        'validate_phone_number'             => 'The {field} field needs to be a valid Phone Number',
        'validate_regex'                    => 'The {field} field needs to contain a value with valid format',
        'validate_valid_json_string'        => 'The {field} field needs to contain a valid JSON format string',
        'validate_valid_array_size_greater' => 'The {field} fields needs to be an array with a size, equal to, or higher than {param}',
        'validate_valid_array_size_lesser'  => 'The {field} fields needs to be an array with a size, equal to, or lower than {param}',
        'validate_valid_array_size_equal'   => 'The {field} fields needs to be an array with a size equal to {param}',
    ];

    // Class constructor
    function __construct($lang=false) {
        $fw=\Alit::instance();
        if ($lang) {
            if (is_array($lang))
                $this->lang=$lang;
            elseif (is_file($lang))
                if (file_exists($fw->hive['BASE']).str_replace('/',DIRECTORY_SEPARATOR,$lang))
                    $this->lang=$lang;
                else throw new \Exception('Language for validation does not exist!');
        }
        else $this->lang=self::$default_error_messages;
    }

    /**
    *   Shorthand method for inline validation
    *   @param   $data        array
    *   @param   $validators  array
    *   @return  bool|array
    */
    static function isvalid(array $data,array $validators) {
        $validation=self::instance();
        $validation->validation_rules($validators);
        if ($validation->run($data)===false)
            return $validation->get_readable_errors(false);
        else return true;
    }

    /**
    *   Shorthand method for running only the data filters
    *   @param   $data     array
    *   @param   $filters  array
    *   @return  mixed
    */
    static function filter_input(array $data,array $filters) {
        $validation=self::instance();
        return $validation->filter($data,$filters);
    }

    /**
    *   Magic method to generate the validation error messages
    *   @return string
    */
    function __toString() {
        return $this->get_readable_errors(true);
    }

    /**
    *   Perform XSS sanitation
    *   @param   $data  array
    *   @return  array
    */
    static function xss_clean(array $data) {
        foreach ($data as $k=>$v)
            $data[$k]=filter_var($v,FILTER_SANITIZE_STRING);
        return $data;
    }

    /**
    *   Adds a custom validation rule using a callback function.
    *   @param   $rule           string
    *   @param   $callback       callable
    *   @param   $error_message  string
    *   @return  bool
    */
    static function add_validator($rule,$callback,$error_message=null) {
        $method='validate_'.$rule;
        if (method_exists(__CLASS__,$method)
        ||isset(self::$validation_methods[$rule]))
            throw new \Exception("Validator rule '$rule' already exists.");
        self::$validation_methods[$rule]=$callback;
        if ($error_message)
            self::$validation_methods_errors[$rule]=$error_message;
        return true;
    }

    /**
    *   Adds a custom filter using a callback function
    *   @param   $rule      string
    *   @param   $callback  callable
    *   @return  bool
    */
    static function add_filter($rule,$callback) {
        $method='filter_'.$rule;
        if (method_exists(__CLASS__,$method)
        ||isset(self::$filter_methods[$rule]))
            throw new \Exception("Filter rule '$rule' already exists.");
        self::$filter_methods[$rule]=$callback;
        return true;
    }

    /**
    *   Helper method to safely extract an element from an array
    *   @param   $key      mixed
    *   @param   $array    array
    *   @param   $default  mixed
    *   @return  mixed
    */
    static function field($key,array $array,$default=null) {
        if (!is_array($array))
            return null;
        if (isset($array[$key]))
            return $array[$key];
        else return $default;
    }

    /**
    *   Getter/setter for the validation rules.
    *   @param   $rules  array
    *   @return  array
    */
    function validation_rules(array $rules=[]) {
        if (empty($rules))
            return $this->validation_rules;
        $this->validation_rules=$rules;
    }

    /**
    *   Getter/Setter for the filter rules.
    *   @param   $rules  array
    *   @return  array
    */
    function filter_rules(array $rules=[]) {
        if (empty($rules))
            return $this->filter_rules;
        $this->filter_rules=$rules;
    }

    /**
    *   Run the filtering and validation after each other
    *   @param   $data          array
    *   @param   $check_fields  bool
    *   @return  array
    */
    function run(array $data,$check_fields=false) {
        $data=$this->filter($data,$this->filter_rules());
        $validated=$this->validate($data,$this->validation_rules());
        if ($check_fields===true)
            $this->check_fields($data);
        if ($validated!==true)
            return false;
        return $data;
    }

    /**
    *   Ensure that the field counts match the validation rule counts
    *   @param  $data  array
    */
    private function check_fields(array $data) {
        $ruleset=$this->validation_rules();
        $mismatch=array_diff_key($data,$ruleset);
        $fields=array_keys($mismatch);
        foreach ($fields as $field)
            $this->errors[]=[
                'field'=>$field,
                'value'=>$data[$field],
                'rule'=>'mismatch',
                'param'=>null
            ];
    }

    /**
    *   Sanitize the input data.
    *   @param   $input        array
    *   @param   $fields       array|null
    *   @param   $utf8_encode  bool
    *   @return  array
    */
    function sanitize(array $input,array $fields=[],$utf8_encode=true) {
        $magic_quotes=(bool)get_magic_quotes_gpc();
        if (empty($fields))
            $fields=array_keys($input);
        $return=[];
        foreach ($fields as $field) {
            if (!isset($input[$field])) continue;
            else {
                $value=$input[$field];
                if (is_array($value))
                    $value=$this->sanitize($value);
                if (is_string($value)) {
                    if ($magic_quotes===true)
                        $value=stripslashes($value);
                    if (strpos($value,"\r")!==false)
                        $value=trim($value);
                    if (function_exists('iconv')
                    &&function_exists('mb_detect_encoding')
                    &&$utf8_encode) {
                        $current_encoding=mb_detect_encoding($value);
                        if ($current_encoding!='UTF-8'
                        &&$current_encoding!='UTF-16')
                            $value=iconv($current_encoding,'UTF-8',$value);
                    }
                    $value=filter_var($value,FILTER_SANITIZE_STRING);
                }
                $return[$field]=$value;
            }
        }
        return $return;
    }

    /**
    *   Return the error array from the last validation run
    *   @return  array
    */
    function errors() {
        return $this->errors;
    }

    /**
    *   Perform data validation against the provided ruleset
    *   @param   $input    mixed
    *   @param   $ruleset  array
    *   @return  mixed
    */
    function validate(array $input,array $ruleset) {
        $this->errors=[];
        foreach ($ruleset as $field=>$rules) {
            $rules=explode('|',$rules);
            $look_for=array('required_file','required');
            if (count(array_intersect($look_for,$rules))>0
            ||(isset($input[$field]))) {
                if (isset($input[$field])) {
                    if (is_array($input[$field])
                    &&in_array('required_file',$ruleset))
                        $input_array=$input[$field];
                    else $input_array=[$input[$field]];
                }
                else $input_array=[''];
                foreach ($input_array as $value) {
                    $input[$field]=$value;
                    foreach ($rules as $rule) {
                        $method=null;
                        $param=null;
                        if (strstr($rule,',')!==false) {
                            $rule=explode(',',$rule);
                            $method='validate_'.$rule[0];
                            $param=$rule[1];
                            $rule=$rule[0];
                            if (preg_match('/(?:(?:^|;)_([a-z_]+))/',$param,$matches))
                                if (isset($input[$matches[1]]))
                                    $param=str_replace('_'.$matches[1],$input[$matches[1]],$param);
                        }
                        else $method='validate_'.$rule;
                        if (is_callable(array($this,$method))) {
                            $result=$this->$method($field,$input,$param);
                            if (is_array($result))
                                if (array_search($result['field'],array_column($this->errors,'field'))===false)
                                    $this->errors[]=$result;
                        }
                        elseif (isset(self::$validation_methods[$rule])) {
                            $result=call_user_func(self::$validation_methods[$rule],$field,$input,$param);
                            if ($result===false)
                                if (array_search($result['field'],array_column($this->errors,'field'))===false)
                                    $this->errors[]=[
                                        'field'=>$field,
                                        'value'=>$input[$field],
                                        'rule'=>$rule,
                                        'param'=>$param
                                    ];
                        }
                        else throw new \Exception("Validator method '$method' does not exist.");
                    }
                }
            }
        }
        return (count($this->errors)>0)?$this->errors:true;
    }

    /**
    *   Set a readable name for a specified field names
    *   @param  $field          string
    *   @param  $readable_name  string
    */
    static function set_field_name($field,$readable_name) {
        self::$fields[$field]=$readable_name;
    }

    /**
    *   Set readable name for specified fields in an array
    *   @param  $array  array
    */
    static function set_field_names(array $array) {
        foreach ($array as $field=>$readable_name)
            self::set_field_name($field,$readable_name);
    }

    /**
    *   Set a custom error message for a validation rule
    *   @param  $rule     string
    *   @param  $message  string
    */
    static function set_error_message($rule,$message) {
        $validation=self::instance();
        self::$validation_methods_errors[$rule]=$message;
    }

    /**
    *   Set custom error messages for validation rules in an array
    *   @param  $array  array
    */
    static function set_error_messages(array $array) {
        foreach ($array as $rule=>$message)
            self::set_error_message($rule,$message);
    }

    /**
    *   Get error messages.
    *   @return  array
    */
    protected function get_messages() {
        return $this->lang;
    }

    /**
    *   Process the validation errors and return human readable error messages.
    *   @param   $convert_to_string  bool
    *   @param   $field_class        string
    *   @param   $error_class        string
    *   @return  array|string
    */
    function get_readable_errors($convert_to_string=false,$field_class='validation-field',$error_class='validation-error-message') {
        if (empty($this->errors))
            return ($convert_to_string)?null:[];
        $resp=[];
        $messages=$this->get_messages();
        foreach ($this->errors as $e) {
            $field=ucwords(str_replace($this->field_char_to_remove,chr(32),$e['field']));
            $param=$e['param'];
            if (array_key_exists($e['field'],self::$fields)) {
                $field=self::$fields[$e['field']];
                if (array_key_exists($param,self::$fields))
                    $param=self::$fields[$e['param']];
            }
            if (isset($messages[$e['rule']])) {
                if (is_array($param))
                    $param=implode(',',$param);
                $message=str_replace('{param}',$param,
                    str_replace('{field}','<span class="'.$field_class.'">'.$field.'</span>',$messages[$e['rule']])
                );
                $resp[]=$message;
            }
            else throw new \Exception('Rule "'.$e['rule'].'" does not have an error message');
        }
        if (!$convert_to_string)
            return $resp;
        else {
            $buffer='';
            foreach ($resp as $s)
                $buffer.="<span class=\"$error_class\">$s</span>";
            return $buffer;
        }
    }

    /**
    *   Process the validation errors and return an array of errors with field names as keys
    *   @param   $convert_to_string
    *   @return  array|null
    */
    function get_errors_array($convert_to_string=null) {
        if (empty($this->errors))
            return ($convert_to_string)?null:[];
        $resp=[];
        $messages=$this->get_messages();
        foreach ($this->errors as $e) {
            $field=ucwords(str_replace(['_','-'],chr(32),$e['field']));
            $param=$e['param'];
            if (array_key_exists($e['field'],self::$fields)) {
                $field=self::$fields[$e['field']];
                if (array_key_exists($param,self::$fields))
                    $param=self::$fields[$e['param']];
            }
            if (isset($messages[$e['rule']])) {
                if (!isset($resp[$e['field']])) {
                    if (is_array($param))
                        $param=implode(',',$param);
                    $message=str_replace('{param}',$param,
                        str_replace('{field}',$field,$messages[$e['rule']])
                    );
                    $resp[$e['field']]=$message;
                }
            }
            else throw new \Exception('Rule "'.$e['rule'].'" does not have an error message');
        }
        return $resp;
    }

    /**
    *   Filter the input data according to the specified filter set
    *   @param   $input      mixed
    *   @param   $filterset  array
    *   @return  mixed
    */
    function filter(array $input,array $filterset) {
        foreach ($filterset as $field=>$filters) {
            if (!array_key_exists($field,$input))
                continue;
            $filters=explode('|',$filters);
            foreach ($filters as $filter) {
                $params=null;
                if (strstr($filter,',')!==false) {
                    $filter=explode(',',$filter);
                    $params=array_slice($filter,1,count($filter)-1);
                    $filter=$filter[0];
                }
                if (is_array($input[$field]))
                    $input_array=&$input[$field];
                else $input_array=array(&$input[$field]);
                foreach ($input_array as &$value) {
                    if (is_callable(array($this,'filter_'.$filter))) {
                        $method='filter_'.$filter;
                        $value=$this->$method($value,$params);
                    }
                    elseif (function_exists($filter))
                        $value=$filter($value);
                    elseif (isset(self::$filter_methods[$filter]))
                        $value=call_user_func(self::$filter_methods[$filter],$value,$params);
                    else throw new \Exception("Filter method '$filter' does not exist.");
                }
            }
        }
        return $input;
    }

    /**
    *   Replace noise words in a string
    *   ref: http://tax.cchgroup.com/help/Avoiding_noise_words_in_your_search.htm
    *   @param   $value   string
    *   @param   $params  array
    *   @return  string
    */
protected function filter_noise_words($value,$params=null) {
        $value=preg_replace('/\s\s+/u',chr(32),$value);
        $value=" $value ";
        $words=explode(',',self::$en_noise_words);
        foreach ($words as $word) {
            $word=trim($word);
            $word=" $word ";
            if (stripos($value,$word)!==false)
                $value=str_ireplace($word,chr(32),$value);
        }
        return trim($value);
    }

    /**
    *   Remove all known punctuation from a string
    *   @param   $value   string
    *   @param   $params  array
    *   @return  string
    */
    protected function filter_rmpunctuation($value,$params=null) {
        return preg_replace("/(?![.=$'â‚¬%-])\p{P}/u",'',$value);
    }

    /**
    *   Sanitize the string by removing any script tags
    *   @param   $value   string
    *   @param   $params  array
    *   @return  string
    */
    protected function filter_sanitize_string($value,$params=null) {
        return filter_var($value,FILTER_SANITIZE_STRING);
    }

    /**
    *   Sanitize the string by urlencoding characters
    *   @param   $value   string
    *   @param   $params  array
    *   @return  string
    */
    protected function filter_urlencode($value,$params=null) {
        return filter_var($value,FILTER_SANITIZE_ENCODED);
    }

    /**
    *   Sanitize the string by converting html characters to their HTML entities
    *   @param   $value   string
    *   @param   $params  array
    *   @return  string
    */
    protected function filter_htmlencode($value,$params=null) {
        return filter_var($value,FILTER_SANITIZE_SPECIAL_CHARS);
    }

    /**
    *   Sanitize the string by removing illegal characters from emails
    *   @param   $value   string
    *   @param   $params  array
    *   @return  string
    */
    protected function filter_sanitize_email($value,$params=null) {
        return filter_var($value,FILTER_SANITIZE_EMAIL);
    }

    /**
    *   Sanitize the string by removing illegal characters from numbers
    *   @param   $value   string
    *   @param   $params  array
    *   @return  string
    */
    protected function filter_sanitize_numbers($value,$params=null) {
        return filter_var($value,FILTER_SANITIZE_NUMBER_INT);
    }

    /**
    *   Sanitize the string by removing illegal characters from float numbers
    *   @param   $value   string
    *   @param   $params  array
    *   @return  string
    */
    protected function filter_sanitize_floats($value,$params=null) {
        return filter_var($value,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
    *   Filter out all HTML tags except the defined basic tags
    *   @param   $value   string
    *   @param   $params  array
    *   @return  string
    */
    protected function filter_basic_tags($value,$params=null) {
        return strip_tags($value,self::$basic_tags);
    }

    /**
    *   Convert the provided numeric value to a whole number
    *   @param   $value   string
    *   @param   $params  array
    *   @return  string
    */
    protected function filter_whole_number($value,$params=null) {
        return intval($value);
    }

    /**
    *   Convert MS Word special characters (“, ”, ‘, ’, –, …) to web safe characters
    *   @param   $value   string
    *   @param   $params  array
    *   @return  string
    */
    protected function filter_ms_word_characters($value,$params=null) {
        $word_open_double='“';
        $word_close_double='”';
        $web_safe_double='"';
        $value=str_replace([$word_open_double,$word_close_double],$web_safe_double,$value);
        $word_open_single='‘';
        $word_close_single='’';
        $web_safe_single="'";
        $value=str_replace([$word_open_single,$word_close_single],$web_safe_single,$value);
        $word_em='–';
        $web_safe_em='-';
        $value=str_replace($word_em,$web_safe_em,$value);
        $word_ellipsis='…';
        $web_safe_em='...';
        $value=str_replace($word_ellipsis,$web_safe_em,$value);
        return $value;
    }

    /**
    *   Verify that a value is contained within the pre-defined value set
    *   @param   $field  string
    *   @param   $input  array
    *   @param   $param  null
    *   @return  mixed
    */
    protected function validate_contains($field,$input,$param=null) {
        if (!isset($input[$field])) return;
        $param=trim(strtolower($param));
        $value=trim(strtolower($input[$field]));
        if (preg_match_all('#\'(.+?)\'#',$param,$matches,PREG_PATTERN_ORDER))
            $param=$matches[1];
        else $param=explode(chr(32),$param);
        if (in_array($value,$param))
            return;
        return [
            'field'=>$field,
            'value'=>$value,
            'rule'=>__FUNCTION__,
            'param'=>$param
        ];
    }

    /**
    *   Verify that a value is contained within the pre-defined value set
    *   @param   $field  string
    *   @param   $input  array
    *   @param   $param  string|null
    *   @return  mixed
    */
    protected function validate_contains_list($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        $param=trim(strtolower($param));
        $value=trim(strtolower($input[$field]));
        $param=explode(';',$param);
        if (in_array($value,$param)) return;
        else return [
            'field'=>$field,
            'value'=>$value,
            'rule'=>__FUNCTION__,
            'param'=>$param
        ];
    }

    /**
    *   Verify that a value is NOT contained within the pre-defined value set
    *   @param   $field  string
    *   @param   $input  array
    *   @param   $param  string|null
    *   @return  mixed
    */
    protected function validate_doesnt_contain_list($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        $param=trim(strtolower($param));
        $value=trim(strtolower($input[$field]));
        $param=explode(';',$param);
        if (!in_array($value,$param))
            return;
        else return [
            'field'=>$field,
            'value'=>$value,
            'rule'=>__FUNCTION__,
            'param'=>$param
        ];
    }

    /**
    *   Check if the specified key is present and not empty
    *   @param   $field  string
    *   @param   $input  array
    *   @param   $param  string|null
    *   @return  mixed
    */
    protected function validate_required($field,$input,$param=null) {
        if (isset($input[$field])
        &&($input[$field]===false
        ||$input[$field]===0
        ||$input[$field]===0.0
        ||$input[$field]==='0'
        ||!empty($input[$field])))
            return;
        return [
            'field'=>$field,
            'value'=>null,
            'rule'=>__FUNCTION__,
            'param'=>$param
        ];
    }

    /**
    *   Determine if the provided email is valid
    *   @param   $field  string
    *   @param   $input  array
    *   @param   $param  string|null
    *   @return  mixed
    */
    protected function validate_valid_email($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        if (!filter_var($input[$field],FILTER_VALIDATE_EMAIL))
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
    }

    /**
    *   Determine if the provided value length is less or equal to a specific value
    *   @param   $field  string
    *   @param   $input  array
    *   @param   $param  string|null
    *   @return  mixed
    */
    protected function validate_max_len($field,$input,$param=null) {
        if (!isset($input[$field])) return;
        if (function_exists('mb_strlen'))
            if (mb_strlen($input[$field])<=(int)$param)
                return;
        else if (strlen($input[$field])<=(int)$param)
            return;
        return [
            'field'=>$field,
            'value'=>$input[$field],
            'rule'=>__FUNCTION__,
            'param'=>$param
        ];
    }

    /**
    *   Determine if the provided value length is more or equal to a specific value
    *   @param   $field  string
    *   @param   $input  array
    *   @param   $param  string|null
    *   @return  mixed
    */
    protected function validate_min_len($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        if (function_exists('mb_strlen'))
            if (mb_strlen($input[$field])>=(int)$param)
                return;
        else if (strlen($input[$field])>=(int)$param)
            return;
        return [
            'field'=>$field,
            'value'=>$input[$field],
            'rule'=>__FUNCTION__,
            'param'=>$param
        ];
    }

    /**
    *   Determine if the provided value length matches a specific value
    *   @param   $field  string
    *   @param   $input  array
    *   @param   $param  string|null
    *   @return  mixed
    */
    protected function validate_exact_len($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        if (function_exists('mb_strlen'))
            if (mb_strlen($input[$field])==(int)$param)
                return;
        else if (strlen($input[$field])==(int)$param)
            return;
        return [
            'field'=>$field,
            'value'=>$input[$field],
            'rule'=>__FUNCTION__,
            'param'=>$param
        ];
    }

    /**
    *   Determine if the provided value contains only alpha characters
    *   @param   $field  string
    *   @param   $input  array
    *   @param   $param  string|null
    *   @return  mixed
    */
    protected function validate_alpha($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        if (!preg_match(
        '/^([a-zÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ])+$/i',
        $input[$field])!==false)
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
    }

    /**
    *   Determine if the provided value contains only alpha-numeric characters
    *   @param   $field  string
    *   @param   $input  array
    *   @param   $param  string|null
    *   @return  mixed
    */
    protected function validate_alpha_numeric($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        if (!preg_match(
        '/^([a-z0-9ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ])+$/i',
        $input[$field])!==false)
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
    }

    /**
    *   Determine if the provided value contains only alpha characters with dashed and underscores
    *   @param   $field  string
    *   @param   $input  array
    *   @param   $param  string|null
    *   @return  mixed
    */
    protected function validate_alpha_dash($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        if (!preg_match(
        '/^([a-z0-9ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ_-])+$/i',
        $input[$field])!==false)
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
    }

    /**
    *   Determine if the provided value contains only alpha numeric characters with spaces
    *   @param   $field  string
    *   @param   $input  array
    *   @param   $param  string|null
    *   @return  mixed
    */
    protected function validate_alpha_numeric_space($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        if (!preg_match(
        "/^([a-z0-9ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ\s])+$/i",
        $input[$field])!==false)
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
    }

    /**
    *   Determine if the provided value contains only alpha numeric characters with spaces
    *   @param   $field  string
    *   @param   $input  array
    *   @param   $param  string|null
    *   @return  mixed
    */
    protected function validate_alpha_space($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        if (!preg_match(
        "/^([0-9a-zÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ\s])+$/i",
        $input[$field])!==false)
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
    }

    /**
    *   Determine if the provided value is a valid number or numeric string
    *   @param   $field  string
    *   @param   $input  array
    *   @param   $param  string|null
    *   @return  mixed
    */
    protected function validate_numeric($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        if (!is_numeric($input[$field]))
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
    }

    /**
    *   Determine if the provided value is a valid integer
    *   @param   $field  string
    *   @param   $input  array
    *   @param   $param  string|null
    *   @return  mixed
    */
    protected function validate_integer($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        if (filter_var($input[$field],FILTER_VALIDATE_INT)===false)
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
    }

    /**
    *   Determine if the provided value is a PHP accepted boolean
    *   @param   $field  string
    *   @param   $input  array
    *   @param   $param  string|null
    *   @return  mixed
    */
    protected function validate_boolean($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field])
        &&$input[$field]!==0)
            return;
        $booleans=['1','true',true,1,'0','false',false,0,'yes','no','on','off'];
        if (in_array($input[$field],$booleans,true))
            return;
        return [
            'field'=>$field,
            'value'=>$input[$field],
            'rule'=>__FUNCTION__,
            'param'=>$param
        ];
    }

    /**
    *   Determine if the provided value is a valid float
    *   @param   $field  string
    *   @param   $input  array
    *   @param   $param  null
    *   @return  mixed
    */
    protected function validate_float($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        if (filter_var($input[$field],FILTER_VALIDATE_FLOAT)===false)
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
    }

    /**
    *   Determine if the provided value is a valid url
    *   @param   $field  string
    *   @param   $input  array
    *   @param   $param  string|null
    *   @return  mixed
    */
    protected function validate_valid_url($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        if (!filter_var($input[$field],FILTER_VALIDATE_URL))
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
    }

    /**
    *   Determine if a URL exists & is accessible
    *   @param   $field  string
    *   @param   $input  array
    *   @param   $param  string|null
    *   @return  mixed
    */
    protected function validate_url_exists($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        $url=parse_url(strtolower($input[$field]));
        if (isset($url['host']))
            $url=$url['host'];
        if (function_exists('checkdnsrr'))
            if (checkdnsrr(idn_to_ascii($url),'A')===false)
                return [
                    'field'=>$field,
                    'value'=>$input[$field],
                    'rule'=>__FUNCTION__,
                    'param'=>$param
                ];
        else if (gethostbyname($url)==$url)
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
    }

    /**
    *   Determine if the provided value is a valid IP address
    *   @param   $field  string
    *   @param   $input  array
    *   @return  mixed
    */
    protected function validate_valid_ip($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        if (!filter_var($input[$field],FILTER_VALIDATE_IP)!==false)
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
    }

    /**
    *   Determine if the provided value is a valid IPv4 address
    *   @param   $field  string
    *   @param   $input  array
    *   @return  mixed
    */
    protected function validate_valid_ipv4($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        if (!filter_var($input[$field],FILTER_VALIDATE_IP,FILTER_FLAG_IPV4))
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
    }

    /**
    *   Determine if the provided value is a valid IPv6 address
    *   @param   $field  string
    *   @param   $input  array
    *   @return  mixed
    */
    protected function validate_valid_ipv6($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        if (!filter_var($input[$field],FILTER_VALIDATE_IP,FILTER_FLAG_IPV6))
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
    }

    /**
    *   Determine if the input is a valid credit card number.
    *   ref: http://stackoverflow.com/questions/174730/what-is-the-best-way-to-validate-a-credit-card-in-php
    *   @param   $field  string
    *   @param   $input  array
    *   @return  mixed
    */
    protected function validate_valid_cc($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        $number=preg_replace('/\D/','',$input[$field]);
        if (function_exists('mb_strlen'))
            $number_length=mb_strlen($number);
        else $number_length=strlen($number);
        $parity=$number_length%2;
        $total=0;
        for ($i=0;$i<$number_length;++$i) {
            $digit=$number[$i];
            if ($i%2==$parity) {
                $digit*=2;
                if ($digit>9) $digit-=9;
            }
            $total+=$digit;
        }
        if ($total%10==0) return;
        return [
            'field'=>$field,
            'value'=>$input[$field],
            'rule'=>__FUNCTION__,
            'param'=>$param
        ];
    }

    /**
    *   Determine if the input is a valid human name
    *   @param   $field  string
    *   @param   $input  array
    *   @return  mixed
    */
    protected function validate_valid_name($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        if (!preg_match(
        "/^([a-zÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïñðòóôõöùúûüýÿ '-])+$/i",
        $input[$field])!==false)
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
    }

    /**
    *   Determine if the provided input is likely to be a street address using weak detection
    *   @param   $field  string
    *   @param   $input  array
    *   @return  mixed
    */
    protected function validate_street_address($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        $hasLetter=preg_match('/[a-zA-Z]/',$input[$field]);
        $hasDigit=preg_match('/\d/',$input[$field]);
        $hasSpace=preg_match('/\s/',$input[$field]);
        $passes=$hasLetter&&$hasDigit&&$hasSpace;
        if (!$passes)
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
    }

    /**
    *   Determine if the provided value is a valid IBAN
    *   @param   $field  string
    *   @param   $input  array
    *   @return  mixed
    */
    protected function validate_iban($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        static $character=[
            'A'=>10,'C'=>12,'D'=>13,'E'=>14,'F'=>15,
            'G'=>16,'H'=>17,'I'=>18,'J'=>19,'K'=>20,
            'L'=>21,'M'=>22,'N'=>23,'O'=>24,'P'=>25,
            'Q'=>26,'R'=>27,'S'=>28,'T'=>29,'U'=>30,
            'V'=>31,'W'=>32,'X'=>33,'Y'=>34,'Z'=>35,'B'=>11
        ];
        if (!preg_match(
        "/\A[A-Z]{2}\d{2} ?[A-Z\d]{4}( ?\d{4}){1,} ?\d{1,4}\z/",
        $input[$field]))
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
        $iban=str_replace(' ','',$input[$field]);
        $iban=substr($iban,4).substr($iban,0,4);
        $iban=strtr($iban,$character);
        if (bcmod($iban,97)!=1)
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
    }

    /**
    *   Determine if the provided input is a valid date (ISO 8601) or specify a custom format
    *   @param   $field  string
    *   @param   $input  string
    *   @param   $param  string
    *   @return  mixed
    */
    protected function validate_date($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        if (!$param) {
            $cdate1=date('Y-m-d',strtotime($input[$field]));
            $cdate2=date('Y-m-d H:i:s',strtotime($input[$field]));
            if ($cdate1!=$input[$field]
            &&$cdate2!=$input[$field])
                return [
                    'field'=>$field,
                    'value'=>$input[$field],
                    'rule'=>__FUNCTION__,
                    'param'=>$param
                ];
        }
        else {
            $date=\DateTime::createFromFormat($param,$input[$field]);
            if ($date===false||$input[$field]!=date($param,$date->getTimestamp()))
                return [
                    'field'=>$field,
                    'value'=>$input[$field],
                    'rule'=>__FUNCTION__,
                    'param'=>$param
                ];
        }
    }

    /**
    *   Determine if the provided input meets age requirement (ISO 8601)
    *   @param   $field  string
    *   @param   $input  string
    *   @param   $param  string|int
    *   @return  mixed
    */
    protected function validate_min_age($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        $cdate1=new DateTime(date('Y-m-d',strtotime($input[$field])));
        $today=new DateTime(date('d-m-Y'));
        $interval=$cdate1->diff($today);
        $age=$interval->y;
        if ($age<=$param)
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
    }

    /**
    *   Determine if the provided numeric value is lower or equal to a specific value
    *   @param   $field  string
    *   @param   $input  array
    *   @param   $param  string|null
    *   @return  mixed
    */
    protected function validate_max_numeric($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        if (is_numeric($input[$field])
        &&is_numeric($param)
        &&($input[$field]<=$param))
            return;
        return [
            'field'=>$field,
            'value'=>$input[$field],
            'rule'=>__FUNCTION__,
            'param'=>$param
        ];
    }

    /**
    *   Determine if the provided numeric value is higher or equal to a specific value
    *   @param   $field  string
    *   @param   $input  array
    *   @param   $param  string|null
    *   @return  mixed
    */
    protected function validate_min_numeric($field,$input,$param=null) {
        if (!isset($input[$field])
        ||$input[$field]==='')
            return;
        if (is_numeric($input[$field])
        &&is_numeric($param)
        &&($input[$field]>=$param))
            return;
        return [
            'field'=>$field,
            'value'=>$input[$field],
            'rule'=>__FUNCTION__,
            'param'=>$param
        ];
    }

    /**
    *   Determine if the provided value starts with param
    *   @param   $field  string
    *   @param   $input  array
    *   @return  mixed
    */
    protected function validate_starts($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        if (strpos($input[$field],$param)!==0)
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
    }

    /**
    *   Checks if a file was uploaded
    *   @param   $field  string
    *   @param   $input  array
    *   @return  mixed
    */
    protected function validate_required_file($field,$input,$param=null) {
        if (!isset($input[$field]))
            return;
        if (is_array($input[$field])
        &&$input[$field]['error']!==4)
            return;
        return [
            'field'=>$field,
            'value'=>$input[$field],
            'rule'=>__FUNCTION__,
            'param'=>$param
        ];
    }

    /**
    *   Check the uploaded file for extension (only)
    *   @param   $field  string
    *   @param   $input  array
    *   @return  mixed
    */
    protected function validate_extension($field,$input,$param=null) {
        if (!isset($input[$field]))
            return;
        if (is_array($input[$field])
        &&$input[$field]['error']!==4) {
            $param=trim(strtolower($param));
            $allowed_extensions=explode(';',$param);
            $path_info=pathinfo($input[$field]['name']);
            $extension=isset($path_info['extension'])?$path_info['extension']:false;
            if ($extension&&in_array($extension,$allowed_extensions))
                return;
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
        }
    }

    /**
    *   Determine if the provided field value equals current field value
    *   @param   $field  string
    *   @param   $input  string
    *   @param   $param  string
    *   @return  mixed
    */
    protected function validate_equalsfield($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        if ($input[$field]==$input[$param])
            return;
        return [
            'field'=>$field,
            'value'=>$input[$field],
            'rule'=>__FUNCTION__,
            'param'=>$param
        ];
    }

    /**
    *   Determine if the provided field value is a valid GUID (v4)
    *   @param   $field  string
    *   @param   $input  string
    *   @param   $param  string
    *   @return  mixed
    */
    protected function validate_guidv4($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        if (preg_match(
        "/\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/",
        $input[$field]))
            return;
        return [
            'field'=>$field,
            'value'=>$input[$field],
            'rule'=>__FUNCTION__,
            'param'=>$param
        ];
    }

    /**
    *   Trim whitespace only when the value is a scalar
    *   @param   $value  mixed
    *   @return  mixed
    */
    private function trimScalar($value) {
        if (is_scalar($value))
            $value=trim($value);
        return $value;
    }

    /**
    *   Determine if the provided value is a valid phone number
    *   @param   $field  string
    *   @param   $input  array
    *   @return  mixed
    */
    protected function validate_phone_number($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        $regex='/^(\d[\s-]?)?[\(\[\s-]{0,2}?\d{3}[\)\]\s-]{0,2}?\d{3}[\s-]?\d{4}$/i';
        if (!preg_match($regex,$input[$field]))
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
    }

    /**
    *   Custom regex validator
    *   @param   $field  string
    *   @param   $input  array
    *   @return  mixed
    */
    protected function validate_regex($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        $regex=$param;
        if (!preg_match($regex,$input[$field]))
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
    }

    /**
    *   JSON validator
    *   @param   $field  string
    *   @param   $input  array
    *   @return  mixed
    */
    protected function validate_valid_json_string($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        if (!is_string($input[$field])
        ||!is_object(json_decode($input[$field])))
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
    }

    /**
    *   Check if an input is an array and if the size is more or equal to a specific value
    *   @param   $field  string
    *   @param   $input  array
    *   @return  mixed
    */
    protected function validate_valid_array_size_greater($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        if (!is_array($input[$field])
        ||sizeof($input[$field])<(int)$param)
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
    }

    /**
    *   Check if an input is an array and if the size is less or equal to a specific value
    *   @param   $field  string
    *   @param   $input  array
    *   @return  mixed
    */
    protected function validate_valid_array_size_lesser($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        if (!is_array($input[$field])
        ||sizeof($input[$field])>(int)$param)
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
    }

    /**
    *   Check if an input is an array and if the size is equal to a specific value
    *   @param   $field  string
    *   @param   $input  array
    *   @return  mixed
    */
    protected function validate_valid_array_size_equal($field,$input,$param=null) {
        if (!isset($input[$field])
        ||empty($input[$field]))
            return;
        if (!is_array($input[$field])
        ||sizeof($input[$field])==(int)$param)
            return [
                'field'=>$field,
                'value'=>$input[$field],
                'rule'=>__FUNCTION__,
                'param'=>$param
            ];
    }
}
