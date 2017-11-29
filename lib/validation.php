<?php
/**
*   Tiny Validation Library for Alit PHP
*   @package     Alit PHP
*   @subpackage  Alit.Validation
*   @copyright   Copyright (c) 2017-2011 Suyadi. All Rights Reserved.
*   @license     https://opensource.org/licenses/MIT The MIT License (MIT)
*   @author      Suyadi <suyadi.1992@gmail.com>
*/
// Prohibit direct access to file
if (!defined('DS')) die('Direct file access is not allowed.');


class Validation extends \Factory {

    protected static
        // Methods
        $fields=[],
        $filter_methods=[],
        $validation_methods=[],
        $validation_methods_errors=[];

    protected
        // Rules and error message
        $lang,
        $errors=[],
        $filter_rules=[],
        $validation_rules=[],
        $field_char_to_remove=['_','-'];

    static
        // Basic html tag to remove
        $basic_tags='
            <br><p><a><strong><b><i><em><img>'.
            '<blockquote><code><dd><dl><hr><h1><h2><h3>'.
            '<h4><h5><h6><label><ul><li><span><sub><sup>';

    static
        // English noise words to remove
        $en_noise_words="
            about,after,all,also,an,and,another,any,are,as,at,be,because,been,before,".
            "being,between,both,but,by,came,can,come,could,did,do,each,for,from,get,".
            "got,has,had,he,have,her,here,him,himself,his,how,if,in,into,is,it,its,it's,like,".
            "make,many,me,might,more,most,much,must,my,never,now,of,on,only,or,other,".
            "our,out,over,said,same,see,should,since,some,still,such,take,than,that,".
            "the,their,them,then,there,these,they,this,those,through,to,too,under,up,".
            "very,was,way,we,well,were,what,where,which,while,who,with,would,you,your,a,".
            "b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,$,1,2,3,4,5,6,7,8,9,0,_";

    static
        // Validation error messages
        $err_msg=[
            'required'                 => "The %s field is required",
            'valid_email'              => "The %s field must be a valid email address",
            'max_len'                  => "The %s field needs to be %s characters or less",
            'min_len'                  => "The %s field needs to be at least %s characters",
            'exact_len'                => "The %s field needs to be exactly %s characters",
            'alpha'                    => "The %s field may only contain letters",
            'alpha_numeric'            => "The %s field may only contain letters and numbers",
            'alpha_numeric_space'      => "The %s field may only contain letters, numbers and spaces",
            'alpha_dash'               => "The %s field may only contain letters and dashes",
            'alpha_space'              => "The %s field may only contain letters and spaces",
            'numeric'                  => "The %s field must be a number",
            'integer'                  => "The %s field must be a number without a decimal",
            'boolean'                  => "The %s field has to be either true or false",
            'float'                    => "The %s field must be a number with a decimal point",
            'valid_url'                => "The %s field has to be a URL",
            'url_exists'               => "The %s URL does not exist",
            'valid_ip'                 => "The %s field needs to be a valid IP address",
            'valid_ipv4'               => "The %s field needs to contain a valid IPv4 address",
            'valid_ipv6'               => "The %s field needs to contain a valid IPv6 address",
            'guidv4'                   => "The %s field needs to contain a valid GUID",
            'valid_cc'                 => "The %s is not a valid credit card number",
            'valid_name'               => "The %s should be a full name",
            'contains'                 => "The %s can only be one of the following: %s",
            'contains_list'            => "The %s is not a valid option",
            'doesnt_contain_list'      => "The %s field contains a value that is not accepted",
            'street_address'           => "The %s field needs to be a valid street address",
            'date'                     => "The %s must be a valid date",
            'min_numeric'              => "The %s field needs to be numeric, equal or higher than %s",
            'max_numeric'              => "The %s field needs to be numeric, equal or lower than %s",
            'min_age'                  => "The %s field needs to have an age with %s or higher",
            'invalid'                  => "The %s field is invalid",
            'starts'                   => "The %s field needs to start with %s",
            'extension'                => "The %s field can only have one of the following type: %s",
            'required_file'            => "The %s field is required",
            'equalsfield'              => "The %s field does not equal %s field",
            'iban'                     => "The %s field needs to contain a valid IBAN",
            'phone_number'             => "The %s field needs to be a valid Phone Number",
            'valid_json_string'        => "The %s field needs to contain a valid JSON format string",
            'valid_array_size_greater' => "The %s fields needs to be array with size %s or higher",
            'valid_array_size_lesser'  => "The %s fields needs to be array with size %s or lower",
            'valid_array_size_equal'   => "The %s fields needs to be an array with a size equal to %s",
        ];

    const
        // Error messages
        E_InvSetLang="Ivalid language supplied, language must be an array",
        E_Validator_Inexist="Validation method doesn't exists: %s",
        E_Validator_RuleExist="Vlaidation rule already exists: %s",
        E_Filter_Inexist="Filter method doesn't exists: %s",
        E_Filter_RuleExist="Filter rule already exists: %s",
        E_Rule_NoMsg="Rule doesn't have Error message: %s",
        E_Arg_isRegex="You can't use regex as function argument";

    // Class constructor
    function __construct() {
        $this->lang=[];
        foreach (self::$err_msg as $k=>$v)
            $this->lang['validate_'.$k]=$v;

    }

