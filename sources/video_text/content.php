<?php

if (IS_LOGGED == false) {
    header("Location: " . PT_Link('login'));
    exit();
}
if (empty($_GET['id'])) {
    header("Location: " . PT_Link(''));
    exit();
}
if ($pt->config->video_text_system != 'on') {
    header("Location: " . PT_Link(''));
    exit();
}
$id    = PT_Secure($_GET['id']);
$video = $db->where('id', $id)->getOne(T_VIDEOS);
if (empty($video)) {
    header("Location: " . PT_Link(''));
    exit();
}
if (!empty($video->facebook) || !empty($video->vimeo) || !empty($video->daily) || !empty($video->youtube) || !empty($video->twitch) || !empty($video->ok)) {
    header("Location: " . PT_Link(''));
    exit();
}
if (!PT_IsAdmin()) {
    if (empty($db->where('id', $id)->where('user_id', $user->id)->getValue(T_VIDEOS, 'count(*)'))) {
        header("Location: " . PT_Link(''));
        exit();
    }
}
$cards_list = '';
$pt->cards = $db->where('video_id',$id)->get(T_CARDS);
if (!empty($pt->cards)) {
    foreach ($pt->cards as $key => $pt->card) {
        $cards_list     .= PT_LoadPage('video_text/list', array('ID' => $pt->card->id,
                                                                'TYPE' => $pt->card->type));
    }
}

$pt->page_url_ = $pt->config->site_url.'/video_text/'.$id;
$video           = PT_GetVideoByID($video, 0, 0, 0);
$pt->video       = $video;
$pt->page        = 'video-text';
$pt->title       = $lang->video_text . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword     = $pt->config->keyword;
$pt->content     = PT_LoadPage('video_text/content', array(
    'ID' => $video->id,
    'USER_DATA' => $video->owner,
    'THUMBNAIL' => $video->thumbnail,
    'URL' => $video->url,
    'TITLE' => $video->title,
    'DESC' => br2nl($video->edit_description),
    'DESC_2' => $video->markup_description,
    'VIEWS' => $video->views,
    'TIME' => $video->time_ago,
    'TAGS' => $video->tags,
    'CARDS_LIST' => $cards_list,

));