<?php

/**
 * Storage class for files using the GridFS
 */

require_once __DIR__ . '/MimeTypes.class.php';
class Bucket {
	/**
	 * Bucket collection in Database which is a GridFS
	 * @var MongoDB/GridFS
	 */
	private $bucket = null;

	/**
	 * Original database connection client
	 * @var MongoDB/Client
	 */
	private $database = null;
	private $fileName = null;
	private $id = null;
	private $isId = null;
	private $stream = null;
	private $consName = null;

	/**
	 * Constructs the Bucket object.
	 * @param String  $fileName Filename/FileID
	 * @param boolean $isId     If $filename is FileID, $isID is true.
	 */
	function __construct($fileName, $isId = false) {
		$this->database = DatabaseConnection::getFileDatabase();
		$this->bucket = $this->database->selectGridFSBucket();
		$this->isId = $isId;
		try {
			if ($isId) {
				$this->id = new MongoDB\BSON\ObjectId($fileName);
			} else {
				$this->fileName = $fileName;
			}

			$this->consName = $fileName;
		} catch (Exception $e) {
			//can leave it empty.
		}

	}

	/**
	 * Returns the contents of the file. This method reads the content from the stream and returns it as an echoable resource.
	 * @param  String $revision Files can be stored in revision. If need to select a particular revision, use this.
	 * @return Resource           Echoable file resource.
	 */
	function getContents($revision = null) {
		if ($this->stream) {
			return $this->stream;
		}
		try {
			if ($this->isId) {
				$this->stream = $this->bucket->openDownloadStream($this->id);
			} else {
				$options = null;
				if ($revision) {
					$options['revision'] = $revision;
				}
				if(empty($options)){
					$this->stream = $this->bucket->openDownloadStreamByName($this->fileName);
				} else {
					$this->stream = $this->bucket->openDownloadStreamByName($this->fileName, $options);
				}

			}
			return stream_get_contents($this->stream);
		} catch (Exception $e) {
			return null;
		}
	}

	/**
	 * Upload the file into the GridFS from a particular path.
	 * @param  String  $path
	 * @param  boolean $deleteSource If the original file needs to be deleted after uploading, set this as true.
	 * @return Array                Returns the data from the GridFS related to the current upload.
	 */
	function upload($path, $deleteSource = false) {
		$file = fopen($path, 'rb');
		$this->id = $this->bucket->uploadFromStream($this->fileName, $file);
		if ($deleteSource) {
			unlink($path);
		}
		$pack = array(
			'id' => $this->id . "",
			'id_url' => (new Url('bucket/' . $this->id . '?object'))->getAbsoluteUrl(),
			'file_url' => (new Url('bucket/' . $this->fileName))->getAbsoluteUrl(),
			'cdn_url' => Bucket::cdn('bucket/' . $this->fileName),
			'download_url' => (new Url('bucket/' . $this->fileName . '?download'))->getAbsoluteUrl(),
			'metadata_url' => (new Url('bucket/' . $this->fileName . '?metadata'))->getAbsoluteUrl(),
		);
		return $pack;
	}

	public static function cdn($uri) {
		return Session::cacheCDN($uri);
	}

	/**
	 * Every file has some meta data, like the creation time, edited time etc. Those metadata can be accessed with this method.
	 * @param  String  $revision
	 * @param  boolean $asArray  If the return type needs to be an array, set this true. Else alse. Default: true.
	 * @return Array/MongoDB\BSONObject
	 */
	function getMetaData($revision = null, $asArray = true) {
		$hash = md5($revision . $asArray . $this->consName) . ".meta";
		$cached = Cache::get($hash);
		if ($cached) {
			return $cached;
		}
		if ($this->stream) {
			$this->metadata = $asArray ? WebAPI::purifyArray($this->bucket->getFileDocumentForStream($this->stream)) : $this->bucket->getFileDocumentForStream($this->stream);
			Cache::set($hash, $this->metadata);
			return $this->metadata;
		}
		try {
			if ($this->isId) {
				$this->stream = $this->bucket->openDownloadStream($this->id);
			} else {
				$options = array();
				if ($revision) {
					$options['revision'] = $revision;
				}
				$this->stream = $this->bucket->openDownloadStreamByName($this->fileName, $options);
			}
			$this->metadata = $asArray ? WebAPI::purifyArray($this->bucket->getFileDocumentForStream($this->stream)) : $this->bucket->getFileDocumentForStream($this->stream);
			Cache::set($hash, $this->metadata);
			return $this->metadata;
		} catch (Exception $e) {
			return null;
		}
	}

	/**
	 * Deletes the file from the GridFS.
	 * @return boolean True if deleted, else false.
	 */
	function delete() {
		try {
			if ($this->isId) {
				$this->bucket->delete($this->id);
			} else {
				@$this->bucket->delete($this->getMetaData(null, false)->_id);
			}
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	function getMimeType() {
		global $mime_types;
		$metadata = $this->getMetaData();
		$ext = pathinfo($metadata['filename'], PATHINFO_EXTENSION);
		$ext = strtolower($ext);
		if (isset($mime_types[$ext])) {
			return $mime_types[$ext];
		} else {
			return 'application/octet-stream';
		}
	}

}