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
if (!IS_LOGGED) {

    $response_data    = array(
        'api_status'  => '400',
        'api_version' => $api_version,
        'errors' => array(
            'error_id' => '1',
            'error_text' => 'Not logged in'
        )
    );
}
else{

    $types = array('views','subscribers','most_active');
    $type = 'views';

    if (!empty($_POST['type']) && in_array($_POST['type'], $types)) {
        $type = $_POST['type'];
    }
    $limit = (!empty($_POST['limit']) && is_numeric($_POST['limit']) && $_POST['limit'] > 0 && $_POST['limit'] <= 50 ? PT_Secure($_POST['limit']) : 20);

    $sort_types = array('today','this_week','this_month','this_year','all_time');
    $sort_type = 'all_time';
    $pt->cat_type = 'all_time';

    if (!empty($_POST['sort_type']) && in_array($_POST['sort_type'], $sort_types)) {
        $sort_type = $_POST['sort_type'];
    }

    if ($sort_type == 'today') {
        $start = strtotime(date('M')." ".date('d').", ".date('Y')." 12:00am");
        $end = strtotime(date('M')." ".date('d').", ".date('Y')." 11:59pm");
        $pt->cat_type = 'today';
    }
    elseif ($sort_type == 'this_week') {
        
        $time = strtotime(date('l').", ".date('M')." ".date('d').", ".date('Y'));
        if (date('l') == 'Saturday') {
            $start = strtotime(date('M')." ".date('d').", ".date('Y')." 12:00am");
        }
        else{
            $start = strtotime('last saturday, 12:00am', $time);
        }

        if (date('l') == 'Friday') {
            $end = strtotime(date('M')." ".date('d').", ".date('Y')." 11:59pm");
        }
        else{
            $end = strtotime('next Friday, 11:59pm', $time);
        }
        $pt->cat_type = 'this_week';
    }
    elseif ($sort_type == 'this_month') {
        $start = strtotime("1 ".date('M')." ".date('Y')." 12:00am");
        $end = strtotime(cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'))." ".date('M')." ".date('Y')." 11:59pm");
        $pt->cat_type = 'this_month';
    }
    elseif ($sort_type == 'this_year') {
        $start = strtotime("1 January ".date('Y')." 12:00am");
        $end = strtotime("31 December ".date('Y')." 11:59pm");
        $pt->cat_type = 'this_year';
    }

    if ($type == 'views') {
        if (!empty($_POST['channels_ids']) && !empty($_POST['last_count'])) {
            $ids = PT_Secure($_POST['channels_ids']);
            $last_count = PT_Secure($_POST['last_count']);
            if ($sort_type == 'all_time') {
                $videos = $db->rawQuery('SELECT user_id, SUM(views) AS count FROM '.T_VIDEOS.' WHERE user_id NOT IN ('.$ids.') AND user_id NOT IN ('.implode(",", $pt->blocked_array).') GROUP BY user_id HAVING count <= '.$last_count.' ORDER BY count DESC LIMIT '.$limit);
            }
            else{
                
                $videos = $db->rawQuery('SELECT u.user_id AS user_id , v.video_id, COUNT(*) AS count FROM '.T_VIEWS.' v ,'.T_VIDEOS.' u WHERE v.time >= '.$start.' AND v.time <= '.$end.' AND u.id = v.video_id AND  u.user_id NOT IN ('.$ids.') AND u.user_id NOT IN ('.implode(",", $pt->blocked_array).') GROUP BY u.user_id HAVING count <= '.$last_count.' ORDER BY count DESC LIMIT '.$limit);
            }
        }
        else{
            if ($sort_type == 'all_time') {
                $videos = $db->rawQuery('SELECT user_id, SUM(views) AS count FROM '.T_VIDEOS.' WHERE user_id NOT IN ('.implode(",", $pt->blocked_array).') GROUP BY user_id ORDER BY count DESC LIMIT '.$limit);
            }
            else{
                $videos = $db->rawQuery('SELECT u.user_id AS user_id , v.video_id, COUNT(*) AS count FROM '.T_VIEWS.' v ,'.T_VIDEOS.' u WHERE v.time >= '.$start.' AND v.time <= '.$end.' AND u.id = v.video_id AND u.user_id NOT IN ('.implode(",", $pt->blocked_array).') GROUP BY u.user_id ORDER BY count DESC LIMIT '.$limit);
            }
        }
    }
    elseif ($type == 'subscribers') {
        if (!empty($_POST['channels_ids']) && !empty($_POST['last_count'])) {
            $ids = PT_Secure($_POST['channels_ids']);
            $last_count = PT_Secure($_POST['last_count']);
            if ($sort_type == 'all_time') {
                $videos = $db->rawQuery('SELECT user_id, COUNT(*) AS count FROM '.T_SUBSCRIPTIONS.' WHERE user_id NOT IN ('.$ids.') AND user_id NOT IN ('.implode(",", $pt->blocked_array).') GROUP BY user_id HAVING count <= '.$last_count.' ORDER BY count DESC LIMIT '.$limit);
            }
            else{
                $videos = $db->rawQuery('SELECT user_id, COUNT(*) AS count FROM '.T_SUBSCRIPTIONS.' WHERE time >= '.$start.' AND time <= '.$end.' AND user_id NOT IN ('.$ids.') AND user_id NOT IN ('.implode(",", $pt->blocked_array).')  GROUP BY user_id HAVING count <= '.$last_count.' ORDER BY count DESC LIMIT '.$limit);
            }
        }
        else{
            if ($sort_type == 'all_time') {
                $videos = $db->rawQuery('SELECT user_id, COUNT(*) AS count FROM '.T_SUBSCRIPTIONS.' WHERE user_id NOT IN ('.implode(",", $pt->blocked_array).') GROUP BY user_id ORDER BY count DESC LIMIT '.$limit);
            }
            else{
                $videos = $db->rawQuery('SELECT user_id, COUNT(*) AS count FROM '.T_SUBSCRIPTIONS.' WHERE time >= '.$start.' AND time <= '.$end.' AND user_id NOT IN ('.implode(",", $pt->blocked_array).')  GROUP BY user_id ORDER BY count DESC LIMIT '.$limit);
            }
        }
    }
    elseif ($type == 'most_active') {
        $time = strtotime(date('l').", ".date('M')." ".date('d').", ".date('Y'));

        if (date('l') == 'Friday') {
            $week_end = strtotime(date('M')." ".date('d').", ".date('Y')." 11:59pm");
        }
        else{
            $week_end = strtotime('next Friday, 11:59pm', $time);
        }
        $db->where('active_expire', time(),'<=')->update(T_USERS,array('active_expire' => $week_end,
                                                                       'active_time' => 0));
        if (!empty($_POST['channels_ids']) && !empty($_POST['last_count'])) {
            $ids = PT_Secure($_POST['channels_ids']);
            $last_count = PT_Secure($_POST['last_count']);
            $videos = $db->rawQuery('SELECT * FROM '.T_USERS.' WHERE id NOT IN ('.$ids.') AND active_time <> 0  AND active_time <= '.$last_count.' AND id NOT IN ('.implode(",", $pt->blocked_array).') ORDER BY active_time DESC LIMIT '.$limit);
        }
        else{
            $videos = $db->rawQuery('SELECT * FROM '.T_USERS.' WHERE active_time <> 0 AND id NOT IN ('.implode(",", $pt->blocked_array).') ORDER BY active_time DESC LIMIT '.$limit);
        }
    }

    $channels_array = array();
    if (!empty($videos)) {
        foreach ($videos as $key => $value) {
            $channels = array();
            if ($type == 'views') {
                $views_count = number_format($value->count);
                $views_ = $value->count;
                $subscribers = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_SUBSCRIPTIONS.' WHERE user_id = '.$value->user_id.' GROUP BY user_id LIMIT 1');
                $subscribers_count = 0;
                if (isset($subscribers[0])) {
                    $subscribers_count = ($subscribers[0]->count > 0) ? number_format($subscribers[0]->count) : 0;
                }
                $user = PT_UserData($value->user_id);
                $user = array_intersect_key(ToArray($user), array_flip($user_public_data));
            }
            elseif ($type == 'subscribers') {
                $subscribers_count = number_format($value->count);
                $views_ = $value->count;
                $views = $db->rawQuery('SELECT SUM(views) AS count FROM '.T_VIDEOS.' WHERE user_id = '.$value->user_id.' GROUP BY user_id LIMIT 1');
                $views_count = 0;
                if (isset($views[0])) {
                    $views_count = ($views[0]->count > 0) ? number_format($views[0]->count) : 0;
                }
                $user = PT_UserData($value->user_id);
                $user = array_intersect_key(ToArray($user), array_flip($user_public_data));
            }
            elseif ($type == 'most_active') {
                $subscribers = $db->rawQuery('SELECT COUNT(*) AS count FROM '.T_SUBSCRIPTIONS.' WHERE user_id = '.$value->id.' GROUP BY user_id LIMIT 1');
                $subscribers_count = 0;
                if (isset($subscribers[0])) {
                    $subscribers_count = ($subscribers[0]->count > 0) ? number_format($subscribers[0]->count) : 0;
                }
                $views = $db->rawQuery('SELECT SUM(views) AS count FROM '.T_VIDEOS.' WHERE user_id = '.$value->id.' GROUP BY user_id LIMIT 1');
                $views_count = 0;
                if (isset($views[0])) {
                    $views_count = ($views[0]->count > 0) ? number_format($views[0]->count) : 0;
                }
                $views_ = $value->active_time;
                $user = PT_UserData($value->id);
                $user = array_intersect_key(ToArray($user), array_flip($user_public_data));
            }

            
            if (!empty($user)) {
                $channels['is_subscribed_to_channel'] = $db->where('user_id', $user['id'])->where('subscriber_id', $pt->user->id)->getValue(T_SUBSCRIPTIONS, "count(*)") != 0 ? 1 : 0;
                $channels['user_data'] = $user;
                $channels['views'] = $views_count;
                $channels['count'] = $views_;
                $channels['subscribers_count'] = $subscribers_count;
                $channels['active_time'] = (!empty($user['active_time']) && $user['active_time'] > 0 ? secondsToTime($user['active_time']) : "0 sec");
                $channels_array[] = $channels;
            }
        }
    }
    $response_data     = array(
        'api_status'   => '200',
        'api_version'  => $api_version,
        'channels' => $channels_array
    );
}