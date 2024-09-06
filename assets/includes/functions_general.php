<?php
// +------------------------------------------------------------------------+
// | @author Deen Doughouz (DoughouzForest)
// | @author_url 1: http://www.playtubescript.com
// | @author_url 2: http://codecanyon.net/user/doughouzforest
// | @author_email: wowondersocial@gmail.com
// +------------------------------------------------------------------------+
// | PlayTube - The Ultimate Video Sharing Platform
// | Copyright (c) 2017 PlayTube. All rights reserved.
// +------------------------------------------------------------------------+
function PT_LoadPage($page_url = '', $data = array(), $set_lang = true) {
    global $pt, $lang_array, $config, $fl_currpage, $countries_name,$db;
    $page = './themes/' . $config['theme'] . '/layout/' . $page_url . '.html';
    if (!file_exists($page)) {
        die("File not Exists : $page");
    }
    $page_content = '';
    ob_start();
    require($page);
    $page_content = ob_get_contents();
    ob_end_clean();
    if ($set_lang == true) {
        $page_content = preg_replace_callback("/{{LANG (.*?)}}/", function($m) use ($lang_array) {
            return (isset($lang_array[$m[1]])) ? $lang_array[$m[1]] : '';
        }, $page_content);
    }
    if (!empty($data) && is_array($data)) {
        foreach ($data as $key => $replace) {
            if ($key == 'USER_DATA') {
                $replace = ToArray($replace);
                $page_content = preg_replace_callback("/{{USER (.*?)}}/", function($m) use ($replace) {
                    return (isset($replace[$m[1]])) ? $replace[$m[1]] : '';
                }, $page_content);
            } else {
                $object_to_replace = "{{" . $key . "}}";
                $page_content      = str_replace($object_to_replace, $replace, $page_content);
            }
        }
    }
    if (IS_LOGGED == true) {
        $replace = ToArray($pt->user);
        $page_content = preg_replace_callback("/{{ME (.*?)}}/", function($m) use ($replace) {
            return (isset($replace[$m[1]])) ? $replace[$m[1]] : '';
        }, $page_content);
    }
    $page_content = preg_replace("/{{LINK (.*?)}}/", PT_Link("$1"), $page_content);
    $page_content = preg_replace_callback("/{{CONFIG (.*?)}}/", function($m) use ($config) {
        return (isset($config[$m[1]])) ? $config[$m[1]] : '';
    }, $page_content);
    return $page_content;
}
function PT_LoadAdminPage($page_url = '', $data = array(), $set_lang = true) {
    global $pt, $lang_array, $config, $db;
    $page = './admin-panel/pages/' . $page_url . '.html';
    if (!file_exists($page)) {
        return false;
    }
    $page_content = '';
    ob_start();
    require($page);
    $page_content = ob_get_contents();
    ob_end_clean();
    if ($set_lang == true) {
        $page_content = preg_replace_callback("/{{LANG (.*?)}}/", function($m) use ($lang_array) {
            return (isset($lang_array[$m[1]])) ? $lang_array[$m[1]] : '';
        }, $page_content);
    }
    if (!empty($data) && is_array($data)) {
        foreach ($data as $key => $replace) {
            if ($key == 'USER_DATA') {
                $replace = ToArray($replace);
                $page_content = preg_replace_callback("/{{USER (.*?)}}/", function($m) use ($replace) {
                    return (isset($replace[$m[1]])) ? $replace[$m[1]] : '';
                }, $page_content);
            } else {
                $object_to_replace = "{{" . $key . "}}";
                $page_content      = str_replace($object_to_replace, $replace, $page_content);
            }
        }
    }
    if (IS_LOGGED == true) {
        $replace = ToArray($pt->user);
        $page_content = preg_replace_callback("/{{ME (.*?)}}/", function($m) use ($replace) {
            return (isset($replace[$m[1]])) ? $replace[$m[1]] : '';
        }, $page_content);
    }
    $page_content = preg_replace("/{{LINK (.*?)}}/", PT_Link("$1"), $page_content);
    $page_content = preg_replace_callback("/{{CONFIG (.*?)}}/", function($m) use ($config) {
        return (isset($config[$m[1]])) ? $config[$m[1]] : '';
    }, $page_content);
    return $page_content;
}
function PT_Link($string) {
    global $site_url;
    return $site_url . '/' . $string;
}
function PT_Slug($string, $video_id) {
    global $pt;
    if ($pt->config->seo_link != 'on') {
        return $video_id;
    }
    $slug = url_slug($string, array(
        'delimiter' => '-',
        'limit' => 100,
        'lowercase' => true,
        'replacements' => array(
            '/\b(an)\b/i' => 'a',
            '/\b(example)\b/i' => 'Test'
        )
    ));
    return $slug . '_' . $video_id . '.html';
}
function PT_URLSlug($string, $id) {
    global $pt;
    $slug = url_slug($string, array(
        'delimiter' => '-',
        'limit' => 100,
        'lowercase' => true,
        'replacements' => array(
            '/\b(an)\b/i' => 'a',
            '/\b(example)\b/i' => 'Test'
        )
    ));
    return $slug . '_' . $id . '.html';
}
function PT_LoadAdminLinkSettings($link = '') {
    global $site_url;
    return $site_url . '/admin-cp/' . $link;
}
function PT_LoadAdminLink($link = '') {
    global $site_url;
    return $site_url . '/admin-panel/' . $link;
}
function url_slug($str, $options = array()) {
    // Make sure string is in UTF-8 and strip invalid UTF-8 characters
    $str      = mb_convert_encoding((string) $str, 'UTF-8', mb_list_encodings());
    $defaults = array(
        'delimiter' => '-',
        'limit' => null,
        'lowercase' => true,
        'replacements' => array(),
        'transliterate' => false
    );
    // Merge options
    $options  = array_merge($defaults, $options);
    $char_map = array(
        // Latin
        'À' => 'A',
        'Á' => 'A',
        'Â' => 'A',
        'Ã' => 'A',
        'Ä' => 'A',
        'Å' => 'A',
        'Æ' => 'AE',
        'Ç' => 'C',
        'È' => 'E',
        'É' => 'E',
        'Ê' => 'E',
        'Ë' => 'E',
        'Ì' => 'I',
        'Í' => 'I',
        'Î' => 'I',
        'Ï' => 'I',
        'Ð' => 'D',
        'Ñ' => 'N',
        'Ò' => 'O',
        'Ó' => 'O',
        'Ô' => 'O',
        'Õ' => 'O',
        'Ö' => 'O',
        'Ő' => 'O',
        'Ø' => 'O',
        'Ù' => 'U',
        'Ú' => 'U',
        'Û' => 'U',
        'Ü' => 'U',
        'Ű' => 'U',
        'Ý' => 'Y',
        'Þ' => 'TH',
        'ß' => 'ss',
        'à' => 'a',
        'á' => 'a',
        'â' => 'a',
        'ã' => 'a',
        'ä' => 'a',
        'å' => 'a',
        'æ' => 'ae',
        'ç' => 'c',
        'è' => 'e',
        'é' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'ì' => 'i',
        'í' => 'i',
        'î' => 'i',
        'ï' => 'i',
        'ð' => 'd',
        'ñ' => 'n',
        'ò' => 'o',
        'ó' => 'o',
        'ô' => 'o',
        'õ' => 'o',
        'ö' => 'o',
        'ő' => 'o',
        'ø' => 'o',
        'ù' => 'u',
        'ú' => 'u',
        'û' => 'u',
        'ü' => 'u',
        'ű' => 'u',
        'ý' => 'y',
        'þ' => 'th',
        'ÿ' => 'y',
        // Latin symbols
        '©' => '(c)',
        // Greek
        'Α' => 'A',
        'Β' => 'B',
        'Γ' => 'G',
        'Δ' => 'D',
        'Ε' => 'E',
        'Ζ' => 'Z',
        'Η' => 'H',
        'Θ' => '8',
        'Ι' => 'I',
        'Κ' => 'K',
        'Λ' => 'L',
        'Μ' => 'M',
        'Ν' => 'N',
        'Ξ' => '3',
        'Ο' => 'O',
        'Π' => 'P',
        'Ρ' => 'R',
        'Σ' => 'S',
        'Τ' => 'T',
        'Υ' => 'Y',
        'Φ' => 'F',
        'Χ' => 'X',
        'Ψ' => 'PS',
        'Ω' => 'W',
        'Ά' => 'A',
        'Έ' => 'E',
        'Ί' => 'I',
        'Ό' => 'O',
        'Ύ' => 'Y',
        'Ή' => 'H',
        'Ώ' => 'W',
        'Ϊ' => 'I',
        'Ϋ' => 'Y',
        'α' => 'a',
        'β' => 'b',
        'γ' => 'g',
        'δ' => 'd',
        'ε' => 'e',
        'ζ' => 'z',
        'η' => 'h',
        'θ' => '8',
        'ι' => 'i',
        'κ' => 'k',
        'λ' => 'l',
        'μ' => 'm',
        'ν' => 'n',
        'ξ' => '3',
        'ο' => 'o',
        'π' => 'p',
        'ρ' => 'r',
        'σ' => 's',
        'τ' => 't',
        'υ' => 'y',
        'φ' => 'f',
        'χ' => 'x',
        'ψ' => 'ps',
        'ω' => 'w',
        'ά' => 'a',
        'έ' => 'e',
        'ί' => 'i',
        'ό' => 'o',
        'ύ' => 'y',
        'ή' => 'h',
        'ώ' => 'w',
        'ς' => 's',
        'ϊ' => 'i',
        'ΰ' => 'y',
        'ϋ' => 'y',
        'ΐ' => 'i',
        // Turkish
        'Ş' => 'S',
        'İ' => 'I',
        'Ç' => 'C',
        'Ü' => 'U',
        'Ö' => 'O',
        'Ğ' => 'G',
        'ş' => 's',
        'ı' => 'i',
        'ç' => 'c',
        'ü' => 'u',
        'ö' => 'o',
        'ğ' => 'g',
        // Russian
        'А' => 'A',
        'Б' => 'B',
        'В' => 'V',
        'Г' => 'G',
        'Д' => 'D',
        'Е' => 'E',
        'Ё' => 'Yo',
        'Ж' => 'Zh',
        'З' => 'Z',
        'И' => 'I',
        'Й' => 'J',
        'К' => 'K',
        'Л' => 'L',
        'М' => 'M',
        'Н' => 'N',
        'О' => 'O',
        'П' => 'P',
        'Р' => 'R',
        'С' => 'S',
        'Т' => 'T',
        'У' => 'U',
        'Ф' => 'F',
        'Х' => 'H',
        'Ц' => 'C',
        'Ч' => 'Ch',
        'Ш' => 'Sh',
        'Щ' => 'Sh',
        'Ъ' => '',
        'Ы' => 'Y',
        'Ь' => '',
        'Э' => 'E',
        'Ю' => 'Yu',
        'Я' => 'Ya',
        'а' => 'a',
        'б' => 'b',
        'в' => 'v',
        'г' => 'g',
        'д' => 'd',
        'е' => 'e',
        'ё' => 'yo',
        'ж' => 'zh',
        'з' => 'z',
        'и' => 'i',
        'й' => 'j',
        'к' => 'k',
        'л' => 'l',
        'м' => 'm',
        'н' => 'n',
        'о' => 'o',
        'п' => 'p',
        'р' => 'r',
        'с' => 's',
        'т' => 't',
        'у' => 'u',
        'ф' => 'f',
        'х' => 'h',
        'ц' => 'c',
        'ч' => 'ch',
        'ш' => 'sh',
        'щ' => 'sh',
        'ъ' => '',
        'ы' => 'y',
        'ь' => '',
        'э' => 'e',
        'ю' => 'yu',
        'я' => 'ya',
        // Ukrainian
        'Є' => 'Ye',
        'І' => 'I',
        'Ї' => 'Yi',
        'Ґ' => 'G',
        'є' => 'ye',
        'і' => 'i',
        'ї' => 'yi',
        'ґ' => 'g',
        // Czech
        'Č' => 'C',
        'Ď' => 'D',
        'Ě' => 'E',
        'Ň' => 'N',
        'Ř' => 'R',
        'Š' => 'S',
        'Ť' => 'T',
        'Ů' => 'U',
        'Ž' => 'Z',
        'č' => 'c',
        'ď' => 'd',
        'ě' => 'e',
        'ň' => 'n',
        'ř' => 'r',
        'š' => 's',
        'ť' => 't',
        'ů' => 'u',
        'ž' => 'z',
        // Polish
        'Ą' => 'A',
        'Ć' => 'C',
        'Ę' => 'e',
        'Ł' => 'L',
        'Ń' => 'N',
        'Ó' => 'o',
        'Ś' => 'S',
        'Ź' => 'Z',
        'Ż' => 'Z',
        'ą' => 'a',
        'ć' => 'c',
        'ę' => 'e',
        'ł' => 'l',
        'ń' => 'n',
        'ó' => 'o',
        'ś' => 's',
        'ź' => 'z',
        'ż' => 'z',
        // Latvian
        'Ā' => 'A',
        'Č' => 'C',
        'Ē' => 'E',
        'Ģ' => 'G',
        'Ī' => 'i',
        'Ķ' => 'k',
        'Ļ' => 'L',
        'Ņ' => 'N',
        'Š' => 'S',
        'Ū' => 'u',
        'Ž' => 'Z',
        'ā' => 'a',
        'č' => 'c',
        'ē' => 'e',
        'ģ' => 'g',
        'ī' => 'i',
        'ķ' => 'k',
        'ļ' => 'l',
        'ņ' => 'n',
        'š' => 's',
        'ū' => 'u',
        'ž' => 'z'
    );
    // Make custom replacements
    $str      = preg_replace(array_keys($options['replacements']), $options['replacements'], $str);
    // Transliterate characters to ASCII
    if ($options['transliterate']) {
        $str = str_replace(array_keys($char_map), $char_map, $str);
    }
    // Replace non-alphanumeric characters with our delimiter
    $str = preg_replace('/[^\p{L}\p{Nd}]+/u', $options['delimiter'], $str);
    // Remove duplicate delimiters
    $str = preg_replace('/(' . preg_quote($options['delimiter'], '/') . '){2,}/', '$1', $str);
    // Truncate slug to max. characters
    $str = mb_substr($str, 0, ($options['limit'] ? $options['limit'] : mb_strlen($str, 'UTF-8')), 'UTF-8');
    // Remove delimiter from ends
    $str = trim($str, $options['delimiter']);
    return $options['lowercase'] ? mb_strtolower($str, 'UTF-8') : $str;
}
function br2nl($st) {
    if (empty($st)) {
        return $st;
    }
    $breaks = array(
        "<br />",
        "<br>",
        "<br/>"
    );
    return str_ireplace($breaks, "\r\n", $st);
}
function ToObject($array) {
    $object = new stdClass();
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $value = ToObject($value);
        }
        if (isset($value)) {
            $object->$key = $value;
        }
    }
    return $object;
}
function ToArray($obj) {
    if (is_object($obj))
        $obj = (array) $obj;
    if (is_array($obj)) {
        $new = array();
        foreach ($obj as $key => $val) {
            $new[$key] = ToArray($val);
        }
    } else {
        $new = $obj;
    }
    return $new;
}

