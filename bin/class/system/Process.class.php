<?php
/* An easy way to keep in track of external processes.
 * Ever wanted to execute a process in php, but you still wanted to have somewhat controll of the process ? Well.. This is a way of doing it.
 * @compability: Linux only. (Windows does not work).
 * @author: Peec
 */

class Process {
	private $pid;
	private $command;
	private $tmp;
	private $nohup = true;

	public function __construct($cl = false, $nohup = true, $tempfile) {
		$this->nohup = $nohup;
		if ($cl != false) {
			$this->command = $cl;
			$this->tmp = $tempfile;
			$this->runCom();
		}
	}
	private function runCom() {
		$command = $this->command . ' > /tmp/' . $this->tmp . ' 2>&1 & echo $!';
		if ($this->nohup) {
			$command = 'nohup ' . $command;
		}
		//at_error_log("Command: $command ".getCurrentRenderTime(), "api");

		exec($command, $op);
		$this->pid = (int) $op[0];
	}

	public function setPid($pid) {
		$this->pid = $pid;
	}

	public function getPid() {
		return $this->pid;
	}

	public function getTempFile() {
		return '/tmp/' . $this->tmp;
	}

	public function getOutput() {
		return file_get_contents($this->getTempFile());
	}

	public function isAlive() {
		$command = 'ps -p ' . $this->pid;
		exec($command, $op);
		if (!isset($op[1])) {
			return false;
		} else {
			return true;
		}

	}

	public function start() {
		if ($this->command != '') {
			$this->runCom();
		} else {
			return true;
		}
	}

	public function stop() {
		$command = 'kill ' . $this->pid;
		exec($command);
		if ($this->status() == false) {
			return true;
		} else {
			return false;
		}

	}
}