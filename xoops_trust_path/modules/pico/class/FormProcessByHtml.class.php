<?php

class FormProcessByHtml
{
    var $fields           = [];
    var $form_html        = '';
    var $column_separator = ',';
    var $types            = ['int', 'double', 'singlebytes', 'email', 'telephone'];
    var $validator_dir;

    public function FormProcessByHtml()
    {
        return $this->__construct();
    }

    public function __construct()
    {
        // register validators
        $this->validator_dir = dirname(__FILE__) . '/validators';
        if ($handler = @opendir($this->validator_dir)) {
            while (($file = readdir($handler)) !== false) {
                if (substr($file, 0, 1) == '.') {
                    continue;
                }
                $this->types[] = substr($file, 0, -4);
            }
        }
    }

    public function setFieldsByForm($form_html, $ignore_names = [])
    {
        // initialize
        $this->fields    = [];
        $this->form_html = $form_html;

        // get name="..." from the form
        preg_match_all('#<[^>]+name=([\'"]?)([^\'" ]+)\\1[^>]*>#iU', $this->form_html, $matches, PREG_SET_ORDER);
        $tags = [];
        foreach ($matches as $match) {
            $tags[] = [$match[0], $match[2]];
        }

        // parse HTML and file label
        foreach ($tags as $tag_and_name) {
            list($tag, $field_name_raw) = $tag_and_name;

            // judge whether the field is array or not (TODO)
            $count = 1; // number of controllers with the "name"
            if (($pos = strpos($field_name_raw, '[')) > 0) {
                $field_name = substr($field_name_raw, 0, $pos);
                $array_type = 'linear';
            } else {
                $field_name = $field_name_raw;
                $array_type = '';
            }

            // ignore the form with specified name like cancel button
            if (in_array($field_name, $ignore_names)) {
                continue;
            }

            // options for radio/checkbox or multiple text with the same "name"
            $options = [];
            if (isset($this->fields[$field_name])) {
                $this->fields[$field_name]['count']++;
                $this->fields[$field_name]['tags'][]    = $tag;
                $this->fields[$field_name]['options'][] = $this->fildValueFromTag($tag);
                continue;
            } else {
                $options[] = $this->fildValueFromTag($tag);
            }

            // tag kind
            if (strncasecmp($tag, '<textarea', 9) === 0) {
                $tag_kind = 'textarea';
            } elseif (strncasecmp($tag, '<select', 7) === 0) {
                $tag_kind = 'select';
                if (stristr($tag, 'multiple')) {
                    $count = 0x10000; // large enough
                }
            } elseif (stristr($tag, 'type="checkbox"')) {
                $tag_kind = 'checkbox';
            } elseif (stristr($tag, 'type="radio"')) {
                $tag_kind = 'radio';
            } elseif (stristr($tag, 'type="hidden"')) {
                $tag_kind = 'hidden';
            } elseif (stristr($tag, 'type="text"')) {
                $tag_kind = 'text';
            } elseif (stristr($tag, 'type="submit"')) {
                $tag_kind = 'submit';
            } else {
                continue;
            }

            // get id of the tag
            $id = '';
            if (preg_match('/id\s*\=\s*"([^"]+)"/', $tag, $regs)) {
                $id = trim($regs[1]);
            }

            // get title of the tag
            $title = '';
            if (preg_match('/title\s*\=\s*"([^"]+)"/', $tag, $regs)) {
                $title = trim($regs[1]);
            }

            // get classes of the tag
            $classes = [];
            if (preg_match('/class\s*\=\s*"([^"]+)"/', $tag, $regs)) {
                $classes = array_map('trim', explode(' ', trim($regs[1])));
            }

            // required
            $required = in_array('required', $classes) ? true : false;

            // type judgement
            $type = 'string';
            foreach ($this->types as $eachtype) {
                if (in_array($eachtype, $classes)) {
                    $type = $eachtype;
                    break;
                }
            }

            // get label as title of the field
            $label = empty($title) ? $field_name : $title;
            if (!in_array($tag_kind, ['radio', 'checkbox'])) {
                // search <label> for other than radio/checkbox
                if (preg_match('#for\s*\=\s*([\'"]?)' . preg_quote($id) . '\\1\>(.*)\<\/label\>#iU', $this->form_html, $regs)) {
                    $label = strip_tags(@$regs[2]);
                }
            } else {
                // search the nearest <legend> for radio/checkbox
                foreach (preg_split('#</fieldset>#i', $form_html) as $fieldsetblock) {
                    if (strstr($fieldsetblock, $tag) && preg_match('#<legend[^>]*>([^<]+)</legend>#', $fieldsetblock, $sub_regs)) {
                        $label = strip_tags(@$sub_regs[1]);
                        break;
                    }
                }
            }

            $this->fields[$field_name] = [
                'field_name_raw' => $field_name_raw,
                'tags'           => [$tag],
                'tag_kind'       => $tag_kind,
                'id'             => $id,
                'classes'        => $classes,
                'label'          => $label,
                'required'       => $required,
                'array_type'     => $array_type,
                'type'           => $type,
                'options'        => $options,
                'count'          => $count,
                'errors'         => [],
            ];
        }
    }