function PT_Secure($string, $censored_words = 0, $br = true) {
    global $mysqli;
    if (is_array($string)) {
       return '';
    }
    $string = trim($string);
    $string = mysqli_real_escape_string($mysqli, $string);
    $string = htmlspecialchars($string, ENT_QUOTES);
    if ($br == true) {
        $string = str_replace('\r\n', " <br>", $string);
        $string = str_replace('\n\r', " <br>", $string);
        $string = str_replace('\r', " <br>", $string);
        $string = str_replace('\n', " <br>", $string);
    } else {
        $string = str_replace('\r\n', "", $string);
        $string = str_replace('\n\r', "", $string);
        $string = str_replace('\r', "", $string);
        $string = str_replace('\n', "", $string);
    }
    $string = stripslashes($string);
    $string = str_replace('&amp;#', '&#', $string);
    $string = preg_replace("/{{(.*?)}}/", '', $string);
    if ($censored_words == 1) {
        global $config;
        $censored_words = @explode(",", $config['censored_words']);
        foreach ($censored_words as $censored_word) {
            $censored_word = trim($censored_word);
            $string        = str_replace($censored_word, '****', $string);
        }
    }
    return $string;
}
function PT_IsLogged() {
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        $id = PT_GetUserFromSessionID($_SESSION['user_id']);
        if (is_numeric($id) && !empty($id)) {
            return true;
        }
    }

    else if (!empty($_COOKIE['user_id']) && !empty($_COOKIE['user_id'])) {
        $id = PT_GetUserFromSessionID($_COOKIE['user_id']);
        if (is_numeric($id) && !empty($id)) {
            return true;
        }
    }

    else {
        return false;
    }
}
function PT_GetUserFromSessionID($session_id, $platform = 'web') {
    global $db;
    if (empty($session_id)) {
        return false;
    }
    $platform   = PT_Secure($platform);
    $session_id = PT_Secure($session_id);
    $return     = $db->where('session_id', $session_id);
    $return     = $db->where('platform', $platform);
    return $db->getValue(T_SESSIONS, 'user_id');
}


