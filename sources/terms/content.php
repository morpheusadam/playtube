<?php
if (empty($_GET['type']) || !isset($_GET['type'])) {
	header("Location: " . PT_Link(''));
	exit();
}
$pages = array('terms','privacy-policy','about-us', 'refund-policy', 'faqs');
if (!in_array($_GET['type'], $pages)) {
	header("Location: " . PT_Link(''));
	exit();
}
if ($_GET['type'] == 'refund-policy' && $pt->refund_terms_page == 0) {
	header("Location: " . PT_Link(''));
	exit();
}
if ($_GET['type'] == 'terms' && $pt->terms_of_use_page == 0) {
	header("Location: " . PT_Link(''));
	exit();
}
if ($_GET['type'] == 'privacy-policy' && $pt->privacy_policy_page == 0) {
	header("Location: " . PT_Link(''));
	exit();
}
if ($_GET['type'] == 'about-us' && $pt->about_page == 0) {
	header("Location: " . PT_Link(''));
	exit();
}
$_GET['type'] = strip_tags($_GET['type']);
$pt->terms = PT_GetTerms();

$pt->description  = $pt->config->description;
$pt->keyword   = $pt->config->keyword;
$pt->page        = 'terms';
$pt->title       = '';
$type = PT_Secure($_GET['type']);

if ($type == 'terms') {
	$pt->title  = $lang->terms_of_use;
} else if ($type == 'about-us') {
    $pt->title  = $lang->about_us;
} else if ($type == 'privacy-policy') {
    $pt->title  = $lang->privacy_policy;
} else if ($type == 'refund-policy') {
    $pt->title  = $lang->refund_policy;
} else if ($type == 'faqs') {
    $pt->title  = $lang->faqs;
}

$page = 'terms/' . $type;
$pt->page_url_ = $pt->config->site_url.'/terms/'.$type;
$pt->title = $pt->config->name . ' | ' . $pt->title;
$pt->content  = PT_LoadPage($page);