    public function importSession($session_data, $check_fields = true)
    {
        if ($check_fields) {
            foreach (array_keys($this->fields) as $field_name) {
                if (isset($session_data[$field_name])) {
                    $this->fields[$field_name] = $session_data[$field_name];
                }
            }
        } else {
            $this->fields = $session_data;
        }
    }

    public function fetchPost($input_encoding = null)
    {
        (method_exists('MyTextSanitizer', 'sGetInstance') and $myts = &MyTextSanitizer::sGetInstance()) || $myts = &MyTextSanitizer::getInstance();

        $_post = $this->_getPostAsArray($input_encoding);

        foreach ($this->fields as $field_name => $attribs) {
            $value = @$_post[$field_name];

            // array checks (TODO)
            $value4reqcheck = $value;
            if (is_array($value)) {
                if ($attribs['count'] <= 1) {
                    $value          = strval(array_pop($value));
                    $value4reqcheck = $value;
                } else {
                    $value          = array_slice($value, 0, $attribs['count']);
                    $value4reqcheck = implode('', $value);
                }
            }

            // missing required
            if ($attribs['required'] == true && ($value4reqcheck === '' || $value4reqcheck === null)) {
                $this->fields[$field_name]['errors'][] = in_array($attribs['tag_kind'], ['text', 'textarea']) ? 'missing required' : 'missing selected';
            }

            $value                              = $this->_validateValueRecursive($value, $field_name, $attribs);
            $this->fields[$field_name]['value'] = $value;
        }

        return $this->fields;
    }

    public function _validateValueRecursive($value, $fn = null, $at = null)
    {
        static $field_name, $attribs;

        if (!empty($fn)) {
            $field_name = $fn;
        }
        if (!empty($at)) {
            $attribs = $at;
        }

        if (is_array($value)) {
            return array_map([$this, '_validateValueRecursive'], $value);
        } else {
            // tag_kind validation (range check)
            // select
            if ($attribs['tag_kind'] == 'select' && !$this->validateSelectOption($attribs['tags'][0], $value)) {
                $this->fields[$field_name]['errors'][] = 'invalid option';
            }
            // radio/checkbox
            if (in_array($attribs['tag_kind'], ['radio', 'checkbox']) && !empty($value)) {
                if (!in_array($value, $attribs['options'])) {
                    $this->fields[$field_name]['errors'][] = 'invalid option';
                }
            }
            // hidden
            if ($attribs['tag_kind'] == 'hidden') {
                $value = @$attribs['options'][0];
            }

            // type checks & conversions
            switch ($attribs['type']) {
                case 'int':
                    $value = $this->convertZenToHan(trim($value));
                    if (!empty($value)) {
                        if (is_numeric($value)) {
                            $value = intval($value);
                        } else {
                            $this->fields[$field_name]['errors'][] = 'invalid number';
                        }
                    }
                    break;

                case 'double':
                    $value = $this->convertZenToHan(trim($value));
                    if (!empty($value)) {
                        if (is_numeric($value)) {
                            $value = doubleval($value);
                        } else {
                            $this->fields[$field_name]['errors'][] = 'invalid number';
                        }
                    }
                    break;

                case 'telephone':
                    $value = $this->convertZenToHan(trim($value));
                    if (!empty($value) && preg_match('/[^()0-9+.-]/', $value)) {
                        $this->fields[$field_name]['errors'][] = 'invalid general';
                    }
                    break;

                case 'email':
                    $value = $this->convertZenToHan($value);
                    if (!empty($value) && !$this->checkEmailAddress($value)) {
                        $this->fields[$field_name]['errors'][] = 'invalid email';
                    }
                    break;

                case 'singlebytes':
                    $value = $this->convertZenToHan($value);
                    break;

                default:
                    if (in_array($attribs['type'], $this->types)) {
                        // custom validator
                        require_once $this->validator_dir . '/' . $attribs['type'] . '.php';
                        $func_name = 'formprocess_validator_' . $attribs['type'];
                        $value     = $func_name($value, $field_name, $this);
                    }
                    break;
            }

            return $value;
        }
    }