function PT_GenerateKey($minlength = 20, $maxlength = 20, $uselower = true, $useupper = true, $usenumbers = true, $usespecial = false) {
    $charset = '';
    if ($uselower) {
        $charset .= "abcdefghijklmnopqrstuvwxyz";
    }
    if ($useupper) {
        $charset .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    }
    if ($usenumbers) {
        $charset .= "123456789";
    }
    if ($usespecial) {
        $charset .= "~@#$%^*()_+-={}|][";
    }
    if ($minlength > $maxlength) {
        $length = mt_rand($maxlength, $minlength);
    } else {
        $length = mt_rand($minlength, $maxlength);
    }
    $key = '';
    for ($i = 0; $i < $length; $i++) {
        $key .= $charset[(mt_rand(0, strlen($charset) - 1))];
    }
    return $key;
}
function PT_Resize_Crop_Image($max_width, $max_height, $source_file, $dst_dir, $quality = 80) {
    $imgsize = @getimagesize($source_file);
    $width   = $imgsize[0];
    $height  = $imgsize[1];
    $mime    = $imgsize['mime'];
    switch ($mime) {
        case 'image/gif':
            $image_create = "imagecreatefromgif";
            $image        = "imagegif";
            break;
        case 'image/png':
            $image_create = "imagecreatefrompng";
            $image        = "imagepng";
            break;
        case 'image/jpeg':
            $image_create = "imagecreatefromjpeg";
            $image        = "imagejpeg";
            break;
        case 'image/webp':
            if (function_exists("imagecreatefromwebp")) {
                $image_create = "imagecreatefromwebp";
                $image        = "imagewebp";
            } else {
                return;
            }
            break;
        default:
            return false;
            break;
    }
    $dst_img    = @imagecreatetruecolor($max_width, $max_height);
    $src_img    = $image_create($source_file);
    $width_new  = $height * $max_width / $max_height;
    $height_new = $width * $max_height / $max_width;
    if ($width_new > $width) {
        $h_point = (($height - $height_new) / 2);
        @imagecopyresampled($dst_img, $src_img, 0, 0, 0, $h_point, $max_width, $max_height, $width, $height_new);
    } else {
        $w_point = (($width - $width_new) / 2);
        @imagecopyresampled($dst_img, $src_img, 0, 0, $w_point, 0, $max_width, $max_height, $width_new, $height);
    }
    @imagejpeg($dst_img, $dst_dir, $quality);
    if ($dst_img)
        @imagedestroy($dst_img);
    if ($src_img)
        @imagedestroy($src_img);
}

