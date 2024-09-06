<?php

if (IS_LOGGED == false || $pt->config->post_system != 'on' || !$pt->config->can_use_post) {
    header("Location: " . PT_Link('login'));
    exit();
}
if (empty($_GET['id'])) {
    header("Location: " . PT_Link('login'));
    exit();
}
$_GET['id'] = strip_tags($_GET['id']);
$id    = PT_Secure($_GET['id']);
$post = $db->where('id',$id)->getOne(T_ACTIVITES);
if (empty($post) || ($post->user_id != $pt->user->id && !PT_IsAdmin())) {
    header("Location: " . PT_Link(''));
    exit();
}



$pt->page_url_ = $pt->config->site_url.'/edit_activity/'.$id;
$pt->post       = $post;
$pt->page        = 'edit_activity';
$pt->title       = $lang->edit_activity . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword     = $pt->config->keyword;
$pt->content     = PT_LoadPage('edit_activity/content',array(
    'ID' => $post->id,
    'TITLE' => $post->text,
    'IMAGE' => PT_GetMedia($post->image),
    'TIME' => date('F/m/Y h:i',$post->time),
));