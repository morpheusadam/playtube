<?php 
$data = array('status' => 400);
if (!empty($_POST['search_value'])) {
	$search_value = PT_Secure($_POST['search_value']);
	$search_result = $db->rawQuery("SELECT * FROM " . T_VIDEOS . " WHERE (title LIKE '%$search_value%' OR tags LIKE '%$search_value%' OR description LIKE '%$search_value%') AND privacy = 0 LIMIT 10");
	if (!empty($search_result)) {
		$html = '';
		foreach ($search_result as $key => $search) {
			$search = PT_GetVideoByID($search, 0, 0, 0);
			$html .= "<div class='search-result'><a href='$search->url'>$search->title</a></div>";
		}
		$data = array('status' => 200, 'html' => $html);
	}
} 
?>