function PT_CompressImage($source_url, $destination_url, $quality) {
    $info = getimagesize($source_url);
    if ($info['mime'] == 'image/jpeg') {
        $image = @imagecreatefromjpeg($source_url);
        @imagejpeg($image, $destination_url, $quality);
    } elseif ($info['mime'] == 'image/gif') {
        $image = @imagecreatefromgif($source_url);
        @imagegif($image, $destination_url, $quality);
    } elseif ($info['mime'] == 'image/png') {
        $image = @imagecreatefrompng($source_url);
        @imagepng($image, $destination_url);
    } elseif ($info['mime'] == 'image/webp') {
        if (function_exists("imagecreatefromwebp")) {
            $image = @imagecreatefromwebp($source_url);
            @imagewebp($image, $destination_url, $quality);
        } else {
            return;
        }
    }
}
function substitute($stringOrFunction, $number) {
    return $number . ' ' . $stringOrFunction;
}
function PT_Time_Elapsed_String_chat($ptime) {
    global $pt, $lang;
    $etime = (time()) - $ptime;
    if ($etime < 1) {
        return $lang->now;
    }
    $seconds = abs($etime);
    $minutes = $seconds / 60;
    $hours   = $minutes / 60;
    $days    = $hours / 24;
    $weeks   = $days / 7;
    $years   = $days / 365;
    if ($seconds < 45) {
        return substitute($lang->now, '');
    } elseif ($seconds < 90) {
        return substitute($lang->_time_m, 1);
    } elseif ($minutes < 45) {
        return substitute($lang->_time_m, round($minutes));
    } elseif ($minutes < 90) {
        return substitute($lang->_time_h, 1);
    } elseif ($hours < 24) {
        return substitute($lang->_time_hrs, round($hours));
    } elseif ($hours < 42) {
        return substitute($lang->_time_d, 1);
    } elseif ($days < 7) {
        return substitute($lang->_time_d, round($days));
    } elseif ($weeks < 2) {
        return substitute($lang->_time_w, 1);
    } elseif ($weeks < 52) {
        return substitute($lang->_time_w, round($weeks));
    } elseif ($years < 1.5) {
        return substitute($lang->_time_y, 1);
    } else {
        return substitute($lang->_time_yrs, round($years));
    }
}
function PT_Time_Elapsed_String($ptime) {
    global $pt, $lang;
    $etime = time() - $ptime;
    if ($etime < 1) {
        return '0 seconds';
    }
    $a        = array(
        365 * 24 * 60 * 60 => $lang->year,
        30 * 24 * 60 * 60 => $lang->month,
        24 * 60 * 60 => $lang->day,
        60 * 60 => $lang->hour,
        60 => $lang->minute,
        1 => $lang->second
    );
    $a_plural = array(
        $lang->year => $lang->years,
        $lang->month => $lang->months,
        $lang->day => $lang->days,
        $lang->hour => $lang->hours,
        $lang->minute => $lang->minutes,
        $lang->second => $lang->seconds
    );
    foreach ($a as $secs => $str) {
        $d = $etime / $secs;
        if ($d >= 1) {
            $r = round($d);
            if ($pt->language_type == 'rtl') {
                $time_ago = $lang->time_ago . ' ' . $r . ' ' . ($r > 1 ? $a_plural[$str] : $str);
            } else {
                $time_ago = $r . ' ' . ($r > 1 ? $a_plural[$str] : $str) . ' ' . $lang->time_ago;
            }
            return $time_ago;
        }
    }
}
function check_($check) {
	return array('status'=>'SUCCESS');
    $siteurl = urlencode($_SERVER['SERVER_NAME']);
    $file    = file_get_contents('http://www.playtubescript.com/purchase.php?code=' . $check . '&url=' . $siteurl);
    $check   = json_decode($file, true);
    return $check;
}
function check_success($check) {
	return array('status'=>'SUCCESS');
    $siteurl = urlencode($_SERVER['SERVER_NAME']);
    $file    = file_get_contents('http://www.playtubescript.com/purchase.php?code=' . $check . '&success=true&url=' . $siteurl);
    $check   = json_decode($file, true);
    return $check;
}
function PT_EditMarkup($text, $link = true, $hashtag = true) {
    global $db;
    if ($link == true && !empty($text)) {
        $link_search = '/\[a\](.*?)\[\/a\]/i';
        if (preg_match_all($link_search, $text, $matches)) {
            foreach ($matches[1] as $match) {
                $match_decode     = urldecode($match);
                $match_decode_url = $match_decode;
                $count_url        = mb_strlen($match_decode);
                $match_url        = $match_decode;
                if (!preg_match("/http(|s)\:\/\//", $match_decode)) {
                    $match_url = 'http://' . $match_url;
                }
                $text = str_replace('[a]' . $match . '[/a]', $match_decode_url, $text);
            }
        }
    }

    if ($hashtag == true && !empty($text)) {
        $hashtag_regex = '/(#\[([0-9]+)\])/';
        preg_match_all($hashtag_regex, $text, $matches);
        foreach ($matches[1] as $key => $value) {
            $hashtag  = $matches[1][$key];
            $hashkey  = $matches[2][$key];
            $tagData = $db->where('id',$hashkey)->getOne(T_HASHTAGS);
            if (!empty($tagData)) {
                $hashlink = '#' . $tagData->tag;
                $text     = str_replace($hashtag, $hashlink, $text);
            }
        }
    }
    return $text;
}
function PT_Markup($text, $link = true, $hashtag = true,$html = true) {
    global $db,$pt;
    if ($link == true && !empty($text)) {
        $link_search = '/\[a\](.*?)\[\/a\]/i';
        if (preg_match_all($link_search, $text, $matches)) {
            foreach ($matches[1] as $match) {
                $match_decode     = urldecode($match);
                $match_decode_url = $match_decode;
                $count_url        = mb_strlen($match_decode);
                if ($count_url > 50) {
                    $match_decode_url = mb_substr($match_decode_url, 0, 30) . '....' . mb_substr($match_decode_url, 30, 20);
                }
                $match_url = $match_decode;
                if (!preg_match("/http(|s)\:\/\//", $match_decode)) {
                    $match_url = 'http://' . $match_url;
                }
                $text = str_replace('[a]' . $match . '[/a]', '<a href="' . strip_tags($match_url) . '" target="_blank" class="hash" rel="nofollow">' . $match_decode_url . '</a>', $text);
            }
        }
    }
    if ($hashtag == true && !empty($text)) {
        $hashtag_regex = '/(#\[([0-9]+)\])/';
        preg_match_all($hashtag_regex, $text, $matches);
        foreach ($matches[1] as $key => $value) {
            $hashtag  = $matches[1][$key];
            $hashkey  = $matches[2][$key];
            $tagData = $db->where('id',$hashkey)->getOne(T_HASHTAGS);
            if (!empty($tagData)) {
                $hashlink = '#' . $tagData->tag;
                if ($html == true && $pt->config->hashtag_system == 'on') {
                    $hashlink = '<a class="hash" href="'.PT_Link('hashtag/'.$tagData->tag).'" data-load="?link1=hashtag&id='.$tagData->tag.'">#' . $tagData->tag . '</a>';
                }
                
                $text     = str_replace($hashtag, $hashlink, $text);
            }
        }
    }
    return $text;
}
function covtime($youtube_time) {
    $start = new DateTime('@0'); // Unix epoch
    $start->add(new DateInterval($youtube_time));
    return $start->format('H:i:s');
}

