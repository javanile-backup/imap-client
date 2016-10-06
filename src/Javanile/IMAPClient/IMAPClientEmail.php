<?php

namespace Javanile\IMAPClient;

##
class gmail_fetch_client_mail {
	
	##
	private $inbox = null;
	
	##
	private $number = null;
	
	##
	private $from_email = null;

	##
	private $path = null;

    ##
    private $body = null;

    ##
    private $summary = null;

    ##
    private $hasAttachments = null;
    
    private $client = null;

	##
	public function __construct(&$inbox, $number, $path, $client) {
		$this->inbox  = &$inbox;
		$this->number = $number;				
		$this->path   = $path;
        $this->client = $client;
	}
	
	##
	public function open() {		
		$this->overview = imap_fetch_overview($this->inbox,$this->number,0);
		$this->structure = imap_fetchstructure($this->inbox, $this->number ,0);
		preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i',$this->overview[0]->from,$data);		
		$this->from_email = $data[0];      
	}

    ##
    public function getFrom() {
        return $this->overview[0]->from;
    }

    ##
    public function getTo() {
        return $this->overview[0]->to;
    }

	##
	public function getFromEmail() {
		return $this->from_email;		
	}
	
	##
	public function getSubject() {		
		return isset($this->overview[0]->subject) ? quoted_printable_decode($this->overview[0]->subject) : '(nessuno)';
	}
	
    ##
    public function testMessageId($id)
    {
        ##
        $this->overview = imap_fetch_overview($this->inbox,$this->number,0);
        
        ##
        return $this->overview[0]->message_id ==  $id;
    }
    
    ##
    public function getMessageId()
    {
        ##
        return $this->overview[0]->message_id;
    }
    
	##
	public function getAttachments() {
		$out = array();
		
		foreach($this->structure->parts as $fpos=>$part) {
			if (isset($part->disposition) && $part->disposition == "ATTACHMENT") {				
				$out[] = new gmail_fetch_client_attachment($this->inbox,$this->number,$fpos,$part,$this->path);
			}
		}
		
		return $out;		
	}

    ##
	public function hasAttachments() {

        ##
        if (is_null($this->hasAttachments)) {

            ##
            $this->hasAttachments = false;

            ##
            if (isset($this->structure->parts)) {
                foreach(@$this->structure->parts as $fpos=>$part) {
                    if (isset($part->disposition) && $part->disposition == "ATTACHMENT") {
                        $this->hasAttachments = true;
                    }
                }
            }
        }

        ##
		return $this->hasAttachments;
	}

    ##
    public function getBody() {

        ##
        if (!$this->body) {
            if ($this->hasAttachments()) {
                $mode = 1.2;
            } else {
                $mode = 1;
            }
            $this->body = quoted_printable_decode(imap_fetchbody($this->inbox, $this->number, $mode));
            if (!$this->overview[0]->seen) {
                imap_clearflag_full($this->inbox, ''.$this->number, "\\Seen");
            }            
        }
        return $this->body; 
    }

    ##
    public function getContent() {
        return $this->getBody();
    }

	##
	public function moveTo($foldername)
    {	
        //
        $folder = trim($foldername);
        
        //
		imap_mail_move($this->inbox, $this->number, $folder);	
        
        //
        if (imap_errors()) 
        { 
            //
            $this->client->createFolder($folder);
            
            //
            if (!imap_errors()) 
            {
                //
                imap_mail_move($this->inbox, $this->number, $folder);	           
            }
        }
        
        //
        return $this->success();
	}

    ##
    public function deleteFromFolder($folder)
    {
        ##
        $my_message_id = $this->getMessageId();
        
        ##
        $date = $this->getDate('Y-m-d');
        $since = date('Y-m-d',strtotime('-1 days',strtotime($date)));
        $before = date('Y-m-d',strtotime('+1 days',strtotime($date)));
        $search = "SINCE {$since} BEFORE {$before}";
           
        ##
        $this->client->setFolder($folder);
        
        ##
        $emails = imap_search($this->inbox, $search);
     
        ##
        if ($emails && count($emails) > 0) {
            foreach ($emails as $i) {
                $o = imap_fetch_overview($this->inbox,$i,0);
                if ($o[0]->message_id == $my_message_id) {
                    imap_delete($this->inbox, $i);
                    return;
                }
            }
        }
        
        ##
        return !imap_errors();
    }
    
    ##
    public function deleteFromAllFolder()
    {
        //
        $folders = $this->client->getFolders();
        
        
        var_Dump($folders);
        
        die();
    }    
    
    ##
    public function getDate($format=null) 
    {
        return date($format ? $format : 'd/m/Y', strtotime($this->overview[0]->date));
    }

    ##
    public function getSummary() {

        ##
        if (is_null($this->summary)) {

            $body = trim(nl2br($this->getBody()));
            
            $body = strip_tags(str_replace(array('</p>','</div>'), array("\n","\n"), $body));

            $cut0 = strpos($body, "--");

            if ($cut0 > 0) {
                $body = substr($body, 0, $cut0);
            }

            $this->summary = trim($body);
        }

        ##
        return $this->summary;
    }

    ##
    public function getHash() {
        return md5($this->overview[0]->message_id.@$this->overview[0]->subject);
    }
    
    /**
     * 
     * 
     */
    private function success()
    {
        //
        return !count(imap_errors());
    }
}

