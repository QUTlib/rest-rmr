<?php
header('Content-Type: text/html;charset=utf-8');
if (isset($_GET['file']) && ($file = $_GET['file']) && is_file($file)) {
?><!doctype html>
<html lang="en">
<head><title>Source: /<?=$file?></title></head>
<body><h1>Source: /<?=$file?></h1><?php highlight_file($file);?>
</html><?php
} else {
	header('X-Fale: carp', false, 404);
?><!doctype html>
<html lang="en">
<head><title>Not Found</title></head>
<body><h1>Not Found</h1><p>The document you're attempting to view could not be found.</p></body>
</html><?php
}