function PT_CreateSession() {
    $hash = sha1(rand(1111, 9999));
    if (!empty($_SESSION['hash_id'])) {
        $_SESSION['hash_id'] = $_SESSION['hash_id'];
        return $_SESSION['hash_id'];
    }
    $_SESSION['hash_id'] = $hash;
    return $hash;
}
function PT_ShortText($text = "", $len = 100) {
    if (empty($text) || !is_string($text) || !is_numeric($len) || $len < 1) {
        return "****";
    }
    if (strlen($text) > $len) {
        $text = mb_substr($text, 0, $len, "UTF-8") . "..";
    }
    return $text;
}
function PT_GetIdFromURL($url = false) {
    if (!$url) {
        return false;
    }
    $slug = @end(explode('_', $url));
    $id   = 0;
    $slug = explode('.', $slug);
    $id   = (is_array($slug) && !empty($slug[0]) && is_numeric($slug[0])) ? $slug[0] : 0;
    return $id;
}
function PT_Decode($text = '') {
    return htmlspecialchars_decode($text);
}
function PT_Backup($sql_db_host, $sql_db_user, $sql_db_pass, $sql_db_name, $tables = false, $backup_name = false) {
    $mysqli = new mysqli($sql_db_host, $sql_db_user, $sql_db_pass, $sql_db_name);
    $mysqli->select_db($sql_db_name);
    $mysqli->query("SET NAMES 'utf8'");
    $queryTables = $mysqli->query('SHOW TABLES');
    while ($row = $queryTables->fetch_row()) {
        $target_tables[] = $row[0];
    }
    if ($tables !== false) {
        $target_tables = array_intersect($target_tables, $tables);
    }
    $content = "-- phpMyAdmin SQL Dump
-- http://www.phpmyadmin.net
--
-- Host Connection Info: " . $mysqli->host_info . "
-- Generation Time: " . date('F d, Y \a\t H:i A ( e )') . "
-- Server version: " . mysqli_get_server_info($mysqli) . "
-- PHP Version: " . PHP_VERSION . "
--\n
SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";
SET time_zone = \"+00:00\";\n
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;\n\n";
    foreach ($target_tables as $table) {
        $result        = $mysqli->query('SELECT * FROM ' . $table);
        $fields_amount = $result->field_count;
        $rows_num      = $mysqli->affected_rows;
        $res           = $mysqli->query('SHOW CREATE TABLE ' . $table);
        $TableMLine    = $res->fetch_row();
        $content       = (!isset($content) ? '' : $content) . "
-- ---------------------------------------------------------
--
-- Table structure for table : `{$table}`
--
-- ---------------------------------------------------------
\n" . $TableMLine[1] . ";\n";
        for ($i = 0, $st_counter = 0; $i < $fields_amount; $i++, $st_counter = 0) {
            while ($row = $result->fetch_row()) {
                if ($st_counter % 100 == 0 || $st_counter == 0) {
                    $content .= "\n--
-- Dumping data for table `{$table}`
--\n\nINSERT INTO " . $table . " VALUES";
                }
                $content .= "\n(";
                for ($j = 0; $j < $fields_amount; $j++) {
                    $row[$j] = str_replace("\n", "\\n", addslashes($row[$j]));
                    if (isset($row[$j])) {
                        $content .= '"' . $row[$j] . '"';
                    } else {
                        $content .= '""';
                    }
                    if ($j < ($fields_amount - 1)) {
                        $content .= ',';
                    }
                }
                $content .= ")";
                if ((($st_counter + 1) % 100 == 0 && $st_counter != 0) || $st_counter + 1 == $rows_num) {
                    $content .= ";\n";
                } else {
                    $content .= ",";
                }
                $st_counter = $st_counter + 1;
            }
        }
        $content .= "";
    }
    $content .= "
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;";
    if (!file_exists('script_backups/' . date('d-m-Y'))) {
        @mkdir('script_backups/' . date('d-m-Y'), 0777, true);
    }
    if (!file_exists('script_backups/' . date('d-m-Y') . '/' . time())) {
        mkdir('script_backups/' . date('d-m-Y') . '/' . time(), 0777, true);
    }
    if (!file_exists("script_backups/" . date('d-m-Y') . '/' . time() . "/index.html")) {
        $f = @fopen("script_backups/" . date('d-m-Y') . '/' . time() . "/index.html", "a+");
        @fwrite($f, "");
        @fclose($f);
    }
    if (!file_exists('script_backups/.htaccess')) {
        $f = @fopen("script_backups/.htaccess", "a+");
        @fwrite($f, "deny from all\nOptions -Indexes");
        @fclose($f);
    }
    if (!file_exists("script_backups/" . date('d-m-Y') . "/index.html")) {
        $f = @fopen("script_backups/" . date('d-m-Y') . "/index.html", "a+");
        @fwrite($f, "");
        @fclose($f);
    }
    if (!file_exists('script_backups/index.html')) {
        $f = @fopen("script_backups/index.html", "a+");
        @fwrite($f, "");
        @fclose($f);
    }
    $folder_name = "script_backups/" . date('d-m-Y') . '/' . time();
    $put         = @file_put_contents($folder_name . '/SQL-Backup-' . time() . '-' . date('d-m-Y') . '.sql', $content);
    if ($put) {
        $rootPath = realpath('./');
        $zip      = new ZipArchive();
        $open     = $zip->open($folder_name . '/Files-Backup-' . time() . '-' . date('d-m-Y') . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($open !== true) {
            return false;
        }
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath), RecursiveIteratorIterator::LEAVES_ONLY);
        foreach ($files as $name => $file) {
            if (!preg_match('/\bscript_backups\b/', $file)) {
                if (!$file->isDir()) {
                    $filePath     = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($rootPath) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }
        $zip->close();
        $table = T_CONFIG;
        $date  = date('d-m-Y');
        $mysqli->query("UPDATE `$table` SET `value` = '$date' WHERE `name` = 'last_backup'");
        $mysqli->close();
        return true;
    } else {
        return false;
    }
}
function pt_size_format($bytes) {
    global $lang_array;
    $size = array('1' => '0MB',
                  '2000000' => '2MB',
                  '6000000' => '6MB',
                  '12000000' => '12MB',
                  '24000000' => '24MB',
                  '48000000' => '48MB',
                  '96000000' => '96MB',
                  '256000000' => '256MB',
                  '512000000' => '512MB',
                  '1000000000' => '1GB',
                  '10000000000' => '10GB',
                  '0' => $lang_array['unlimited']);
    return $size[$bytes];
}
// function pt_size_format($bytes) {
//     $kb = 1024;
//     $mb = $kb * 1024;
//     $gb = $mb * 1024;
//     $tb = $gb * 1024;
//     if (($bytes >= 0) && ($bytes < $kb)) {
//         return $bytes . ' B';
//     } elseif (($bytes >= $kb) && ($bytes < $mb)) {
//         return ceil($bytes / $kb) . ' KB';
//     } elseif (($bytes >= $mb) && ($bytes < $gb)) {
//         return ceil($bytes / $mb) . ' MB';
//     } elseif (($bytes >= $gb) && ($bytes < $tb)) {
//         return ceil($bytes / $gb) . ' GB';
//     } elseif ($bytes >= $tb) {
//         return ceil($bytes / $tb) . ' TB';
//     } else {
//         return $bytes . ' B';
//     }
// }
function pt_delete_field($id = false) {
    global $pt, $sqlConnect;
    if (IS_LOGGED == false || !PT_IsAdmin()) {
        return false;
    }
    $id    = PT_Secure($id);
    $table = T_FIELDS;
    $query = mysqli_query($sqlConnect, "DELETE FROM `$table` WHERE `id` = {$id}");
    if ($query) {
        $table  = T_USR_PROF_FIELDS;
        $query2 = mysqli_query($sqlConnect, "ALTER TABLE `$table` DROP `fid_{$id}`;");
        if ($query2) {
            return true;
        }
    }
    return false;
}
function pt_is_url($url = false) {
    if (empty($url)) {
        return false;
    }
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        return true;
    }
    return false;
}
function pt_get_user_ads($placement = 1) {
    global $pt, $db;
    $t_users = T_USERS;
    // $db->where(" `user_id` IN (SELECT `id` FROM `$t_users` WHERE `wallet` > '0') ");
    // if ($pt->config->usr_v_mon == 'on' && $placement == 1) {
    //     $db->where(" `user_id` IN (SELECT `id` FROM `$t_users` WHERE `video_mon` = '1') ");
    // }
    // $db->where("`status`", 1);
    // $db->where("`placement`", $placement);
    $country = "";
    if (IS_LOGGED && !empty($pt->user->country_id)) {
        $usr_country = $pt->user->country_id;
        $country = " AND `audience` LIKE '%$usr_country%' ";
    }
    //$ad = $db->orderBy('RAND()')->getOne(T_USR_ADS);
    $all_spent = $db->rawQuery('SELECT user_id, SUM(spent) AS spent FROM '.T_USR_ADS.' WHERE status = 1 AND (type = 1 OR type = 2) GROUP BY user_id ORDER BY spent DESC');

    $users_ids = array(0);
    if ($all_spent) {
        foreach ($all_spent as $key => $value) {
            $user_data = $db->where('id',$value->user_id)->getOne(T_USERS);
            if ($user_data && (int) $user_data->wallet > 0 && (empty($user_data->total_ads) || $user_data->total_ads == 0 || ($user_data->total_ads > 0 && $user_data->total_ads > $value->spent))) {
                $users_ids[] = $value->user_id;
            }
        }
    }
    $ad = $db->rawQuery("select * FROM ".T_USR_ADS." where (day_limit > day_spend OR day_limit = 0) AND (lifetime_limit > spent OR lifetime_limit = 0)  AND placement = '".$placement."' AND status = 1 AND user_id IN (".implode(',', $users_ids).") ".$country." ORDER BY RAND() LIMIT 1");
    if (!empty($ad) && !empty($ad[0])) {
        $ad = $ad[0];
    }

    return (!empty($ad)) ? $ad : false;
}
function pt_register_ad_views($ad_id = false, $publisher_id = false) {
    global $pt, $db;
    if (empty($ad_id) || empty($publisher_id)) {
        return false;
    }
    $ad     = $db->where('id', $ad_id)->getOne(T_USR_ADS);
    $result = false;
    if (!empty($ad)) {
        $ad_owner     = $db->where('id', $ad->user_id)->getOne(T_USERS);
        $con_price    = $pt->config->ad_v_price;
        $pub_price    = $pt->config->pub_price;
        $ad_trans     = false;
        $is_owner     = false;
        $ad_tans_data = array(
            'results' => ($ad->results += 1)
        );
        if (IS_LOGGED) {
            $is_owner = ($ad->user_id == $pt->user->id) ? true : false;
        }
        if (!array_key_exists($ad->id, $pt->user_ad_cons['uaid_'])) {
            $video_owner = $db->where('id', $publisher_id)->getOne(T_USERS);
            if ((($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'off') || ($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'on' && $video_owner->monetization == '1')) && $video_owner->video_mon == 1) {

                if (!empty($video_owner) && ($ad->user_id != $video_owner->id)) {
                    $db->where('id', $publisher_id)->update(T_USERS, array(
                        'balance' => (($video_owner->balance += $pub_price))
                    ));
                    $db->insert(T_ADS_TRANS,array('amount' => $pub_price,'type' => 'video', 'ad_id' => $ad->id, 'video_owner' => $publisher_id, 'time' => time()));

                }
            }
            $ad_tans_data['spent']              = ($ad->spent += $con_price);
            $ad_trans                           = true;
            $pt->user_ad_cons['uaid_'][$ad->id] = $ad->id;
            setcookie('_uads', htmlentities(serialize($pt->user_ad_cons)), time() + (10 * 365 * 24 * 60 * 60), '/');
            $db->insert(T_ADS_TRANS,array('amount' => $con_price ,'type' => 'spent', 'ad_id' => $ad->id, 'video_owner' => $publisher_id, 'time' => time()));

        }


        $update = $db->where('id', $ad_id)->update(T_USR_ADS, $ad_tans_data);

        if ($update && $ad_trans && !$is_owner) {
            $ad_value = ($ad_owner->wallet -= $con_price);
            if ($ad_value < 0) {
                $ad_value = 0;
            }
            $db->where('id', $ad_owner->id)->update(T_USERS, array(
                'wallet' => $ad_value
            ));
            if ($ad->day_limit > 0) {
                if ($ad->day == date("Y-m-d")) {
                    $db->where('id',$ad->id)->update(T_USR_ADS,array('day_spend' => ($ad->day_spend + $con_price)));
                }
                else{
                    $db->where('id',$ad->id)->update(T_USR_ADS,array('day_spend' => $con_price ,
                                                                     'day'       => date("Y-m-d")));
                }
            }

            $result = true;
        }
    }
    return $result;
}
function clear_cookies() {
    foreach ($_COOKIE as $key => $value) {
        setcookie($key, $value, time() - 10000, "/");
    }
}
function pt_url_domain($url) {
    $host = @parse_url($url, PHP_URL_HOST);
    if (!$host) {
        $host = $url;
    }
    if (substr($host, 0, 4) == "www.") {
        $host = substr($host, 4);
    }
    if (strlen($host) > 50) {
        $host = substr($host, 0, 47) . '...';
    }
    return $host;
}

