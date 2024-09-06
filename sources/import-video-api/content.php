<?php
if (!IS_LOGGED) {
	exit;
}
$pt->sub_categories_array = array();
foreach ($pt->sub_categories as $cat_key => $subs) {
	$pt->sub_categories_array["'".$cat_key."'"] = '<option value="0">'.$lang->none.'</option>';
	foreach ($subs as $sub_key => $sub_value) {
		$pt->sub_categories_array["'".$cat_key."'"] .= '<option value="'.array_keys($sub_value)[0].'">'.$sub_value[array_keys($sub_value)[0]].'</option>';
	}
}
$pt->page        = 'import-video-api';
$pt->title       = $lang->home . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword     = $pt->config->keyword;

echo PT_LoadPage("hybird_view/content",array(
	'CONTENT' => PT_LoadPage("import-video/content"),
	'EXTRA_JS' => PT_LoadPage("extra-js/content"),
	'IS_LOGGED' => (IS_LOGGED == true) ? 'data-logged="true"' : '',
));
exit();