    /**
    *   Set error messages text
    *   @param  $errors  array
    */
    static function setlang(array $errors) {
        $fw=\Alit::instance();
        $eval=self::instance();
        $count=count($errors);
        if (is_array($errors)&&$count>0)
            foreach ($errors as $k=>$v)
                $eval->lang['validate_'.$k]=$v;
        else $fw->abort(500,self::E_InvSetLang);

    }

    /**
    *   Shorthand method for inline validation
    *   @param   $data        array
    *   @param   $validators  array
    *   @return  bool|array
    */
    static function isvalid(array $data,array $validators) {
        $eval=self::instance();
        $eval->validation_rules($validators);
        if ($eval->run($data)===false)
            return $eval->get_readable_errors(false);
        else return true;
    }

    /**
    *   Shorthand method for running only the data filters
    *   @param   $data     array
    *   @param   $filters  array
    *   @return  mixed
    */
    static function filter_input(array $data,array $filters) {
        $eval=self::instance();
        return $eval->filter($data,$filters);
    }

    /**
    *   Magic method to generate the validation error messages
    *   @return  string
    */
    function __toString() {
        return $this->get_readable_errors(true);
    }

    /**
    *   Perform XSS sanitation
    *   @param   $data  array
    *   @return  array
    */
    function xss_clean(array $data) {
        foreach ($data as $k=>$v) {
            $data[$k]=str_replace(['&amp;','&lt;','&gt;'],['&amp;amp;','&amp;lt;','&amp;gt;'],$v);
            $data[$k]=preg_replace('~(&#*\w+)[\x00-\x20]+;~u','$1;',$v);
            $data[$k]=preg_replace('~(&#x*[0-9A-F]+);*~iu','$1;',$v);
            $data[$k]=html_entity_decode($v,ENT_COMPAT,'UTF-8');
            $data[$k]=preg_replace('~(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>~iu','$1>',$k);
            $data[$k]=preg_replace('~([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)'.
                '[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s'.
                '[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t'.
                '[\x00-\x20]*:~iu','$1=$2nojavascript...',$v);
            $data[$k]=preg_replace('~([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v'.
                '[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i'.
                '[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:~iu','$1=$2novbscript...',$v);
            $data[$k]=preg_replace('~([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*'.
                '-moz-binding[\x00-\x20]*:~u','$1=$2nomozbinding...',$v);
            $data[$k]=preg_replace('~(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]'.
                '*.*?expression[\x00-\x20]*\([^>]*+>~i','$1>',$v);
            $data[$k]=preg_replace('~(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]'.
                '*.*?behaviour[\x00-\x20]*\([^>]*+>~i','$1>',$v);
            $data[$k]=preg_replace('~(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]'.
                '*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p'.
                '[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>~iu','$1>',$v);
            $data[$k]=preg_replace('~</*\w+:\w[^>]*+>~i','',$v);
            do {
                $old=$data[$k];
                $data[$k]=preg_replace('~</*(?:applet|b(?:ase|gsound|link)|embed'.
                    '|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)'.
                    '|title|xml)[^>]*+>~i','',$data[$k]);
            } while ($old!==$data[$k]);
        }
        return filter_var($data[$k],FILTER_SANITIZE_STRING);
    }

    /**
    *   Adds a custom validation rule using a callback function.
    *   @param   $rule      string
    *   @param   $callback  callable
    *   @param   $err_msg   string
    *   @return  bool
    */
    static function add_validator($rule,$callback,$err_msg=null) {
        $fw=\Alit::instance();
        $method='validate_'.$rule;
        if (method_exists(__CLASS__,$method)
        ||isset(self::$validation_methods[$rule]))
            $fw->abort(500,sprintf(self::E_Validator_RuleExist,$rule));
        self::$validation_methods[$rule]=$callback;
        if ($err_msg)
            self::$validation_methods_errors[$rule]=$err_msg;
        return true;
    }

    /**
    *   Adds a custom filter using a callback function
    *   @param   $rule      string
    *   @param   $callback  callable
    *   @return  bool
    */
    static function add_filter($rule,$callback) {
        $fw=\Alit::instance();
        $method='filter_'.$rule;
        if (method_exists(__CLASS__,$method)
        ||isset(self::$filter_methods[$rule]))
            $fw->abort(500,sprintf(self::E_Filter_RuleExist,$rule));
        self::$filter_methods[$rule]=$callback;
        return true;
    }