function pt_redirect($url) {
    header("Loacation: $url");
    exit();
}

function connect_to_url($url = '', $config = array()) {
    if (empty($url)) {
        return false;
    }
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.7.12) Gecko/20050915 Firefox/1.0.7");
    if (!empty($config['POST'])) {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $config['POST']);
    }
    if (!empty($config['bearer'])) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $config['bearer']
        ));
    }
    //execute the session
    $curl_response = curl_exec($curl);
    //finish off the session
    curl_close($curl);
    return $curl_response;
}

function connect_to_url_headers($url = '', $config = array()) {
    if (empty($url)) {
        return false;
    }
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_POST, 0);
    curl_setopt($curl, CURLOPT_HEADER, 1);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);

    //execute the session
    $curl_response = curl_exec($curl);
    //finish off the session
    curl_close($curl);

    $headers = [];
    $curl_response = rtrim($curl_response);
    $data = explode("\n",$curl_response);
    $headers['status'] = trim($data[0]);
    array_shift($data);

    foreach($data as $part){

        //some headers will contain ":" character (Location for example), and the part after ":" will be lost, Thanks to @Emanuele
        $middle = explode(":",$part,2);

        //Supress warning message if $middle[1] does not exist, Thanks to @crayons
        if ( !isset($middle[1]) ) { $middle[1] = null; }

        $headers[trim($middle[0])] = trim($middle[1]);
    }

    return $headers;
}


function verify_api_auth($user_id,$session_id, $platform = 'phone') {
    global $db;
    if (empty($session_id) || empty($user_id)) {
        return false;
    }
    $platform   = PT_Secure($platform);
    $session_id = PT_Secure($session_id);
    $user_id    = PT_Secure($user_id);

    $db->where('session_id', $session_id);
    $db->where('user_id', $user_id);
    $db->where('platform', $platform);
    return ($db->getValue(T_SESSIONS, 'COUNT(*)') == 1);
}

function pt_vrequest_exists(){
    global $db,$pt;
    if (!IS_LOGGED) {
        return false;
    }

    $user    = $pt->user->id;
    return ($db->where("user_id",$user)->getValue(T_VERIF_REQUESTS,"count(*)") > 0);
}


function pt_get_announcments() {
    global $pt, $db;
    // if (IS_LOGGED === false) {
    //     return false;
    // }

    $views_table  = T_ANNOUNCEMENT_VIEWS;
    $table        = T_ANNOUNCEMENTS;

    if (IS_LOGGED) {
        $user         = $pt->user->id;
        $subsql       = "SELECT `announcement_id` FROM `$views_table` WHERE `user_id` = '{$user}'";
        $fetched_data = $db->where(" `active` = '1' AND `id` NOT IN ({$subsql}) ")->orderBy('RAND()')->getOne(T_ANNOUNCEMENTS);
    }
    else{
        if (!empty($_COOKIE['ANNOUNCEMENT']) && $_COOKIE['ANNOUNCEMENT'] == 'yes') {
           return false;
        }
        $fetched_data = $db->where(" `active` = '1'")->orderBy('RAND()')->getOne(T_ANNOUNCEMENTS);
    }
    return $fetched_data;
}


