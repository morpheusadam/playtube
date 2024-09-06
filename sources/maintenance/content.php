<?php
$pt->title = "Maintenance";
$pt->description = $pt->config->description;
$pt->page = "maintenance";
$pt->content = PT_LoadPage("maintenance/content");

$langs__footer = $langs;
$langs_html    = '';
$langs_modal_html    = '';
foreach ($langs__footer as $key => $language) {
    if ($pt->langs_status[$language] == 1) {
        $lang_explode = explode('.', $language);
        $language     = $lang_explode[0];
        $language_    = ucfirst($language);
        $langs_html .= '<li><a href="?lang=' . $language . '" rel="nofollow">' . $language_ . '</a></li>';
        $langs_modal_html .=  '<li class="language_select"><a href="?lang=' . $language . '" rel="nofollow" class="' . $language . '">' . $language_ . '</a></li>';
    }
}
$pt->langs = $langs_modal_html;

$content_data = [
    'CONTAINER_TITLE' => $pt->title,
    'CONTAINER_TITLE_DE' => htmlspecialchars($pt->title),
    'CONTAINER_DESC_DE' => htmlspecialchars($pt->description),
    'CONTAINER_CONTENT' => $pt->content,
    'theme_url' => $config['theme_url'],
    "HEADER_AD" => "",
    "FOOTER_AD" => "",
    "FOOTER_LAYOUT" => "",
    "EXTRA_JS" => "",
    "OG_METATAGS" => "",
    "HEADER_LAYOUT" => "",
    "ANNOUNCEMENT" => "",
    "DATE" => date('Y')
];

echo PT_LoadPage('container', $content_data);
exit();
