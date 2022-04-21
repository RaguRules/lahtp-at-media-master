<?php
require_once __DIR__ . '/../load.php';
$jar = __DIR__.'/../jar/at-kaipulla.jar';
$app_path = "";
$ppt = $_GET['file']; //pdf is also supported
$output = $_GET['output'];
$isPdf = $_GET['is_pdf'];
$pid = $_GET['pid'];
if($_GET['env'] == "local"){
	$app_path = "/Applications/OpenOffice.app/Contents/MacOS";
} else {
	$app_path = "/etc/openoffice4/program";
	if(!file_exists($app_path)){
		echo "env:".Session::$environment.":: No OpenOffice installation found at /usr/bin/openoffice4\n";
		die();
	}
}

$cmd = "/usr/bin/java -Dcom.sun.star.lib.loader.unopath=$app_path -jar '$jar' '$ppt' '$output'";
$handle = popen($cmd, 'r');
if (!is_resource($handle)) {
	echo "Unable to kick start the pipe handle @ ".__CLASS__;
	die();
}
$origin = null;
$slide_count = 0;
$slides = array();
$pdf = null;
while (!feof($handle)) {
	$data = fread($handle, 4096);
	if(!(empty($data) or $data == "" or $data == "\n")){
		$line = trim($data,"\n");
		echo $line."\n";
		$data = explode(':', $line);
		$type = trim($data[0]);
		$message = trim($data[1]);
		switch ($data[0]) {
			case 'origin':
				$origin = $message;
				break;

			case 'info':
				if($message == "error"){
					echo "Error occured while processing the file";
				}
				break;

			case 'wrote':
				if(WebAPI::endsWith($message, 'png')){
					array_push($slides, $message);
				} else if (WebAPI::endsWith($message, 'pdf')) {
					$pdf = $message;
				}
				break;

			case 'slides':
				$slide_count = (int)$message;
				break;
		}
	}
}

$paper = new Paper($pid);
$result = $paper->uploadSlides($slides);


if($result == 1){
	echo "Success";
}
echo "\n\nIntrepreted output: \n";
print_R($slides); //key is sort order
echo "\n"."PDF: ".$pdf."\n";
echo "slide count: $slide_count\n";