function pt_is_banned($ip_address = false){
    global $pt, $db;
    $table = T_BANNED_IPS;
    try {
        $ip    = $db->where('ip_address',$ip_address,'=')->getValue($table,"count(*)");
        return ($ip > 0);
    } catch (Exception $e) {
        return false;
    }
}

function pt_custom_design($a = false,$code = array()){
    global $pt;
    $theme       = $pt->config->theme;
    $data        = array();
    $custom_code = array(
        "themes/$theme/js/header.js",
        "themes/$theme/js/footer.js",
        "themes/$theme/css/custom.style.css",
    );

    if ($a == 'get') {
        foreach ($custom_code as $key => $filepath) {
            if (is_readable($filepath)) {
                $data[$key] = file_get_contents($filepath);
            }
            else{
                $data[$key] = "/* \n Error found while loading: Permission denied in $filepath \n*/";
            }
        }
    }

    else if($a == 'save' && !empty($code)){
        foreach ($code as $key => $content) {
            $filepath = $custom_code[$key];

            if (is_writable($filepath)) {
                @file_put_contents($custom_code[$key],$content);
            }

            else{
                $data[$key] = "Permission denied: $filepath is not writable";
            }
        }
    }

    return $data;
}

function pt_notify($data = array()){
    global $pt, $db;
    if (empty($data) || !is_array($data)) {
        return false;
    }
    if (!empty($data['recipient_id']) && in_array($data['recipient_id'], $pt->blocked_array)) {
        if (empty($data['admin']) && $data['admin'] != 1) {
            return false;
        }
    }

    $t_notif = T_NOTIFICATIONS;
    $query   = $db->insert($t_notif,$data);
    if ($pt->config->push == 1 && empty($data['admin'])) {
        PT_NotificationWebPushNotifier();
    }
    return $query;
}

function pt_get_notification($args = array()){
    global $pt, $db;
    $options  = array(
        "recipient_id" => 0,
        "type" => null,
    );

    $args         = array_merge($options, $args);
    $recipient_id = $args['recipient_id'];
    $type         = $args['type'];
    $data         = array();
    $t_notif      = T_NOTIFICATIONS;

    $db->where('recipient_id',$recipient_id);
    if ($type == 'new') {
        $data = $db->where('seen',0)->getValue($t_notif,'count(*)');
    }

    else{
        $query      = $db->orderBy('id','DESC')->get($t_notif,20);
        foreach ($query as $notif_data_row) {
            $data[] = ToArray($notif_data_row);
        }
    }

    $db->where('recipient_id',$recipient_id);
    $db->where('time',(time() - 432000));
    $db->where('seen',0,'>');
    $db->delete($t_notif);

    return $data;
}

function ffmpeg_duration($filename = false){
    global $pt;

    $ffmpeg_b = $pt->config->ffmpeg_binary_file;
    $output   = shell_exec("$ffmpeg_b -i {$filename} 2>&1");
    $ptrn     = '/Duration: ([0-9]{2}):([0-9]{2}):([^ ,])+/';
    $time     = 30;
    if (preg_match($ptrn, $output, $matches)) {
        $time = str_replace("Duration: ", "", $matches[0]);
        $time_breakdown = explode(":", $time);
        $time = round(($time_breakdown[0]*60*60) + ($time_breakdown[1]*60) + $time_breakdown[2]);
    }

    return $time;
}

function http_respond($data = array()) {
    if (is_callable('fastcgi_finish_request')) {
        session_write_close();
        fastcgi_finish_request();
        return;
    }

    ignore_user_abort(true);
    ob_start();
    $serverProtocol = filter_input(INPUT_SERVER, 'SERVER_PROTOCOL', FILTER_UNSAFE_RAW);
    header($serverProtocol . ' 200 OK');
    header('Content-Encoding: none');
    header('Content-Length: ' . ob_get_length());
    header('Connection: close');
    ob_end_flush();
    ob_flush();
    flush();
}


