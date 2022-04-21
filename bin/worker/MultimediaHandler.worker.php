<?php
require_once __DIR__ . '/../load.php';
$media = new Media($_GET['mid']);
new MediaProcesser($media, $_GET['file']);
