<?php
if (IS_LOGGED == false) {
    header("Location: " . PT_Link('login'));
    exit();
}
$user_id               = $user->id;
$pt->is_admin          = PT_IsAdmin();
$pt->is_settings_admin = false;

if (isset($_GET['user']) && !empty($_GET['user']) && ($pt->is_admin === true)) {
    if (empty($db->where('username', PT_Secure($_GET['user']))->getValue(T_USERS, 'count(*)'))) {
        header("Location: " . PT_Link(''));
        exit();
    }
    $user_id               = $db->where('username', PT_Secure($_GET['user']))->getValue(T_USERS, 'id');
    $pt->is_settings_admin = true;
}

$pt->settings     = PT_UserData($user_id);
$pt->setting_page = 'general';
$pages_array      = array(
    'general',
    'profile',
    'password',
    'privacy',
    'change',
    'social',
    'avatar',
    'email',
    'delete',
    'monetization',
    'withdrawals',
    'verification',
    'balance',
    'two_factor',
    'blocked_users',
    'manage_sessions',
    'points',
    'my_info',
    'invitation_links',
);

if ($pt->settings->id == $user->id) {
    $pages_array = array(
        'general',
        'profile',
        'password',
        'privacy',
        'change',
        'social',
        'avatar',
        'email',
        'delete',
        'monetization',
        'withdrawals',
        'verification',
        'balance',
        'two_factor',
        'blocked_users',
        'manage_sessions',
        'points',
        'my_info',
        'invitation_links',
    );
}
if ($pt->config->affiliate_system == 1) {
    $pages_array[] = 'my_affiliates';
}
$pt->page_url_ = $pt->config->site_url.'/settings';
if (!empty($_GET['page']) && $_GET['page'] == 'two_factor' && ($pt->config->two_factor_setting != 'on' && $pt->config->google_authenticator != 'on' && $pt->config->authy_settings != 'on')) {
    header("Location: " . PT_Link(''));
    exit();
}
if (!empty($_GET['page'])) {
    if (in_array($_GET['page'], $pages_array)) {
        if ($_GET['page'] != 'balance') {
            $pt->setting_page = $_GET['page'];
            $pt->page_url_ = $pt->config->site_url.'/settings/'.$pt->setting_page;
        }
        else{
            if (($pt->config->usr_v_mon == 'off' && $pt->config->sell_videos_system == 'off')) {
                $pt->setting_page = 'general';
                $pt->page_url_ = $pt->config->site_url.'/settings/'.$pt->setting_page;
            }
            else{
                $pt->setting_page = $_GET['page'];
                $pt->page_url_ = $pt->config->site_url.'/settings/'.$pt->setting_page;
            }
        }
    }
}

$pt->user_setting = '';
if (!empty($_GET['user'])) {
    $pt->user_setting = 'user=' . $_GET['user'] . '&';
    $pt->page_url_ = $pt->config->site_url.'/settings/'.$pt->setting_page.'/'.$_GET['user'];
}
$countries = '';
foreach ($countries_name as $key => $value) {
    $selected = ($key == $pt->settings->country_id) ? 'selected' : '';
    $countries .= '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
}
if ($pt->config->invite_links_system == 'on' && !canUseFeature($pt->settings->id,'who_can_invite_links')) {
    $pt->config->invite_links_system = 'off';
    if ($pt->setting_page == 'invitation_links') {
        header("Location: " . PT_Link(''));
        exit();
    }
}
if ($pt->config->affiliate_system == 1 && !canUseFeature($pt->settings->id,'who_can_affiliate')) {
    $pt->config->affiliate_system = 0;
    if ($pt->setting_page == 'my_affiliates') {
        header("Location: " . PT_Link(''));
        exit();
    }
}


// Get user custom Fields
if ($pt->setting_page == 'general') {
    $db->where('placement','general');
} 

else if ($pt->setting_page == 'profile') {
    $db->where('placement',array('profile','social'),'IN');
}

$pt->profile_fields = null;
$pt->profile_fields = $db->where('active','1')->get(T_FIELDS);
$pt->user->fields   = $db->where('user_id',$user_id)->getOne(T_USR_PROF_FIELDS);
$pt->user->fields   = (is_object($pt->user->fields)) ? get_object_vars($pt->user->fields) : array();
$custom_fields      = "";


