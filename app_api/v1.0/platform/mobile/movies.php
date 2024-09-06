<?php
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
    $videos_array = array();
    $db->pageLimit = (!empty($_POST['limit']) && is_numeric($_POST['limit']) && $_POST['limit'] > 0 && $_POST['limit'] <= 50 ? PT_Secure($_POST['limit']) : 20);
    $page_number = isset($_POST['page_id']) && is_numeric($_POST['page_id']) && $_POST['page_id'] > 0 ? $_POST['page_id'] : 1;

    $db->where('is_movie', 1);
    if (!empty($_POST['category']) && $_POST['category'] != 'all') {
        $db->where('category_id', PT_Secure($_POST['category']));
    }
    if (!empty($_POST['rating'])) {
        $db->where('rating', PT_Secure($_POST['rating']));
    }
    if (!empty($_POST['release'])) {
        $db->where('movie_release', PT_Secure($_POST['release']));
    }
    if (!empty($_POST['country'])) {
        $db->where('country', PT_Secure($_POST['country']));
    }
    if (!empty($_POST['keyword'])) {
        $db->where('title', '%'.PT_Secure($_POST['keyword']).'%','LIKE');
    }
    $videos = $db->where('user_id',$pt->blocked_array , 'NOT IN')->orderBy('id', 'DESC')->objectbuilder()->paginate(T_VIDEOS, $page_number);
    if (!empty($videos)) {
        foreach ($videos as $key => $video) {
            $video = PT_GetVideoByID($video, 0, 1, 0);
            $videos_array[] = $video;
        }
    }
    $response_data     = array(
        'api_status'   => '200',
        'api_version'  => $api_version,
        'totalPages' => $db->totalPages,
        'channels' => $videos_array
    );
}