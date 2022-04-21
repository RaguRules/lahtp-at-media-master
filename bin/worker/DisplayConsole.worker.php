<?php
require_once __DIR__ . '/../load.php';

exec('/Applications/Utilities/Console.app/Contents/MacOS/Console '.$_GET['tmp_file'].' &');