foreach ($pt->profile_fields as $field_data) {
    $field_data->fid  = 'fid_' . $field_data->id;
    $field_data->name = preg_replace_callback("/{{LANG (.*?)}}/", function($m) use ($pt) {
        return (isset($pt->lang->$m[1])) ? $pt->lang->$m[1] : '';
    }, $field_data->name);

    $field_data->description = preg_replace_callback("/{{LANG (.*?)}}/", function($m) use ($pt) {
        return (isset($pt->lang->$m[1])) ? $pt->lang->$m[1] : '';
    }, $field_data->description);

    if ($field_data->type == 'select') {
        $fid       = '';
        $pt->field = $field_data;
        if (!empty($pt->user->fields[$field_data->fid])) {
            $fid   = $pt->user->fields[$field_data->fid];
        } 

        $pt->fid   = $fid;
        $custom_fields .= PT_LoadPage('settings/custom-options',array(
            "FID"  => $fid,
            "NAME" => $field_data->name,
            "DESC" => $field_data->description,
        ));
    }

    else if ($field_data->type == 'textbox' || $field_data->type == 'textarea') {
        $fid       = '';
        $pt->field = $field_data;
        if (!empty($pt->user->fields[$field_data->fid])) {
            $fid   = $pt->user->fields[$field_data->fid];
        } 

        $pt->fid   = $fid;
        $custom_fields .= PT_LoadPage('settings/custom-inputs',array(
            "ID"   =>  $field_data->id,
            "FID"  => $fid,
            "NAME" => $field_data->name,
            "DESC" => $field_data->description,
        ));
    }


}

$withdrawal_history = "";
if ($pt->setting_page == 'withdrawals') {
    $user_withdrawals  = $db->where('user_id',$pt->user->id)->get(T_WITHDRAWAL_REQUESTS);  
    foreach ($user_withdrawals as $withdrawal) {
        $pt->withdrawal_stat = $withdrawal->status;
        $withdrawal_history .= PT_LoadPage("settings/includes/withdrawals-list",array(
            'W_ID' => $withdrawal->id,
            'W_REQUESTED' => date('Y-F-d',$withdrawal->requested),
            'W_AMOUNT' => number_format($withdrawal->amount, 2),
            'W_CURRENCY' => $withdrawal->currency,
        ));
    }
}


$blocked_users = "";
if ($pt->setting_page == 'blocked_users') {
    $b_users = GetBlockedUsers();
    foreach ($b_users as $user) {
        $blocked_users .= PT_LoadPage("settings/includes/blocked_users_list",array(
            'USER_DATA'    => $user,
            'BLOCK_BUTTON'  => PT_GetBlockButton($user->id,'false')
        ));
    }
    if (empty($blocked_users)) {
        $blocked_users = '<p class="empty_state"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-users"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>'.$lang->no_users_found.'</p>';
    }
}

$sessions = "";
if ($pt->setting_page == 'manage_sessions') {
    $user_sessions = PT_GetUserSessions($pt->settings->id);
    //$user_sessions = $db->where('user_id',$pt->settings->id)->get(T_SESSIONS);
    if (!empty($user_sessions)) {
        foreach ($user_sessions as $key => $pt->session) {
            $sessions .= PT_LoadPage("settings/includes/sessions");
        }
    }
    // $b_users = GetBlockedUsers();
    // foreach ($b_users as $user) {
    //     $blocked_users .= PT_LoadPage("settings/includes/blocked_users_list",array(
    //         'USER_DATA'    => $user,
    //         'BLOCK_BUTTON'  => PT_GetBlockButton($user->id,'false')
    //     ));
    // }
    // if (empty($blocked_users)) {
    //     $blocked_users = '<p class="empty_state"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-users"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>'.$lang->no_users_found.'</p>';
    // }
}
$refs_html = "";
if ($pt->setting_page == 'my_affiliates') {
    $refs = GetReferrers();
    foreach ($refs as $key => $pt->ref) {
        $refs_html .= PT_LoadPage('settings/includes/refs');
    }
}

