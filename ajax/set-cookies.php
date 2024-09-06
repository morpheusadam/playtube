<?php
if (!empty($_POST['name']) && isset($_POST['value'])) {
 	$_SESSION[$_POST['name']] = PT_Secure($_POST['value']);
} 
if (!empty($_POST['name']) && isset($_POST['value']) && $_POST['name'] == 'autoplay') {
 	setcookie($_POST['name'], $_POST['value'], time() + (86400 * 30), "/");
}