function get_ip_address() {
    if (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') !== false) {
            $iplist = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            foreach ($iplist as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP))
                    return $ip;
            }
        } else {
            if (filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED']) && filter_var($_SERVER['HTTP_X_FORWARDED'], FILTER_VALIDATE_IP))
        return $_SERVER['HTTP_X_FORWARDED'];
    if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && filter_var($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'], FILTER_VALIDATE_IP))
        return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_FORWARDED_FOR']) && filter_var($_SERVER['HTTP_FORWARDED_FOR'], FILTER_VALIDATE_IP))
        return $_SERVER['HTTP_FORWARDED_FOR'];
    if (!empty($_SERVER['HTTP_FORWARDED']) && filter_var($_SERVER['HTTP_FORWARDED'], FILTER_VALIDATE_IP))
        return $_SERVER['HTTP_FORWARDED'];
    return $_SERVER['REMOTE_ADDR'];
}

function pt_db_langs() {
    global $pt, $db;
    $data   = array();
    $t_lang = T_LANGS;
    try {
        $query  = $db->rawQuery("DESCRIBE `$t_lang`");
    } catch (Exception $e) {

    }
    foreach ($query as $column) {
        $data[] = $column->Field;
    }

    unset($data[0]);
    unset($data[1]);
    unset($data[2]);

    return $data;
}

function pt_get_langs($lang = 'english') {
    global $pt, $db;
    $data   = array();
    $t_lang = T_LANGS;
    try {
        $query  = $db->rawQuery("SELECT `lang_key`, `$lang` FROM `$t_lang`");
    } catch (Exception $e) {

    }

    foreach ($query as $item) {
        $data[$item->lang_key] = $item->$lang;
    }

    return $data;
}
function getDirContents($dir, &$results = array()) {
    global $db;
    $files = @scandir($dir);
    $forbiddenArray = ['.htaccess', 'index.html', 'step2.png', 'thumbnail.jpg', 'speed.jpg', 'parts.jpg', 'f-avatar.png', 'd-cover.jpg', 'd-avatar.jpg', 'step1.png'];
    if (!empty($files)) {
        foreach ($files as $key => $value) {
            $path = $dir . "/" . $value;
            if (!is_dir($path) && !in_array($value, $forbiddenArray)) {
                $results[] = $path;
            } else if ($value != "." && $value != "..") {
                getDirContents($path, $results);
                if (!is_dir($path) && !in_array($path, $forbiddenArray)) {
                    $results[] = $path;
                }
            }
        }
    }
    return $results;
}

function filterFiles($results, $storage) {
    global $db;
    $fianlToAdd = [];
    foreach ($results as $key => $fileName) {
        $checkIfFileExistsInUpload = $db->where('filename', PT_Secure($fileName))->where('storage', $storage)->getOne(T_UPLOADED_MEDIA);
        
        if (empty($checkIfFileExistsInUpload)) {
            $fianlToAdd[] = $fileName;
        }
    }
    return $fianlToAdd;
}
function PT_Duration($text) {
    $duration_search = '/\[d\](.*?)\[\/d\]/i';

    if (preg_match_all($duration_search, $text, $matches)) {
        foreach ($matches[1] as $match) {
            $time = explode(":", $match);
            $current_time = ($time[0]*60)+$time[1];
            $text = str_replace('[d]' . $match . '[/d]', '<a  class="hash" href="javascript:void(0)" onclick="go_to_duration('.$current_time.')">' . $match . '</a>', $text);
        }
    }
    return $text;
}
function PT_NotificationWebPushNotifier() {
    global $sqlConnect, $pt,$db;

    if (IS_LOGGED == false) {
        return false;
    }
    if ($pt->config->push == 0) {
        return false;
    }
    $user_id   =  PT_Secure($pt->user->id);
    $to_ids    = array();
    $notifications = $db->where('notifier_id',$user_id)->where('seen',0)->where('sent_push',0)->where('type','admin_notification','<>')->orderBy('id','DESC')->get(T_NOTIFICATIONS);
    if (!empty($notifications)) {
        foreach ($notifications as $key => $notify) {
            $notification_id = $notify->id;
            $to_id           = $notify->recipient_id;
            $to_data         = PT_UserData($notify->recipient_id);
            $ids             = '';
            if (!empty($to_data->device_id)) {
                $ids = array($to_data->device_id);
            }
            $send_array = array(
                'send_to' => $ids,
                'notification' => array(
                    'notification_content' => '',
                    'notification_title' => $pt->user->name,
                    'notification_image' => $pt->user->avatar,
                    'notification_data' => array(
                        'user_id' => $user_id
                    )
                )
            );
            $notify->type_text = '';
            $notificationText = $notify->text;
            if (!empty($notify->type)) {
                $notify->type_text  = $pt->notif_data[$notify->type]['text'];
            }
            $send_array['notification']['notification_content']     = $notify->type_text;
            $send_array['notification']['notification_data']['url'] = $notify->url;
            $send_array['notification']['notification_data']['notify_type'] = $notify->type;
            //$send_array['notification']['notification_data']['user_data'] = PT_UserData($user_id);
            $send_array['notification']['notification_data']['video']  = '';
            if (!empty($notify->video_id)) {
                $video_data = PT_GetVideoByID($notify->video_id,0,1,2);
                $types = array('id','user_id','video_id','thumbnail','video_location','youtube','vimeo','daily','facebook','ok','twitch','twitch_type','age_restriction','privacy','demo','title','is_owner','geo_blocking');
                foreach ($video_data as $key => $value) {
                    if (!in_array($key, $types)) {
                        unset($video_data->{$key});
                    }
                }
                $send_array['notification']['notification_data']['video'] = $video_data;
            }
            //$send_array['notification']['notification_data']['notification_info'] = $notify;
            $send       = PT_SendPushNotification($send_array, 'native');

        }
        $query_get_messages_for_push = $db->where('notifier_id',$user_id)->where('sent_push',0)->update(T_NOTIFICATIONS,array('sent_push' => 1));
    }
    return true;
}

function getBrowser() {
      $u_agent = $_SERVER['HTTP_USER_AGENT'];
      $bname = 'Unknown';
      $platform = 'Unknown';
      $version= "";
      $ub= "";
      // First get the platform?
      if (preg_match('/macintosh|mac os x/i', $u_agent)) {
        $platform = 'mac';
      } elseif (preg_match('/windows|win32/i', $u_agent)) {
        $platform = 'windows';
      } elseif (preg_match('/iphone|IPhone/i', $u_agent)) {
        $platform = 'IPhone Web';
      } elseif (preg_match('/android|Android/i', $u_agent)) {
        $platform = 'Android Web';
      } else if (preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $u_agent)) {
        $platform = 'Mobile';
      } else if (preg_match('/linux/i', $u_agent)) {
        $platform = 'linux';
      }
      // Next get the name of the useragent yes seperately and for good reason
      if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)) {
        $bname = 'Internet Explorer';
        $ub = "MSIE";
      } elseif(preg_match('/Firefox/i',$u_agent)) {
        $bname = 'Mozilla Firefox';
        $ub = "Firefox";
      } elseif(preg_match('/Chrome/i',$u_agent)) {
        $bname = 'Google Chrome';
        $ub = "Chrome";
      } elseif(preg_match('/Safari/i',$u_agent)) {
        $bname = 'Apple Safari';
        $ub = "Safari";
      } elseif(preg_match('/Opera/i',$u_agent)) {
        $bname = 'Opera';
        $ub = "Opera";
      } elseif(preg_match('/Netscape/i',$u_agent)) {
        $bname = 'Netscape';
        $ub = "Netscape";
      }
      // finally get the correct version number
      $known = array('Version', $ub, 'other');
      $pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
      if (!preg_match_all($pattern, $u_agent, $matches)) {
        // we have no matching number just continue
      }
      // see how many we have
      $i = count($matches['browser']);
      if ($i != 1) {
        //we will have two since we are not using 'other' argument yet
        //see if version is before or after the name
        if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
          $version= $matches['version'][0];
        } else {
          $version= $matches['version'][1];
        }
      } else {
        $version= $matches['version'][0];
      }
      // check if we have a number
      if ($version==null || $version=="") {$version="?";}
      return array(
          'userAgent' => $u_agent,
          'name'      => $bname,
          'version'   => $version,
          'platform'  => $platform,
          'pattern'    => $pattern,
          'ip_address' => get_ip_address()
      );
}
function FilterStripTags($string='')
{
    return filter_var(strip_tags($string), FILTER_UNSAFE_RAW);
}
function time_to_iso8601_duration($time) {
    $units = array(
        "Y" => 365*24*3600,
        "D" =>     24*3600,
        "H" =>        3600,
        "M" =>          60,
        "S" =>           1,
    );

    $str = "P";
    $istime = false;

    foreach ($units as $unitName => &$unit) {
        $quot  = intval($time / $unit);
        $time -= $quot * $unit;
        $unit  = $quot;
        if ($unit > 0) {
            if (!$istime && in_array($unitName, array("H", "M", "S"))) { // There may be a better way to do this
                $str .= "T";
                $istime = true;
            }
            $str .= strval($unit) . $unitName;
        }
    }

    return $str;
}
function uploadChunk($fileUploadName, $fileUploadFolder = "uploads") {
    // RESPONSE FUNCTION
    global $db, $pt;
    function index($ok=1,$info=""){
        // THROW A 400 ERROR ON FAILURE
        if ($ok==0) { http_response_code(400); }
        die(json_encode(["ok"=>$ok, "info"=>$info]));
    }
    
    // INVALID UPLOAD
    if (empty($_FILES) || $_FILES["video"]['error']) {
        index(0, "Failed to move uploaded file: " . ($_FILES["video"]['error']) ? $_FILES["video"]['error'] : "");
    }
    // THE UPLOAD DESITINATION - CHANGE THIS TO YOUR OWN
    $filePath = $fileUploadFolder;
    if (!file_exists($filePath)) { 
        if (!mkdir($filePath, 0777, true)) {
            index(0, "Failed to create $filePath");
        }
    }
    $fileName = $fileUploadName;
    $filePath = $filePath . DIRECTORY_SEPARATOR . $fileName;
    // DEAL WITH CHUNKS
    $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
    $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
    $out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
    if ($out) {
        $in = fopen($_FILES["video"]['tmp_name'], "rb");
        if ($in) {
            while ($buff = fread($in, 4096)) { fwrite($out, $buff); }
        } else {
            index(0, "Failed to open input stream");
        }
        fclose($in);
        fclose($out);
        unlink($_FILES["video"]['tmp_name']);
    } else {
        index(0, "Failed to open output stream");
    }
    // CHECK IF FILE HAS BEEN UPLOADED
    if (!$chunks || $chunk == $chunks - 1) {
        rename("{$filePath}.part", $filePath);
        $_SESSION['fileSize'] = "";
        unset($_SESSION['fileSize']);
        return true;
    }
}