<?php 
$pt->page_url_ = $pt->config->site_url.'/age_block';
$pt->page = 'age_block';
$pt->title = $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword = $pt->config->keyword;
$pt->age_block_text = $lang->age_block_text;
$pt->content = PT_LoadPage('age_block/content');