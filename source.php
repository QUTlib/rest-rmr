<?php
/*
 * See the NOTICE file distributed with this work for information
 * regarding copyright ownership.  QUT licenses this file to you
 * under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 */

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