    public function stripMQGPC($data)
    {
        //trigger_error("assume magic_quotes_gpc is off", E_USER_NOTICE);
        return $data;
    }

    public function convertEncodingToIE($string, $input_encoding)
    {
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($string, _CHARSET, $input_encoding);
        } elseif (function_exists('iconv')) {
            return iconv($string, $input_encoding, _CHARSET);
        }
        return $string;
    }

    // fetch post data from RAW DATA
    public function _getPostAsArray($input_encoding = null)
    {
        $ret = [];

        $query    = file_get_contents('php://input');
        $key_vals = explode('&', $query);
        foreach ($key_vals as $key_val) {
            @list($key, $val) = array_map('urldecode', explode('=', $key_val));
            if ($input_encoding) {
                $key = $this->convertEncodingToIE($key, $input_encoding);
                $val = $this->convertEncodingToIE($val, $input_encoding);
            }
            @list($key_pref,) = explode('[', $key);
            if ($key_pref != $key) {
                // don't parse explicit array with []
                $ret[$key_pref] = $this->stripMQGPC(@$_POST[$key_pref]);
            } elseif (isset($ret[$key])) {
                // implicit array without []
                if (is_array($ret[$key])) {
                    // add a member of the array
                    $ret[$key][] = $val;
                } else {
                    // convert string into array
                    $ret[$key] = [$ret[$key], $val];
                }
            } else {
                // string
                $ret[$key] = $val;
            }
        }

        return $ret;
    }

    public function getErrors()
    {
        $ret = [];
        foreach ($this->fields as $field_name => $attribs) {
            if (!empty($attribs['errors']) && is_array($attribs['errors'])) {
                foreach ($attribs['errors'] as $error_msg) {
                    $ret[] = [
                        'name'       => $field_name,
                        'label4disp' => htmlspecialchars($attribs['label'], ENT_QUOTES),
                        'message'    => $error_msg,
                    ];
                }
            }
        }

        return $ret;
    }

    public function replaceValues($form_html = null)
    {
        if (empty($form_html)) {
            $form_html = $this->form_html;
        }

        foreach ($this->fields as $field_name => $attribs) {
            switch ($attribs['tag_kind']) {
                case 'textarea':
                    $form_html = $this->replaceContentTextarea($form_html, $attribs);
                    break;
                case 'text':
                    $form_html = $this->replaceValueTextbox($form_html, $attribs);
                    break;
                case 'select':
                    $form_html = $this->replaceSelectedOptions($form_html, $attribs);
                    break;
                case 'radio':
                    $form_html = $this->replaceCheckedRadios($form_html, $attribs, $field_name);
                    break;
                case 'checkbox':
                    $form_html = $this->replaceCheckedCheckboxes($form_html, $attribs, $field_name);
                    break;
                default:
                    break;
            }
        }
        return $form_html;
    }

    public function replaceValueTextbox($form_html, $attribs)
    {
        $values = $attribs['value'];
        if (!is_array($values)) {
            $values = [$values];
        }

        foreach (array_keys($values) as $i) {
            $value      = $values[$i];
            $tag        = @$attribs['tags'][$i];
            $old_tag    = $tag;
            $value4html = htmlspecialchars($value, ENT_QUOTES);

            if (stristr($tag, 'value=')) {
                $new_tag = preg_replace('/value\=\"(.*)\"/', 'value="' . $value4html . '"', $old_tag);
            } else {
                $new_tag = str_replace('/>', 'value="' . $value4html . '" />', $old_tag);
            }
            $form_html = str_replace($old_tag, $new_tag, $form_html);
        }

        return $form_html;
    }

    public function replaceContentTextarea($form_html, $attribs)
    {
        $value4html = htmlspecialchars($attribs['value'], ENT_QUOTES);

        list($before, $content_html_tmp) = explode($attribs['tags'][0], $form_html, 2);
        list($content_html, $after) = explode('</textarea>', $content_html_tmp, 2);
        return $before . $attribs['tags'][0] . $value4html . '</textarea>' . $after;
    }

    public function replaceSelectedOptions($form_html, $attribs)
    {
        $values = $attribs['value'];
        if (!is_array($values)) {
            $values = [$values];
        }

        list($before, $options_html_tmp) = explode($attribs['tags'][0], $form_html, 2);
        list($options_html, $after) = explode('</select>', $options_html_tmp, 2);
        $new_options_html = str_replace('selected="selected"', '', $options_html);
        foreach ($values as $value) {
            $value4html = htmlspecialchars($value, ENT_QUOTES);

            $new_options_html = str_replace('value="' . $value4html . '"', 'value="' . $value4html . '" selected="selected"', $new_options_html);
        }

        return $before . $attribs['tags'][0] . $new_options_html . '</select>' . $after;
    }

    public function replaceCheckedRadios($form_html, $attribs, $field_name)
    {
        $value4html = htmlspecialchars($attribs['value'], ENT_QUOTES);

        preg_match_all('/<input\s+type\="radio"[^>]*name\="' . preg_quote($field_name) . '"[^>]*>/', $form_html, $matches, PREG_PATTERN_ORDER);

        $ret = $form_html;
        foreach ($matches[0] as $match_from) {
            $match_to = str_replace('checked="checked"', '', $match_from);
            if (strstr($match_from, 'value="' . $value4html . '"')) {
                $match_to = str_replace('value="' . $value4html . '"', 'value="' . $value4html . '" checked="checked"', $match_to);
            }
            $ret = str_replace($match_from, $match_to, $ret);
        }
        return $ret;
    }

    public function replaceCheckedCheckboxes($form_html, $attribs, $field_name)
    {
        $values = $attribs['value'];
        if (!is_array($values)) {
            $values = [$values];
        }

        preg_match_all('/<input\s+type\="checkbox"[^>]*name\="' . preg_quote($attribs['field_name_raw']) . '"[^>]*>/', $form_html, $matches, PREG_PATTERN_ORDER);

        $ret = $form_html;
        foreach ($matches[0] as $match_from) {
            $match_to = str_replace('checked="checked"', '', $match_from);
            foreach ($values as $value) {
                $value4html = htmlspecialchars($value, ENT_QUOTES);
                if (strstr($match_from, 'value="' . $value4html . '"')) {
                    $match_to = str_replace('value="' . $value4html . '"', 'value="' . $value4html . '" checked="checked"', $match_to);
                    break;
                }
            }
            $ret = str_replace($match_from, $match_to, $ret);
        }
        return $ret;
    }

    public function validateSelectOption($tag, $value)
    {
        $value4html = htmlspecialchars($value, ENT_QUOTES);

        list($before, $options_html_tmp) = explode($tag, $this->form_html, 2);
        list($options_html, $after) = explode('</select>', $options_html_tmp, 2);
        if (strstr($options_html, 'value="' . $value4html . '"')) {
            return true;
        } else {
            false;
        }
    }

    public function renderForMail($field_separator = "\n", $mid_separator = "\n")
    {
        $ret = '';
        foreach ($this->fields as $field_name => $attribs) {
            $ret .= $field_separator . $attribs['label'] . $mid_separator;
            if ($attribs['array_type'] == 'linear') {
                $ret .= implode($this->column_separator, $attribs['value']);
            } elseif ($attribs['count'] > 1 && is_array($attribs['value'])) {
                $ret .= implode($this->column_separator, $attribs['value']);
            } else {
                $ret .= $attribs['value'];
            }
        }

        return $ret;
    }

    public function renderForDB()
    {
        $ret = [];
        foreach ($this->fields as $field_name => $attribs) {
            $ret[$attribs['label']] = $attribs['value'];
        }
        return $ret;
    }

    public function convertZenToHan($text)
    {
        if (function_exists('mb_convert_kana')) {
            return mb_convert_kana($text, 'as');
        }
        return $text;
    }

    public function fildValueFromTag($tag)
    {
        if (preg_match('/value\s*\=\s*"([^"]+)"/', $tag, $regs)) {
            return trim($regs[1]);
        } else {
            return 'on';
        }
    }

    // The regex to match according to the RFC standard
    // "/^([0-9A-Za-z\/\Q!#$%&'*+-=?^_`{}|~.\E]+|(?:[0-9A-Za-z\/\Q!#$%&'*+-=?^_`{}|~.\E]+\.\"(?:[0-9A-Za-z\/\Q!#$%&'*+-=?^_`{}|~. (),:;
    //	<>@[]\E]+|\\\\\\\\|\\\\\")+\"\.)+[0-9A-Za-z\/\Q!#$%&'*+-=?^_`{}|~.\E]+|\"(?:[0-9A-Za-z\/\Q!#$%&'*+-=?^_`{}|~. (),:;
    //	<>@[]\E]+|\\\\\\\\|\\\\\")+\")@(?:[0-9A-Za-z\-\.]+|\[[0-9A-Za-z\-\.]+\])$/"

    public function checkEmailAddress($email)
    {
        // 1) Check that neither the local or domain part of the e-mail address have leading, trailing, or consecutive dots, and that it is in the correct format:
        if (!preg_match("/^\.|\.\.|\.@|@\.|\.$/", $email) && preg_match("/^[^@]+?@[^\\.]+?\..+$/", $email)) {
            // Email invalid because wrong characters or @ symbols.
            return false;
        }
        // 2) Tokenize the e-mail address by '@:'
        // Split it into sections to make life easier
        $email_array = explode("@", $email);
        $local_array = explode(".", $email_array[0]);

        //3) loop through each and re-concatenate all to get two strings: the local part (before the mandatory '@') and the domain part

        for ($i = 0; $i < sizeof($local_array); $i++) {
            if (!preg_match("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$", $local_array[$i])) {
                return false;
            }
        }

        //4) If the first element/local part of the address does not contain any quotation marks, then use this pattern:
        // $pattern = "/^[0-9A-Za-z\/\Q!#$%&'*+-=?^_`{}|~.\E]+$/";

        //5) Else if the local part begins AND ends with quotation marks, use this pattern:
        // $pattern = "/^\"(?:[0-9A-Za-z\/\Q!#$%&'*+-=?^_`{}|~. (),:;<>@[]\E]+|\\\\\\\\|\\\\\")+\"$/";

        //6) Else if the local part contains TWO quotation marks (one only would invalidate), use this pattern:
        // $pattern = "/^(?:[0-9A-Za-z\/\Q!#$%&'*+-=?^_`{}|~.\E]+\.\"(?:[0-9A-Za-z\/\Q!#$%&'*+-=?^_`{}|~. (),:;<>@[]\E]+|\\\\\\\\|\\\\\")+\"\.)+[0-9A-Za-z\/\Q!#$%&'*+-=?^_`{}|~.\E]+$/";

        //7) Else the first part is invalid

        if (!preg_match("^\[?[0-9\.]+\]?$", $email_array[1])) {
            //8) If the local part was valid: check if domain is IP. If not, it should be valid domain name
            $domain_array = explode(".", $email_array[1]);

            if (sizeof($domain_array) < 2) {
                return false; // Not enough parts to domain
            }
            // If the second domain part is either encapsulated within square brackets ([]) or contains NO square brackets
            // (just use substr and substr_count, it is much faster than regex), and it matches the pattern:
            // preg_match("/^\[?[0-9A-Za-z\-\.]+\]?$/", $domain_array[$1]);
            for ($i = 0; $i < sizeof($domain_array); $i++) {
                //if (!preg_match("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$", $domain_array[$i])) {
                if (!preg_match("/^\[?[0-9A-Za-z\-\.]+\]?$/", $domain_array[$i])) {
                    return false;
                }
            }
        }
        return true; //	Then it is valid.
    }
}