$pt->is_mon = 0;
if ($pt->setting_page == 'monetization') {
    $pt->is_mon = ($db->where('user_id',$pt->settings->id)->getValue(T_MON_REQUESTS,'count(*)'));
    if ($pt->is_admin && ($pt->user->monetization != 1 || $pt->is_mon != 0)) {
        $db->where('id',$pt->user->id)->update(T_USERS,['monetization' => 1]);
        if ($pt->is_mon != 0) {
            $request_data  = $db->where('user_id',$pt->user->id)->getOne(T_MON_REQUESTS);
            if (!empty($request_data)) {
                if (file_exists($request_data->personal_photo)) {
                    @unlink(trim($request_data->personal_photo));
                }
                else if($pt->remoteStorage){
                    @PT_DeleteFromToS3($request_data->personal_photo);
                }

                if (file_exists($request_data->id_photo)) {
                    @unlink(trim($request_data->id_photo));
                }
                else if($pt->remoteStorage){
                    @PT_DeleteFromToS3($request_data->id_photo);
                }
                $db->where('id',$request_data->id)->delete(T_MON_REQUESTS);
            }
        }
        header("Location: " . $pt->page_url_);
        exit();
    }
}
$pt->available_links = 0;
$pt->generated_links = 0;
$pt->used_links = 0;
$trans_html = '';
$pt->show_trans = false;
if ($pt->setting_page == 'invitation_links') {
    $pt->available_links = GetAvailableLinks($pt->settings->id);
    if ($pt->config->user_links_limit > 0) {
        $pt->generated_links = $pt->config->user_links_limit - $pt->available_links;
    }
    else{
        $pt->generated_links = GetGeneratedLinks($pt->settings->id);
    }
    $pt->used_links = GetUsedLinks($pt->settings->id);

    $trans = GetMyInvitaionCodes($pt->settings->id);
    if (count($trans) > 0){
        $pt->show_trans = true;
        foreach ($trans as $key => $transaction){
            $link = '';
            if (!empty($transaction['user_name'])) {
                $link = '<a href="'.$transaction['user_url'].'">'.$transaction['user_name'].'</a>';
            }
            $trans_html .= '<tr data-ad-id="'.$transaction['id'].'"><td><button type="button" class="btn btn-sm btn-default copy-invitation-url" data-link="'.$pt->config->site_url.'/register?invite='.$transaction['code'].'">'.$lang->copy.'</button></td><td>'.$link.'</td><td>'.TranslateDate(date($pt->config->date_style, $transaction['time'])).'</td></tr>';
        }
    }
}
$googleQR = '';
$authyQR = '';
$pt->methodCount = 0;
$pt->factor_used = $lang->email;
if ($pt->setting_page == 'two_factor') {
    if ($pt->settings->two_factor_method == 'google') {
        $pt->factor_used = $lang->google_authenticator;
    }
    if ($pt->settings->two_factor_method == 'authy') {
        $pt->factor_used = $lang->authy_app;
    }
    if ($pt->config->two_factor_setting == 'on') {
        $pt->methodCount += 1;
    }
    if ($pt->config->google_authenticator == 'on') {
        $pt->methodCount += 1;
        require_once 'assets/libs/google_auth/vendor/autoload.php';
        if (empty($pt->settings->google_secret)) {
            $google2fa = new \PragmaRX\Google2FA\Google2FA();
            $pt->settings->google_secret = $google2fa->generateSecretKey();
            $db->where('id',$pt->settings->id)->update(T_USERS,['google_secret' => $pt->settings->google_secret]);
        }

        $google2fa = new \PragmaRX\Google2FA\Google2FA();
          
        $text = $google2fa->getQRCodeUrl(
         $pt->config->title,
         $pt->settings->username,
         $pt->settings->google_secret
        );
            
        $googleQR = 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl='.$text;
    }

    if ($pt->config->authy_settings == 'on') {
        $pt->methodCount += 1;
        $pt->countries_codes = [
            '44' => 'UK (+44)',
            '1' => 'USA (+1)',
            '213' => 'Algeria (+213)',
            '376' => 'Andorra (+376)',
            '244' => 'Angola (+244)',
            '1264' => 'Anguilla (+1264)',
            '1268' => 'Antigua & Barbuda (+1268)',
            '54' => 'Argentina (+54)',
            '374' => 'Armenia (+374)',
            '297' => 'Aruba (+297)',
            '61' => 'Australia (+61)',
            '43' => 'Austria (+43)',
            '994' => 'Azerbaijan (+994)',
            '1242' => 'Bahamas (+1242)',
            '973' => 'Bahrain (+973)',
            '880' => 'Bangladesh (+880)',
            '1246' => 'Barbados (+1246)',
            '375' => 'Belarus (+375)',
            '32' => 'Belgium (+32)',
            '501' => 'Belize (+501)',
            '229' => 'Benin (+229)',
            '1441' => 'Bermuda (+1441)',
            '975' => 'Bhutan (+975)',
            '591' => 'Bolivia (+591)',
            '387' => 'Bosnia Herzegovina (+387)',
            '267' => 'Botswana (+267)',
            '55' => 'Brazil (+55)',
            '673' => 'Brunei (+673)',
            '359' => 'Bulgaria (+359)',
            '226' => 'Burkina Faso (+226)',
            '257' => 'Burundi (+257)',
            '855' => 'Cambodia (+855)',
            '237' => 'Cameroon (+237)',
            '1' => 'Canada (+1)',
            '238' => 'Cape Verde Islands (+238)',
            '1345' => 'Cayman Islands (+1345)',
            '236' => 'Central African Republic (+236)',
            '56' => 'Chile (+56)',
            '86' => 'China (+86)',
            '57' => 'Colombia (+57)',
            '269' => 'Comoros (+269)',
            '242' => 'Congo (+242)',
            '682' => 'Cook Islands (+682)',
            '506' => 'Costa Rica (+506)',
            '385' => 'Croatia (+385)',
            '53' => 'Cuba (+53)',
            '90392' => 'Cyprus North (+90392)',
            '357' => 'Cyprus South (+357)',
            '42' => 'Czech Republic (+42)',
            '45' => 'Denmark (+45)',
            '253' => 'Djibouti (+253)',
            '1809' => 'Dominica (+1809)',
            '1809' => 'Dominican Republic (+1809)',
            '593' => 'Ecuador (+593)',
            '20' => 'Egypt (+20)',
            '503' => 'El Salvador (+503)',
            '240' => 'Equatorial Guinea (+240)',
            '291' => 'Eritrea (+291)',
            '372' => 'Estonia (+372)',
            '251' => 'Ethiopia (+251)',
            '500' => 'Falkland Islands (+500)',
            '298' => 'Faroe Islands (+298)',
            '679' => 'Fiji (+679)',
            '358' => 'Finland (+358)',
            '33' => 'France (+33)',
            '594' => 'French Guiana (+594)',
            '689' => 'French Polynesia (+689)',
            '241' => 'Gabon (+241)',
            '220' => 'Gambia (+220)',
            '7880' => 'Georgia (+7880)',
            '49' => 'Germany (+49)',
            '233' => 'Ghana (+233)',
            '350' => 'Gibraltar (+350)',
            '30' => 'Greece (+30)',
            '299' => 'Greenland (+299)',
            '1473' => 'Grenada (+1473)',
            '590' => 'Guadeloupe (+590)',
            '671' => 'Guam (+671)',
            '502' => 'Guatemala (+502)',
            '224' => 'Guinea (+224)',
            '245' => 'Guinea - Bissau (+245)',
            '592' => 'Guyana (+592)',
            '509' => 'Haiti (+509)',
            '504' => 'Honduras (+504)',
            '852' => 'Hong Kong (+852)',
            '36' => 'Hungary (+36)',
            '354' => 'Iceland (+354)',
            '91' => 'India (+91)',
            '62' => 'Indonesia (+62)',
            '98' => 'Iran (+98)',
            '964' => 'Iraq (+964)',
            '353' => 'Ireland (+353)',
            '972' => 'Israel (+972)',
            '39' => 'Italy (+39)',
            '1876' => 'Jamaica (+1876)',
            '81' => 'Japan (+81)',
            '962' => 'Jordan (+962)',
            '7' => 'Kazakhstan (+7)',
            '254' => 'Kenya (+254)',
            '686' => 'Kiribati (+686)',
            '850' => 'Korea North (+850)',
            '82' => 'Korea South (+82)',
            '965' => 'Kuwait (+965)',
            '996' => 'Kyrgyzstan (+996)',
            '856' => 'Laos (+856)',
            '371' => 'Latvia (+371)',
            '961' => 'Lebanon (+961)',
            '266' => 'Lesotho (+266)',
            '231' => 'Liberia (+231)',
            '218' => 'Libya (+218)',
            '417' => 'Liechtenstein (+417)',
            '370' => 'Lithuania (+370)',
            '352' => 'Luxembourg (+352)',
            '853' => 'Macao (+853)',
            '389' => 'Macedonia (+389)',
            '261' => 'Madagascar (+261)',
            '265' => 'Malawi (+265)',
            '60' => 'Malaysia (+60)',
            '960' => 'Maldives (+960)',
            '223' => 'Mali (+223)',
            '356' => 'Malta (+356)',
            '692' => 'Marshall Islands (+692)',
            '596' => 'Martinique (+596)',
            '222' => 'Mauritania (+222)',
            '269' => 'Mayotte (+269)',
            '52' => 'Mexico (+52)',
            '691' => 'Micronesia (+691)',
            '373' => 'Moldova (+373)',
            '377' => 'Monaco (+377)',
            '976' => 'Mongolia (+976)',
            '1664' => 'Montserrat (+1664)',
            '212' => 'Morocco (+212)',
            '258' => 'Mozambique (+258)',
            '95' => 'Myanmar (+95)',
            '264' => 'Namibia (+264)',
            '674' => 'Nauru (+674)',
            '977' => 'Nepal (+977)',
            '31' => 'Netherlands (+31)',
            '687' => 'New Caledonia (+687)',
            '64' => 'New Zealand (+64)',
            '505' => 'Nicaragua (+505)',
            '227' => 'Niger (+227)',
            '234' => 'Nigeria (+234)',
            '683' => 'Niue (+683)',
            '672' => 'Norfolk Islands (+672)',
            '670' => 'Northern Marianas (+670)',
            '47' => 'Norway (+47)',
            '968' => 'Oman (+968)',
            '680' => 'Palau (+680)',
            '507' => 'Panama (+507)',
            '675' => 'Papua New Guinea (+675)',
            '595' => 'Paraguay (+595)',
            '51' => 'Peru (+51)',
            '63' => 'Philippines (+63)',
            '48' => 'Poland (+48)',
            '351' => 'Portugal (+351)',
            '1787' => 'Puerto Rico (+1787)',
            '974' => 'Qatar (+974)',
            '262' => 'Reunion (+262)',
            '40' => 'Romania (+40)',
            '7' => 'Russia (+7)',
            '250' => 'Rwanda (+250)',
            '378' => 'San Marino (+378)',
            '239' => 'Sao Tome & Principe (+239)',
            '966' => 'Saudi Arabia (+966)',
            '221' => 'Senegal (+221)',
            '381' => 'Serbia (+381)',
            '248' => 'Seychelles (+248)',
            '232' => 'Sierra Leone (+232)',
            '65' => 'Singapore (+65)',
            '421' => 'Slovak Republic (+421)',
            '386' => 'Slovenia (+386)',
            '677' => 'Solomon Islands (+677)',
            '252' => 'Somalia (+252)',
            '27' => 'South Africa (+27)',
            '34' => 'Spain (+34)',
            '94' => 'Sri Lanka (+94)',
            '290' => 'St. Helena (+290)',
            '1869' => 'St. Kitts (+1869)',
            '1758' => 'St. Lucia (+1758)',
            '249' => 'Sudan (+249)',
            '597' => 'Suriname (+597)',
            '268' => 'Swaziland (+268)',
            '46' => 'Sweden (+46)',
            '41' => 'Switzerland (+41)',
            '963' => 'Syria (+963)',
            '886' => 'Taiwan (+886)',
            '7' => 'Tajikstan (+7)',
            '66' => 'Thailand (+66)',
            '228' => 'Togo (+228)',
            '676' => 'Tonga (+676)',
            '1868' => 'Trinidad & Tobago (+1868)',
            '216' => 'Tunisia (+216)',
            '90' => 'Turkey (+90)',
            '7' => 'Turkmenistan (+7)',
            '993' => 'Turkmenistan (+993)',
            '1649' => 'Turks & Caicos Islands (+1649)',
            '688' => 'Tuvalu (+688)',
            '256' => 'Uganda (+256)',
            '380' => 'Ukraine (+380)',
            '971' => 'United Arab Emirates (+971)',
            '598' => 'Uruguay (+598)',
            '7' => 'Uzbekistan (+7)',
            '678' => 'Vanuatu (+678)',
            '379' => 'Vatican City (+379)',
            '58' => 'Venezuela (+58)',
            '84' => 'Vietnam (+84)',
            '84' => 'Virgin Islands - British (+1284)',
            '84' => 'Virgin Islands - US (+1340)',
            '681' => 'Wallis & Futuna (+681)',
            '969' => 'Yemen (North)(+969)',
            '967' => 'Yemen (South)(+967)',
            '260' => 'Zambia (+260)',
            '263' => 'Zimbabwe (+263)',
        ];
        
    }
        
        
}

$pt->page        = 'settings';
$pt->title       = $lang->settings . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword     = $pt->config->keyword;
$pt->content     = PT_LoadPage("settings/content", array(
    'SETTINGSPAGE' => PT_LoadPage("settings/$pt->setting_page", array(
        'USER_DATA' => $pt->settings,
        'COUNTRIES_LAYOUT' => $countries,
        'CUSTOM_FIELDS' => $custom_fields,
        'WITHDRAWAL_HISTORY_LIST' => $withdrawal_history,
        'CUSTOM_DATA' => ((!empty($custom_fields)) ? "1" : "0"),
        'BLOCKED_USERS' => $blocked_users,
        'available_links' => $pt->available_links,
        'generated_links' => $pt->generated_links,
        'used_links' => $pt->used_links,
        'trans_html' => $trans_html,
        'SESSIONS' => $sessions,
        'googleQR' => $googleQR,
        'refs_html' => $refs_html,
        'ADMIN_LAYOUT' => PT_LoadPage('settings/admin', array(
            'USER_DATA' => $pt->settings
        ))
    ))
));