    /**
    *   Helper method to safely extract an element from an array
    *   @param   $key      mixed
    *   @param   $arr      array
    *   @param   $default  mixed
    *   @return  mixed
    */
    static function field($key,array $arr,$default=null) {
        if (!is_array($arr))
            return null;
        return isset($arr[$key])?$arr[$key]:$default;
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
    *   @param   $data   array
    *   @param   $check  bool
    *   @return  array
    */
    function run(array $data,$check=false) {
        $data=$this->filter($data,$this->filter_rules());
        $passed=$this->validate($data,$this->validation_rules());
        if ($check===true)
            $this->check_fields($data);
        if ($passed!==true)
            return false;
        return $data;
    }

    /**
    *   Ensure that the field counts match the validation rule counts
    *   @param  $data  array
    */
    private function check_fields(array $data) {
        $fields=array_keys(array_diff_key($data,$this->validation_rules()));
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
    *   @param   $ipt     array
    *   @param   $fields  array|null
    *   @param   $utf8    bool
    *   @return  array
    */
    function sanitize(array $ipt,array $fields=[],$utf8=true) {
        $magic=(bool)get_magic_quotes_gpc();
        if (empty($fields))
            $fields=array_keys($ipt);
        $out=[];
        foreach ($fields as $field) {
            if (!isset($ipt[$field]))
                continue;
            else {
                $val=$ipt[$field];
                if (is_array($val))
                    $val=$this->sanitize($val);
                if (is_string($val)) {
                    if ($magic===true)
                        $val=stripslashes($val);
                    if (strpos($val,"\r")!==false)
                        $val=trim($val);
                    if (function_exists('iconv')
                    &&function_exists('mb_detect_encoding')
                    &&$utf8) {
                        $enc=mb_detect_encoding($val);
                        if ($enc!='UTF-8'&&$enc!='UTF-16')
                            $val=iconv($enc,'UTF-8',$val);
                    }
                    $val=filter_var($val,FILTER_SANITIZE_STRING);
                }
                $out[$field]=$val;
            }
        }
        return $out;
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
    *   @param   $ipt      mixed
    *   @param   $ruleset  array
    *   @return  mixed
    */
    function validate(array $ipt,array $ruleset) {
        $fw=\Alit::instance();
        $this->errors=[];
        foreach ($ruleset as $field=>$rules) {
            $rules=preg_split('/(?<!\\\)\|(?![^\|]+\))/',$rules);
            $count=count(array_intersect(['required_file','required'],$rules));
            if ($count>0||(isset($ipt[$field]))) {
                if (isset($ipt[$field])) {
                    if (is_array($ipt[$field])
                    &&in_array('required_file',$ruleset))
                        $ipt_arr=$ipt[$field];
                    else $ipt_arr=[$ipt[$field]];
                }
                else $ipt_arr=[''];
                foreach ($ipt_arr as $val) {
                    $ipt[$field]=$val;
                    foreach ($rules as $rule) {
                        $method=null;
                        $arg=null;
                        if (strstr($rule,',')!==false) {
                            $rule=explode(',',$rule);
                            $method='validate_'.$rule[0];
                            $arg=$rule[1];
                            // Prohibit regex pattern as a function's argument. sorry!
                            if (preg_match("/^\/.+\/[a-z]*$/i",$arg))
                                $fw->abort(500,sprintf(self::E_Arg_isRegex,$rule));
                            $rule=$rule[0];
                            if (preg_match('/(?:(?:^|;)_([a-z_]+))/',$arg,$found))
                                if (isset($ipt[$found[1]]))
                                    $arg=str_replace('_'.$found[1],$ipt[$found[1]],$arg);
                        }
                        else $method='validate_'.$rule;
                        if (is_callable([$this,$method])) {
                            $out=$this->$method($field,$ipt,$arg);
                            if (is_array($out))
                                if (array_search($out['field'],array_column($this->errors,'field'))===false)
                                    $this->errors[]=$out;
                        }
                        elseif (isset(self::$validation_methods[$rule])) {
                            $out=call_user_func(self::$validation_methods[$rule],$field,$ipt,$arg);
                            if ($out===false)
                                if (array_search($out['field'],array_column($this->errors,'field'))===false)
                                    $this->errors[]=[
                                        'field'=>$field,
                                        'value'=>$ipt[$field],
                                        'rule'=>$rule,
                                        'param'=>$arg
                                    ];
                        }
                        else $fw->abort(500,sprintf(self::E_Validator_Inexist,$method));
                    }
                }
            }
        }
        $count=count($this->errors);
        return ($count>0)?$this->errors:true;
    }

    /**
    *   Set a readable name for a specified field names
    *   @param  $field  string
    *   @param  $as     string
    */
    static function set_field_name($field,$as) {
        self::$fields[$field]=$as;
    }

    /**
    *   Set readable name for specified fields in an array
    *   @param  $arr  array
    */
    static function set_field_names(array $arr) {
        foreach ($arr as $field=>$as)
            self::set_field_name($field,$as);
    }

    /**
    *   Set a custom error message for a validation rule
    *   @param  $rule  string
    *   @param  $msg   string
    */
    static function set_error_message($rule,$msg) {
        $eval=self::instance();
        self::$validation_methods_errors[$rule]=$msg;
    }

    /**
    *   Set custom error messages for validation rules in an array
    *   @param  $arr  array
    */
    static function set_error_messages(array $arr) {
        foreach ($arr as $rule=>$msg)
            self::set_error_message($rule,$msg);
    }

    /**
    *   Get error languages.
    *   @return  array
    */
    function languages() {
        $res=[];
        foreach ($this->lang as $k=>$v)
            $res[str_replace('validate_','',$k)]=$v;
        return $res;
    }

    /**
    *   Process the validation errors and return human readable error messages.
    *   @param   $to_string     bool
    *   @param   $field_class   string
    *   @param   $err_class     string
    *   @return  array|string
    */
    function get_readable_errors($to_string=false,$field_class='check-field',$err_class='error-msg') {
        $fw=\Alit::instance();
        if (empty($this->errors))
            return ((bool)$to_string)?null:[];
        $response=[];
        $allmsg=$this->lang;
        foreach ($this->errors as $err) {
            $field=ucwords(str_replace($this->field_char_to_remove,chr(32),$err['field']));
            $arg=$err['param'];
            if (array_key_exists($err['field'],self::$fields)) {
                $field=self::$fields[$err['field']];
                if (array_key_exists($arg,self::$fields))
                    $arg=self::$fields[$err['param']];
            }
            if (isset($allmsg[$err['rule']])) {
                if (is_array($arg))
                    $arg=implode(',',$arg);
                $msg=sprintf($allmsg[$err['rule']],'<span class="'.$field_class.'">',$arg);
                $response[]=$msg;
            }
            else $fw->abort(500,sprintf(self::E_Rule_NoMsg,$err['rule']));
        }
        if ((bool)$to_string===false)
            return $response;
        else {
            $buffer='';
            foreach ($response as $res)
                $buffer.='<span class="'.$err_class.'">'.$res.'</span>';
            return $buffer;
        }
    }

    /**
    *   Process the validation errors and return an array of errors with field names as keys
    *   @param   $to_string  bool
    *   @return  array|null
    */
    function get_errors_array($to_string=false) {
        $fw=\Alit::instance();
        if (empty($this->errors))
            return ((bool)$to_string)?null:[];
        $response=[];
        $allmsg=[];
        foreach ($this->lang as $k=>$v)
            $allmsg['validate_'.$k]=$v;
        foreach ($this->errors as $err) {
            $field=ucwords(str_replace(['_','-'],chr(32),$err['field']));
            $arg=$err['param'];
            if (array_key_exists($err['field'],self::$fields)) {
                $field=self::$fields[$err['field']];
                if (array_key_exists($arg,self::$fields))
                    $arg=self::$fields[$err['param']];
            }
            if (isset($allmsg[$err['rule']])) {
                if (!isset($response[$err['field']])) {
                    if (is_array($arg))
                        $arg=implode(',',$arg);
                    $msg=sprintf($allmsg[$err['rule']],$field,$arg);
                    $response[$err['field']]=$msg;
                }
            }
            else $fw->abort(500,sprintf(self::E_Rule_NoMsg,$err['rule']));
        }
        return $response;
    }

    /**
    *   Filter the input data according to the specified filter set
    *   @param   $ipt        mixed
    *   @param   $filterset  array
    *   @return  mixed
    */
    function filter(array $ipt,array $filterset) {
        $fw=\Alit::instance();
        foreach ($filterset as $field=>$filters) {
            if (!array_key_exists($field,$ipt))
                continue;
            foreach (explode('|',$filters) as $filter) {
                $args=null;
                if (strstr($filter,',')!==false) {
                    $filter=explode(',',$filter);
                    $count=count($filter);
                    $args=array_slice($filter,1,$count-1);
                    $filter=$filter[0];
                }
                if (is_array($ipt[$field]))
                    $ipt_arr=&$ipt[$field];
                else $ipt_arr=[&$ipt[$field]];
                foreach ($ipt_arr as &$val) {
                    if (is_callable([$this,'filter_'.$filter])) {
                        $method='filter_'.$filter;
                        $val=$this->$method($val,$args);
                    }
                    elseif (function_exists($filter))
                        $val=$filter($val);
                    elseif (isset(self::$filter_methods[$filter]))
                        $val=call_user_func(self::$filter_methods[$filter],$val,$args);
                    else $fw->abort(500,sprintf(self::E_Filter_Inexist,$filter));
                }
            }
        }
        return $ipt;
    }

    /**
    *   Replace noise words in a string
    *   Reference: http://tax.cchgroup.com/help/Avoiding_noise_words_in_your_search.htm
    *   @param   $val   string
    *   @param   $args  array
    *   @return  string
    */
    protected function filter_noise_words($val,$args=null) {
        $val=' '.preg_replace('/\s\s+/u',chr(32),$val).' ';
        $words=explode(',',self::$en_noise_words);
        foreach ($words as $word) {
            $word=' '.trim($word).' ';
            if (stripos($val,$word)!==false)
                $val=str_ireplace($word,chr(32),$val);
        }
        return trim($val);
    }

    /**
    *   Remove all known punctuation from a string
    *   @param   $val    string
    *   @param   $args   array
    *   @return  string
    */
    protected function filter_rmpunctuation($val,$args=null) {
        return preg_replace("/(?![.=$'â‚¬%-])\p{P}/u",'',$val);
    }

    /**
    *   Sanitize the string by removing any script tags
    *   @param   $val    string
    *   @param   $args   array
    *   @return  string
    */
    protected function filter_sanitize_string($val,$args=null) {
        return filter_var($val,FILTER_SANITIZE_STRING);
    }

    /**
    *   Sanitize the string by urlencoding characters
    *   @param   $val    string
    *   @param   $args   array
    *   @return  string
    */
    protected function filter_urlencode($val,$args=null) {
        return filter_var($val,FILTER_SANITIZE_ENCODED);
    }

    /**
    *   Sanitize the string by converting html characters to their HTML entities
    *   @param   $val    string
    *   @param   $args   array
    *   @return  string
    */
    protected function filter_htmlencode($val,$args=null) {
        return filter_var($val,FILTER_SANITIZE_SPECIAL_CHARS);
    }

    /**
    *   Sanitize the string by removing illegal characters from emails
    *   @param   $val    string
    *   @param   $args   array
    *   @return  string
    */
    protected function filter_sanitize_email($val,$args=null) {
        return filter_var($val,FILTER_SANITIZE_EMAIL);
    }

    /**
    *   Sanitize the string by removing illegal characters from numbers
    *   @param   $val    string
    *   @param   $args   array
    *   @return  string
    */
    protected function filter_sanitize_numbers($val,$args=null) {
        return filter_var($val,FILTER_SANITIZE_NUMBER_INT);
    }

    /**
    *   Sanitize the string by removing illegal characters from float numbers
    *   @param   $val    string
    *   @param   $args   array
    *   @return  string
    */
    protected function filter_sanitize_floats($val,$args=null) {
        return filter_var($val,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
    *   Filter out all HTML tags except the defined basic tags
    *   @param   $val    string
    *   @param   $args   array
    *   @return  string
    */
    protected function filter_basic_tags($val,$args=null) {
        return strip_tags($val,self::$basic_tags);
    }

    /**
    *   Convert the provided numeric value to a whole number
    *   @param   $val    string
    *   @param   $args   array
    *   @return  string
    */
    protected function filter_whole_number($val,$args=null) {
        return intval($val);
    }

    /**
    *   Convert MS Word special characters to web safe characters
    *   Reference: https://stackoverflow.com/questions/7419302/converting-microsoft-word-special-characters-with-php
    *   @param   $val    string
    *   @param   $args   array
    *   @return  string
    */
    protected function filter_ms_word_characters($val,$args=null) {
        $val=str_replace([
                "\xC2\xAB","\xC2\xBB","\xE2\x80\x98","\xE2\x80\x99",
                "\xE2\x80\x9A","\xE2\x80\x9B","\xE2\x80\x9C","\xE2\x80\x9D",
                "\xE2\x80\x9E","\xE2\x80\x9F","\xE2\x80\xB9","\xE2\x80\xBA",
                "\xE2\x80\x93","\xE2\x80\x94","\xE2\x80\xA6"
            ],
            [
                "<<",">>","'","'",
                "'","'",'"','"',
                '"','"',"<",">",
                "-","-","..."
            ],
            $val
        );
        // Remove non-ascii chars
        return preg_replace('/[^\x20-\x7E]*/','',$val);
    }

    /**
    *   Verify that a value is contained within the pre-defined value set
    *   @param   $field  string
    *   @param   $ipt    array
    *   @param   $arg    null
    *   @return  mixed
    */
    protected function validate_contains($field,$ipt,$arg=null) {
        if (!isset($ipt[$field]))
            return;
        $arg=trim(strtolower($arg));
        $val=trim(strtolower($ipt[$field]));
        if (preg_match_all('#\'(.+?)\'#',$arg,$found,PREG_PATTERN_ORDER))
            $arg=$found[1];
        else $arg=explode(chr(32),$arg);
        if (in_array($val,$arg))
            return;
        return [
            'field'=>$field,
            'value'=>$val,
            'rule'=>__FUNCTION__,
            'param'=>$arg
        ];
    }

    /**
    *   Verify that a value is contained within the pre-defined value set
    *   @param   $field  string
    *   @param   $ipt    array
    *   @param   $arg    string|null
    *   @return  mixed
    */
    protected function validate_contains_list($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        $arg=trim(strtolower($arg));
        $val=trim(strtolower($ipt[$field]));
        $arg=explode(';',$arg);
        if (in_array($val,$arg))
            return;
        else return [
            'field'=>$field,
            'value'=>$val,
            'rule'=>__FUNCTION__,
            'param'=>$arg
        ];
    }

    /**
    *   Verify that a value is NOT contained within the pre-defined value set
    *   @param   $field  string
    *   @param   $ipt    array
    *   @param   $arg    string|null
    *   @return  mixed
    */
    protected function validate_doesnt_contain_list($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        $arg=trim(strtolower($arg));
        $val=trim(strtolower($ipt[$field]));
        $arg=explode(';',$arg);
        if (!in_array($val,$arg))
            return;
        else return [
            'field'=>$field,
            'value'=>$val,
            'rule'=>__FUNCTION__,
            'param'=>$arg
        ];
    }

    /**
    *   Check if the specified key is present and not empty
    *   @param   $field  string
    *   @param   $ipt    array
    *   @param   $arg    string|null
    *   @return  mixed
    */
    protected function validate_required($field,$ipt,$arg=null) {
        if (isset($ipt[$field])
        &&($ipt[$field]===false
        ||$ipt[$field]===0
        ||$ipt[$field]===0.0
        ||$ipt[$field]==='0'
        ||!empty($ipt[$field])))
            return;
        return [
            'field'=>$field,
            'value'=>null,
            'rule'=>__FUNCTION__,
            'param'=>$arg
        ];
    }

    /**
    *   Determine if the provided email is valid
    *   @param   $field  string
    *   @param   $ipt    array
    *   @param   $arg    string|null
    *   @return  mixed
    */
    protected function validate_valid_email($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if (!filter_var($ipt[$field],FILTER_VALIDATE_EMAIL))
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
    }

    /**
    *   Determine if the provided value length is less or equal to a specific value
    *   @param   $field  string
    *   @param   $ipt    array
    *   @param   $arg    string|null
    *   @return  mixed
    */
    protected function validate_max_len($field,$ipt,$arg=null) {
        if (!isset($ipt[$field]))
            return;
        if (function_exists('mb_strlen'))
            if (mb_strlen($ipt[$field])<=(int)$arg)
                return;
        else if (strlen($ipt[$field])<=(int)$arg)
            return;
        return [
            'field'=>$field,
            'value'=>$ipt[$field],
            'rule'=>__FUNCTION__,
            'param'=>$arg
        ];
    }

    /**
    *   Determine if the provided value length is more or equal to a specific value
    *   @param   $field  string
    *   @param   $ipt    array
    *   @param   $arg    string|null
    *   @return  mixed
    */
    protected function validate_min_len($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if (function_exists('mb_strlen'))
            if (mb_strlen($ipt[$field])>=(int)$arg)
                return;
        else if (strlen($ipt[$field])>=(int)$arg)
            return;
        return [
            'field'=>$field,
            'value'=>$ipt[$field],
            'rule'=>__FUNCTION__,
            'param'=>$arg
        ];
    }

    /**
    *   Determine if the provided value length matches a specific value
    *   @param   $field  string
    *   @param   $ipt    array
    *   @param   $arg    string|null
    *   @return  mixed
    */
    protected function validate_exact_len($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if (function_exists('mb_strlen'))
            if (mb_strlen($ipt[$field])==(int)$arg)
                return;
        else if (strlen($ipt[$field])==(int)$arg)
            return;
        return [
            'field'=>$field,
            'value'=>$ipt[$field],
            'rule'=>__FUNCTION__,
            'param'=>$arg
        ];
    }

    /**
    *   Determine if the provided value contains only alpha characters
    *   @param   $field  string
    *   @param   $ipt    array
    *   @param   $arg    string|null
    *   @return  mixed
    */
    protected function validate_alpha($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if (!preg_match('/^([a-zÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ])+$/i',
        $ipt[$field])!==false)
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
    }

    /**
    *   Determine if the provided value contains only alpha-numeric characters
    *   @param   $field  string
    *   @param   $ipt    array
    *   @param   $arg    string|null
    *   @return  mixed
    */
    protected function validate_alpha_numeric($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if (!preg_match('/^([a-z0-9ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ])+$/i',
        $ipt[$field])!==false)
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
    }

    /**
    *   Determine if the provided value contains only alpha characters with dashed and underscores
    *   @param   $field  string
    *   @param   $ipt    array
    *   @param   $arg    string|null
    *   @return  mixed
    */
    protected function validate_alpha_dash($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if (!preg_match('/^([a-z0-9ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ_-])+$/i',
        $ipt[$field])!==false)
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
    }

    /**
    *   Determine if the provided value contains only alpha numeric characters with spaces
    *   @param   $field  string
    *   @param   $ipt    array
    *   @param   $arg    string|null
    *   @return  mixed
    */
    protected function validate_alpha_numeric_space($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if (!preg_match("/^([a-z0-9ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ\s])+$/i",
        $ipt[$field])!==false)
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
    }

    /**
    *   Determine if the provided value contains only alpha numeric characters with spaces
    *   @param   $field  string
    *   @param   $ipt    array
    *   @param   $arg    string|null
    *   @return  mixed
    */
    protected function validate_alpha_space($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if (!preg_match("/^([0-9a-zÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ\s])+$/i",
        $ipt[$field])!==false)
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
    }

    /**
    *   Determine if the provided value is a valid number or numeric string
    *   @param   $field  string
    *   @param   $ipt    array
    *   @param   $arg    string|null
    *   @return  mixed
    */
    protected function validate_numeric($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if (!is_numeric($ipt[$field]))
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
    }

    /**
    *   Determine if the provided value is a valid integer
    *   @param   $field  string
    *   @param   $ipt    array
    *   @param   $arg    string|null
    *   @return  mixed
    */
    protected function validate_integer($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if (filter_var($ipt[$field],FILTER_VALIDATE_INT)===false)
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
    }

    /**
    *   Determine if the provided value is a PHP accepted boolean
    *   @param   $field  string
    *   @param   $ipt    array
    *   @param   $arg    string|null
    *   @return  mixed
    */
    protected function validate_boolean($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field])
        &&$ipt[$field]!==0)
            return;
        $bool=['1','true',true,1,'0','false',false,0,'yes','no','on','off'];
        if (in_array($ipt[$field],$bool,true))
            return;
        return [
            'field'=>$field,
            'value'=>$ipt[$field],
            'rule'=>__FUNCTION__,
            'param'=>$arg
        ];
    }

    /**
    *   Determine if the provided value is a valid float
    *   @param   $field  string
    *   @param   $ipt    array
    *   @param   $arg    null
    *   @return  mixed
    */
    protected function validate_float($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if (filter_var($ipt[$field],FILTER_VALIDATE_FLOAT)===false)
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
    }

    /**
    *   Determine if the provided value is a valid url
    *   @param   $field  string
    *   @param   $ipt    array
    *   @param   $arg    string|null
    *   @return  mixed
    */
    protected function validate_valid_url($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if (!filter_var($ipt[$field],FILTER_VALIDATE_URL))
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
    }

    /**
    *   Determine if a URL exists & is accessible
    *   @param   $field  string
    *   @param   $ipt    array
    *   @param   $arg    string|null
    *   @return  mixed
    */
    protected function validate_url_exists($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        $url=parse_url(strtolower($ipt[$field]));
        if (isset($url['host']))
            $url=$url['host'];
        if (function_exists('checkdnsrr'))
            if (checkdnsrr(idn_to_ascii($url),'A')===false)
                return [
                    'field'=>$field,
                    'value'=>$ipt[$field],
                    'rule'=>__FUNCTION__,
                    'param'=>$arg
                ];
        else if (gethostbyname($url)==$url)
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
    }

    /**
    *   Determine if the provided value is a valid IP address
    *   @param   $field  string
    *   @param   $ipt    array
    *   @return  mixed
    */
    protected function validate_valid_ip($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if (!filter_var($ipt[$field],FILTER_VALIDATE_IP)!==false)
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
    }

    /**
    *   Determine if the provided value is a valid IPv4 address
    *   @param   $field  string
    *   @param   $ipt    array
    *   @return  mixed
    */
    protected function validate_valid_ipv4($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if (!filter_var($ipt[$field],FILTER_VALIDATE_IP,FILTER_FLAG_IPV4))
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
    }

    /**
    *   Determine if the provided value is a valid IPv6 address
    *   @param   $field  string
    *   @param   $ipt    array
    *   @return  mixed
    */
    protected function validate_valid_ipv6($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if (!filter_var($ipt[$field],FILTER_VALIDATE_IP,FILTER_FLAG_IPV6))
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
    }

    /**
    *   Determine if the input is a valid credit card number.
    *   Reference: http://stackoverflow.com/questions/174730/what-is-the-best-way-to-validate-a-credit-card-in-php
    *   @param   $field  string
    *   @param   $ipt    array
    *   @return  mixed
    */
    protected function validate_valid_cc($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        $num=preg_replace('/\D/','',$ipt[$field]);
        $len=function_exists('mb_strlen')?mb_strlen($num):strlen($num);
        $parity=$len%2;
        $total=0;
        for ($i=0;$i<$len;++$i) {
            $digit=$num[$i];
            if ($i%2==$parity) {
                $digit*=2;
                if ($digit>9)
                    $digit-=9;
            }
            $total+=$digit;
        }
        if ($total%10==0)
            return;
        return [
            'field'=>$field,
            'value'=>$ipt[$field],
            'rule'=>__FUNCTION__,
            'param'=>$arg
        ];
    }

    /**
    *   Determine if the input is a valid human name
    *   @param   $field  string
    *   @param   $ipt    array
    *   @return  mixed
    */
    protected function validate_valid_name($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if (!preg_match("/^([a-zÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïñðòóôõöùúûüýÿ '-])+$/i",
        $ipt[$field])!==false)
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
    }

    /**
    *   Determine if the provided input is likely to be a street address using weak detection
    *   @param   $field  string
    *   @param   $ipt    array
    *   @return  mixed
    */
    protected function validate_street_address($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        $letter=preg_match('/[a-zA-Z]/',$ipt[$field]);
        $digit=preg_match('/\d/',$ipt[$field]);
        $space=preg_match('/\s/',$ipt[$field]);
        if (!($letter&&$digit&&$space))
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
    }

    /**
    *   Determine if the provided value is a valid IBAN
    *   @param   $field  string
    *   @param   $ipt    array
    *   @return  mixed
    */
    protected function validate_iban($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        static $chr=[
            'A'=>10,'C'=>12,'D'=>13,'E'=>14,'F'=>15,
            'G'=>16,'H'=>17,'I'=>18,'J'=>19,'K'=>20,
            'L'=>21,'M'=>22,'N'=>23,'O'=>24,'P'=>25,
            'Q'=>26,'R'=>27,'S'=>28,'T'=>29,'U'=>30,
            'V'=>31,'W'=>32,'X'=>33,'Y'=>34,'Z'=>35,'B'=>11
        ];
        if (!preg_match("/\A[A-Z]{2}\d{2} ?[A-Z\d]{4}( ?\d{4}){1,} ?\d{1,4}\z/",$ipt[$field]))
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
        $iban=str_replace(' ','',$ipt[$field]);
        $iban=strtr(substr($iban,4).substr($iban,0,4),$chr);
        if (bcmod($iban,97)!=1)
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
    }

    /**
    *   Determine if the provided input is a valid date (ISO 8601) or specify a custom format
    *   @param   $field  string
    *   @param   $ipt    string
    *   @param   $arg    string
    *   @return  mixed
    */
    protected function validate_date($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if (!$arg) {
            $date1=date('Y-m-d',strtotime($ipt[$field]));
            $date2=date('Y-m-d H:i:s',strtotime($ipt[$field]));
            if ($date1!=$ipt[$field]
            &&$date2!=$ipt[$field])
                return [
                    'field'=>$field,
                    'value'=>$ipt[$field],
                    'rule'=>__FUNCTION__,
                    'param'=>$arg
                ];
        }
        else {
            $date=\DateTime::createFromFormat($arg,$ipt[$field]);
            if ($date===false||$ipt[$field]!=date($arg,$date->getTimestamp()))
                return [
                    'field'=>$field,
                    'value'=>$ipt[$field],
                    'rule'=>__FUNCTION__,
                    'param'=>$arg
                ];
        }
    }

    /**
    *   Determine if the provided input meets age requirement (ISO 8601)
    *   @param   $field  string
    *   @param   $ipt    string
    *   @param   $arg    string|int
    *   @return  mixed
    */
    protected function validate_min_age($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        $date1=new \DateTime(date('Y-m-d',strtotime($ipt[$field])));
        $today=new \DateTime(date('d-m-Y'));
        $iv=$date1->diff($today);
        $age=$iv->y;
        if ($age<=$arg)
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
    }

    /**
    *   Determine if the provided numeric value is lower or equal to a specific value
    *   @param   $field  string
    *   @param   $ipt    array
    *   @param   $arg    string|null
    *   @return  mixed
    */
    protected function validate_max_numeric($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if (is_numeric($ipt[$field])
        &&is_numeric($arg)
        &&($ipt[$field]<=$arg))
            return;
        return [
            'field'=>$field,
            'value'=>$ipt[$field],
            'rule'=>__FUNCTION__,
            'param'=>$arg
        ];
    }

    /**
    *   Determine if the provided numeric value is higher or equal to a specific value
    *   @param   $field  string
    *   @param   $ipt    array
    *   @param   $arg    string|null
    *   @return  mixed
    */
    protected function validate_min_numeric($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||$ipt[$field]==='')
            return;
        if (is_numeric($ipt[$field])
        &&is_numeric($arg)
        &&($ipt[$field]>=$arg))
            return;
        return [
            'field'=>$field,
            'value'=>$ipt[$field],
            'rule'=>__FUNCTION__,
            'param'=>$arg
        ];
    }

    /**
    *   Determine if the provided value starts with param
    *   @param   $field  string
    *   @param   $ipt    array
    *   @return  mixed
    */
    protected function validate_starts($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if (strpos($ipt[$field],$arg)!==0)
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
    }

    /**
    *   Checks if a file was uploaded
    *   @param   $field  string
    *   @param   $ipt    array
    *   @return  mixed
    */
    protected function validate_required_file($field,$ipt,$arg=null) {
        if (!isset($ipt[$field]))
            return;
        if (is_array($ipt[$field])
        &&$ipt[$field]['error']!==4)
            return;
        return [
            'field'=>$field,
            'value'=>$ipt[$field],
            'rule'=>__FUNCTION__,
            'param'=>$arg
        ];
    }

    /**
    *   Check the uploaded file for extension (only)
    *   @param   $field  string
    *   @param   $ipt    array
    *   @return  mixed
    */
    protected function validate_extension($field,$ipt,$arg=null) {
        if (!isset($ipt[$field]))
            return;
        if (is_array($ipt[$field])
        &&$ipt[$field]['error']!==4) {
            $arg=trim(strtolower($arg));
            $allow=explode(';',$arg);
            $info=pathinfo($ipt[$field]['name']);
            $ext=isset($info['extension'])?$info['extension']:false;
            if ($ext&&in_array($ext,$allow))
                return;
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
        }
    }

    /**
    *   Determine if the provided field value equals current field value
    *   @param   $field  string
    *   @param   $ipt    string
    *   @param   $arg    string
    *   @return  mixed
    */
    protected function validate_equalsfield($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if ($ipt[$field]==$ipt[$arg])
            return;
        return [
            'field'=>$field,
            'value'=>$ipt[$field],
            'rule'=>__FUNCTION__,
            'param'=>$arg
        ];
    }

    /**
    *   Determine if the provided field value is a valid GUID (v4)
    *   @param   $field  string
    *   @param   $ipt    string
    *   @param   $arg    string
    *   @return  mixed
    */
    protected function validate_guidv4($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if (preg_match("/\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/",$ipt[$field]))
            return;
        return [
            'field'=>$field,
            'value'=>$ipt[$field],
            'rule'=>__FUNCTION__,
            'param'=>$arg
        ];
    }

    /**
    *   Trim whitespace only when the value is a scalar
    *   @param   $val   mixed
    *   @return  mixed
    */
    private function trim_scalar($val) {
        return is_scalar($val)?trim($val):$val;
    }

    /**
    *   Determine if the provided value is a valid phone number
    *   @param   $field  string
    *   @param   $ipt    array
    *   @return  mixed
    */
    protected function validate_phone_number($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if (!preg_match('/^(\d[\s-]?)?[\(\[\s-]{0,2}?\d{3}[\)\]\s-]{0,2}?\d{3}[\s-]?\d{4}$/i',$ipt[$field]))
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
    }

    /**
    *   JSON validator
    *   @param   $field  string
    *   @param   $ipt    array
    *   @return  mixed
    */
    protected function validate_valid_json_string($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if (!is_string($ipt[$field])
        ||!is_object(json_decode($ipt[$field])))
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
    }

    /**
    *   Check if an input is an array and if the size is more or equal to a specific value
    *   @param   $field  string
    *   @param   $ipt    array
    *   @return  mixed
    */
    protected function validate_valid_array_size_greater($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if (!is_array($ipt[$field])
        ||sizeof($ipt[$field])<(int)$arg)
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
    }

    /**
    *   Check if an input is an array and if the size is less or equal to a specific value
    *   @param   $field  string
    *   @param   $ipt    array
    *   @return  mixed
    */
    protected function validate_valid_array_size_lesser($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if (!is_array($ipt[$field])
        ||sizeof($ipt[$field])>(int)$arg)
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
    }

    /**
    *   Check if an input is an array and if the size is equal to a specific value
    *   @param   $field  string
    *   @param   $ipt    array
    *   @return  mixed
    */
    protected function validate_valid_array_size_equal($field,$ipt,$arg=null) {
        if (!isset($ipt[$field])
        ||empty($ipt[$field]))
            return;
        if (!is_array($ipt[$field])
        ||sizeof($ipt[$field])==(int)$arg)
            return [
                'field'=>$field,
                'value'=>$ipt[$field],
                'rule'=>__FUNCTION__,
                'param'=>$arg
            ];
    }
}
