<?php

class MediaProcesser {

	const VF1080 = "Very Fast 1080p30";
	const VF720 = "Very Fast 720p30";
	const VF480 = "Very Fast 480p30";
	const F1080 = "Fast 1080p30";
	const F720 = "Fast 720p30";
	const F480 = "Fast 480p30";
	const HQ1080_SUR = "HQ 1080p30 Surround";
	const HQ720_SUR = "HQ 720p30 Surround";
	const HQ480_SUR = "HQ 480p30 Surround";
	const SHQ1080_SUR = "Super HQ 1080p30 Surround";
	const SHQ720_SUR = "Super HQ 720p30 Surround";
	const SHQ480_SUR = "Super HQ 480p30 Surround";

	private $mid = null;

	private $cmds = [];

	private $template = '<%hb%> --preset "<%preset%>" -O -i <%source%> -o <%destination%> 2> /tmp/hb.last.log';
	private $thumbs = '<%ff%> -i <%source%> -filter:v framerate=1/5,scale=-1:100 -q:v 1 thumb-%d.jpg 2> /tmp/ffmpeg.err.log';

	private $_1080 = false;
	private $_720 = false;
	private $_480 = false;

	function __construct($media, $source) {

		if (Session::get('mode') == 'web') {
			throw new UnsupportedEnvironmentException;
		}

		if ($media) {
			$this->mid = $media->getMediaID();
		} else {
			throw new MediaResourceNotFoundException;
		}

		if (!file_exists($source)) {
			throw new MediaResourceNotFoundException;
		}

		if (file_exists('/usr/local/bin/ffmpeg')) {
			$this->thumbs = str_replace("<%ff%>", '/usr/local/bin/ffmpeg', $this->thumbs);
		} else if (file_exists('/usr/bin/ffmpeg')) {
			$this->thumbs = str_replace("<%ff%>", '/usr/bin/ffmpeg', $this->thumbs);
		} else {
			throw new EncoderUnavailableException('ffmpeg');
		}

		if (file_exists('/usr/local/bin/HandBrakeCLI')) {
			$this->template = str_replace("<%hb%>", '/usr/local/bin/HandBrakeCLI', $this->template);
		} else if (file_exists('/usr/bin/HandBrakeCLI')) {
			$this->template = str_replace("<%hb%>", '/usr/bin/HandBrakeCLI', $this->template);
		} else {
			throw new EncoderUnavailableException('HandBrakeCLI');
		}

		$this->mid = $media->getMediaID();

		$cdm_1080 = str_replace("<%preset%>", MediaProcesser::VF1080, $this->template);
		$cdm_1080 = str_replace("<%source%>", $source, $cdm_1080);
		$cdm_1080 = str_replace("<%destination%>", Constants::STORAGE . $this->mid . '/1080.mp4', $cdm_1080);

		array_push($this->cmds, $cdm_1080);

		$cdm_720 = str_replace("<%preset%>", MediaProcesser::VF720, $this->template);
		$cdm_720 = str_replace("<%source%>", $source, $cdm_720);
		$cdm_720 = str_replace("<%destination%>", Constants::STORAGE . $this->mid . '/720.mp4', $cdm_720);

		array_push($this->cmds, $cdm_720);

		$cdm_480 = str_replace("<%preset%>", MediaProcesser::VF480, $this->template);
		$cdm_480 = str_replace("<%source%>", $source, $cdm_480);
		$cdm_480 = str_replace("<%destination%>", Constants::STORAGE . $this->mid . '/480.mp4', $cdm_480);

		array_push($this->cmds, $cdm_480);

		$cdm_thumb = str_replace("<%source%>", Constants::STORAGE . $this->mid . '/720.mp4', $this->thumbs);
		$cdm_thumb = 'cd '.Constants::STORAGE . $this->mid . ' && ' . $cdm_thumb . ' && <%jo%> --size=2k *.jpg 2> /tmp/jpegoptim.err.log';

		if (file_exists('/usr/local/bin/jpegoptim')) {
			$cdm_thumb = str_replace("<%jo%>", '/usr/local/bin/jpegoptim', $cdm_thumb);
		} else if (file_exists('/usr/bin/jpegoptim')) {
			$cdm_thumb = str_replace("<%jo%>", '/usr/bin/jpegoptim', $cdm_thumb);
		} else {
			throw new EncoderUnavailableException('jpegoptim');
		}

		array_push($this->cmds, $cdm_thumb);
		$media->setAvailablity(false);
		$progress = 0.0;
		$cmd_percentage = 98.5;
		foreach ($this->cmds as $cmd) {
			if (WebAPI::contains($cmd, 'HandBrakeCLI')) {
				$handle = popen($cmd, 'r');
				if (!is_resource($handle)) {
					$media->addActivityLog("Unable to kick start the pipe handle.", __CLASS__);
					$media->updateBuildInfo(0.0, "Failure");
					break;
				}
				$message = "Processing...";
				if (WebAPI::contains($cmd, "1080p")) {
					$message = "Beautifying 1080p";
				}
				if (WebAPI::contains($cmd, "720p")) {
					$message = "Glamorizing 720p";
				}
				if (WebAPI::contains($cmd, "480p")) {
					$message = "Embellishing 480p";
				}

				while (!feof($handle)) {
					$data = fread($handle, 4096) . "\n";
					$matches = array();
					preg_match_all('(\d+(?:[\.,]\d+)?\b(?!(?:[\.,]\d+)|(?:\s.%|\spercent))|(\d+))', $data, $matches);
					if (isset($matches[0][2])) {
						$pv = round(((floatval($matches[0][2]) * ($cmd_percentage/100.0) + $progress) / 6), 2);
						echo sprintf("%01.2f", $pv) . "%      $message...\n";
						$media->updateBuildInfo($pv, $message);
					}
				}


				$progress += $cmd_percentage;
			} else {
				echo "49.50%    Optimizing stuffs...\n";
				$media->updateBuildInfo(49.50, "Topping Flavours");
				exec($cmd);
				echo "50.00%   Preparing transcoding...\n";
				$media->updateBuildInfo(50.00, "Preparing Oven");
				break;
			}
		}
		$progress = 300.00;
		try {
			$ffmpeg = '';
			if (file_exists('/usr/local/bin/ffmpeg')) {
				$ffmpeg = '/usr/local/bin/ffmpeg';
			} else if (file_exists('/usr/bin/ffmpeg')) {
				$ffmpeg = '/usr/bin/ffmpeg';
			} else {
				throw new EncoderUnavailableException('ffmpeg');
			}

			$mi = '';
			if (file_exists('/usr/local/bin/mediainfo')) {
				$mi = '/usr/local/bin/mediainfo';
			} else if (file_exists('/usr/bin/mediainfo')) {
				$mi = '/usr/bin/mediainfo';
			} else {
				throw new EncoderUnavailableException('mediainfo');
			}

			$cmds[0]['ffmpeg'] = "cd ".Constants::STORAGE.$this->mid." && $ffmpeg -i  1080.mp4 -hls_time 10 -hls_key_info_file \"../secure.keyinfo\" -hls_playlist_type vod -hls_segment_filename \"1080p%d.ts\" 1080p.m3u8 2>&1";
			$cmds[0]['mediainfo'] = "$mi --Inform=\"Video;%Duration/String3%\" ".Constants::STORAGE.$this->mid."/1080.mp4";

			$cmds[1]['ffmpeg'] = "cd ".Constants::STORAGE.$this->mid." && $ffmpeg -i  720.mp4 -hls_time 10 -hls_key_info_file \"../secure.keyinfo\" -hls_playlist_type vod -hls_segment_filename \"720p%d.ts\" 720p.m3u8 2>&1";
			$cmds[1]['mediainfo'] = "$mi --Inform=\"Video;%Duration/String3%\" ".Constants::STORAGE.$this->mid."/720.mp4";

			$cmds[2]['ffmpeg'] = "cd ".Constants::STORAGE.$this->mid." && $ffmpeg -i  480.mp4 -hls_time 10 -hls_key_info_file \"../secure.keyinfo\" -hls_playlist_type vod -hls_segment_filename \"480p%d.ts\" 480p.m3u8 2>&1";
			$cmds[2]['mediainfo'] = "$mi --Inform=\"Video;%Duration/String3%\" ".Constants::STORAGE.$this->mid."/480.mp4";

			foreach($cmds as $cmd){
				$mediainfo = exec($cmd['mediainfo']);
				$time = explode(':', explode('.', $mediainfo)[0]);
				$time = $time[0]*60*60 + $time[1]*60 + $time[2];
				$handle = popen($cmd['ffmpeg'], 'r');
				if (!is_resource($handle)) {
					die('Unable to open pipe');
				}

				while (!feof($handle)) {
					$data = fread($handle, 4096) . "\n";
					$matches = array();
					if(WebAPI::contains($data, 'frame')){
						preg_match_all('/(?:([0-9]{2}):([0-9]{2}):([0-9]{2}))+/', $data, $matches);
						if(isset($matches[1][0]) and $matches[2][0] and $matches[3][0]){
							$ctime = $matches[1][0] * 60*60 + $matches[2][0] * 60 + $matches[3][0];
							$pv = round((($progress + ($ctime/$time)*99.7) / 6), 2);
							$media->updateBuildInfo($pv, "Encrypting chunks...");
							echo "$pv%    Encrypting chunks...\n";
						}
					}
				}
				$progress += 99.7;
			}

			exec('cd '.Constants::STORAGE.$this->mid.' && rm 1080.mp4 && rm 720.mp4 && rm 480.mp4 && rm video.mp4');
			$dir = Constants::STORAGE.$media->getMediaID();
			$result = array();
			$media->updateBuildInfo(99.7, "Bucketing and Cleaning up...");
			if (is_dir($dir)){
				if ($dh = opendir($dir)){
					while (($file = readdir($dh)) !== false){
						if(!WebAPI::startsWith($file, '.') and WebAPI::endsWith($file, '.jpg')){
							array_push($result, $file);
						}
					}
					closedir($dh);
				}
			}
			foreach($result as $image){
				$bucket = new Bucket($this->mid.'_'.$image);
				$bucket->upload(Constants::STORAGE.$this->mid.'/'.$image, true);
			}
			$media->updateBuildInfo(100.00, "It's done!");
			echo "100.00%    Done.\n";
			$media->setAvailablity(true);
			$media->setDuration($time);
		} catch (Exception $e) {
			$media->setAvailablity(false);
			$media->addActivityLog("Error Processing Media", __CLASS__);
			$media->updateBuildInfo(0.0, $e);
			die();
		}
	}
}
