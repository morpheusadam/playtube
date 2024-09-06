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
	$posts = $db->where('active', '1')->orderBy('views', 'DESC')->get(T_POSTS, 7);

	foreach ($posts as $key => $post) {
		$post->orginal_text =  $post->text;
		$post->text =  strip_tags(htmlspecialchars_decode($post->text));
		$post->views = number_format($post->views);
		$post->image = PT_GetMedia($post->image);
		$post->url = PT_Link('articles/read/' . PT_URLSlug($post->title,$post->id));
		$post->time_format = TranslateDate(date($pt->config->date_style,$post->time));
		$post->text_time = PT_Time_Elapsed_String($post->time);
		$post->user_data = PT_UserData($post->user_id);
		unset($post->user_data->password);
		$post->comments_count     = $db->where('post_id', $post->id)->getValue(T_COMMENTS,'COUNT(*)');
		$post->likes     = $db->where('post_id', $post->id)->where('type', 1)->getValue(T_DIS_LIKES, "count(*)");
        $post->dislikes  = $db->where('post_id', $post->id)->where('type', 2)->getValue(T_DIS_LIKES, "count(*)");
        $u_like     = $db->where('post_id', $post->id)->where('user_id', $user->id)->where('type', 1)->getValue(T_DIS_LIKES, "count(*)");
		$post->liked      = ($u_like > 0) ? 1 : 0;	

		$u_dislike  = $db->where('post_id', $post->id)->where('user_id', $user->id)->where('type', 2)->getValue(T_DIS_LIKES, "count(*)");
		$post->disliked   = ($u_dislike > 0) ? 1 : 0;
    }
    $response_data     = array(
	    'api_status'   => '200',
	    'api_version'  => $api_version,
	    'success_type' => 'most_popular_articles',
	    'data'      => $posts
	);
}
