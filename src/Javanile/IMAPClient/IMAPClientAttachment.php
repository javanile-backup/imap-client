<?php

namespace Javanile\IMAPClient;

class gmail_fetch_client_attachment
{
	
	private $inbox = null;
	private $number = null;
	
	private $fpos = null;
	private $part = null;
	
	private $path = null;
	private $file = null;
	private $name = null;
	
	public function __construct(&$inbox,$number,$fpos,$part,$path) {
		$this->inbox = &$inbox;
		$this->number = $number;
		$this->fpos = $fpos;
		$this->part = $part;		
		$this->path = $path;
	}
	
	public function open() {
		//$nome = sanitize_filename($part->dparameters[0]->value);
		$this->name = $this->part->dparameters[0]->value;
		$this->file = $this->path.'/'.$this->name;
		$body = imap_fetchbody($this->inbox, $this->number, $this->fpos+1);

		if ($this->part->encoding == 3) {
			$data = base64_decode($body);
		} else if($this->part->encoding == 4) {
			$data = quoted_printable_decode($body);
		}				

		@mkdir($this->path,0777,true);
		
		file_put_contents($this->file,$data);	
	}
	
	public function getFilename() {
		return $this->name;		
	}
	
	public function getFullname() {
		return $this->file;		
	}
	
}
