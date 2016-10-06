<?php
/**
 * 
 * 
 */

namespace Javanile\IMAPClient;

class IMAPClientEmail 
{
    /**
     *
     * @var type 
     */
    private $client = null;

    /**
     *
     * @var type 
     */
	private $stream = null;
	
	/**
     *
     * @var type 
     */
	private $number = null;
	
	/**
     *
     * @var type 
     */
	private $from_email = null;
    
    /**
     *
     * @var type 
     */
	private $overview = null;
    
    /**
     *
     * @var type 
     */
	private $structure = null;

    /**
     *
     * @var type 
     */
	private $msgno = null;
    
    /**
     *
     * @var type 
     */
	private $uid = null;

    /**
     *
     * @var type 
     */
    private $body = null;

    /**
     *
     * @var type 
     */
    private $summary = null; 

    /**
     *
     * @var type 
     */
    private $hasAttachments = null;
    
	/**
     * 
     * @param type $client
     * @param type $stream
     * @param type $number
     */
	public function __construct($client, $stream, $number) 
    {
        //
        $this->client = $client;
		
        //
        $this->stream = $stream;
		
        //
        $this->number = $number;	
	}
	
	/**
     * 
     * 
     */
	public function open() 
    {		
        //
		$this->requireOverview();
        
        //
        $this->requireStructure();
	}
    
    /**
     * 
     * 
     */
    private function requireOverview()
    {
        //
        if (!$this->overview)
        {
            //
            $this->overview = @imap_fetch_overview(
                $this->stream, 
                $this->number, 
                0
            );
            
            //
            
          
            //
            $from = null;
            
            //
            preg_match(
                '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i',
                $this->overview[0]->from, 
                $from
            );		
           
            //
            $this->from_email = $from[0];    
            
            //
            if (!$this->overview[0]->message_id) 
            {
                $this->overview[0]->message_id = md5(
                    $this->overview[0]->date.
                    $this->overview[0]->from.
                    $this->overview[0]->to
                );
            }
            
            //
            $this->msgno = $this->overview[0]->msgno;
            
            //
            $this->uid = $this->overview[0]->uid;
        }
    }
    
    /**
     * 
     * 
     */
    private function requireStructure()
    {
        //
        if (!$this->structure)
        {
            //
            $this->structure = imap_fetchstructure($this->stream, $this->number ,0);
        }
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
        $this->overview = imap_fetch_overview($this->stream,$this->number,0);
        
        ##
        return $this->overview[0]->message_id ==  $id;
    }
    
    /**
     * 
     * @return type
     */
    public function getMessageId()
    {
        //
        $this->requireOverview();
        
        //
        return $this->overview[0]->message_id;
    }
    
	##
	public function getAttachments() 
    {
		$out = array();
		
		foreach($this->structure->parts as $fpos=>$part) {
			if (isset($part->disposition) && $part->disposition == "ATTACHMENT") {				
				$out[] = new gmail_fetch_client_attachment($this->stream,$this->number,$fpos,$part,$this->path);
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
            $this->body = quoted_printable_decode(imap_fetchbody($this->stream, $this->number, $mode));
            if (!$this->overview[0]->seen) {
                imap_clearflag_full($this->stream, ''.$this->number, "\\Seen");
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
		imap_mail_move($this->stream, $this->number, $folder);	
        
        //
        if (imap_errors()) 
        { 
            //
            $this->client->createFolder($folder);
            
            //
            if (!imap_errors()) 
            {
                //
                imap_mail_move($this->stream, $this->number, $folder);	           
            }
        }
        
        //
        return $this->success();
	}

    /**
     * 
     * @param type $folder
     * @return type
     */
    public function deleteFromFolder($folder)
    {
        //
        $id     = $this->getMessageId();
        $date   = $this->getDate('Y-m-d');
        $since  = date('Y-m-d',strtotime('-1 days', strtotime($date)));
        $before = date('Y-m-d',strtotime('+1 days', strtotime($date)));
        $query  = "SINCE {$since} BEFORE {$before}";
               
        //
        $this->client->setFolder($folder);
        
        //
        $emails = $this->client->search($query);
     
        //
        if ($emails && count($emails) > 0) 
        {
            //
            foreach ($emails as $email)
            {
                //
                echo $email->getMessageId().' == '.$id ."\n";
            }
        }
        
        //
        return $this->success();
    }
    
    /**
     * 
     * 
     */
    public function deleteFromAllFolder()
    {
        //
        $folders = $this->client->getFolders();
       
        //
        foreach ($folders as $folder)
        {
            //
            $this->deleteFromFolder($folder);  
        }
    }    
    
    /**
     * 
     * @param type $format
     * @return type
     */
    public function getDate($format=null) 
    {
        //
        $this->requireOverview();
        
        //
        return date($format ? $format : 'Y-m-d', strtotime($this->overview[0]->date));
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

