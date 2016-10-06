<?php


namespace Javanile\IMAPClient;



/**
 * 
 * 
 * 
 */
class IMAPClient 
{
	
	##
	private $hostname = null;
	
	##
	private $username = null;
	
	##
	private $password = null;
	
	##
	private $path = null;
	
	##
	private $inbox = null;
    
    public static $labels_fix = array(
        'gennario',
        'febbraio',
        'marzo',
        'aprile',
        'maggio',
        'giugno',
        'luglio',
        'agosto',
        'settembre',
        'ottobre',
        'novembre',
        'dicembre'
    );
    
    
    public static $labels_to = array(
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December'
    );
	
	/**
     * 
     * @param type $args
     */
	public function __construct($args)
    {
		
        
        
		##
		error_reporting(E_ALL);
		ini_set("display_errors",1);
		
		##
		$this->hostname = '{imap.gmail.com:993/imap/ssl/novalidate-cert}';
		$this->username = $args['username'];
		$this->password = $args['password'];

		##
		$this->path = rtrim($path,'/').'/'.$username.'/'.time();		
	}
	
	##
	public function login() 
    {
        ##
		$this->inbox = @imap_open(
			$this->hostname,
			$this->username,
			$this->password
		);

        ##
        return !imap_errors();
	}
	
	##
	public function createFolder($folder) 
    {
        ##
        imap_createmailbox($this->inbox,"{$this->hostname}$folder");
        
        ##
		return !imap_errors();
	}
	
	##
	public function getEmails()
    {	
		##
		$out = array();
		
		##
		$emails = imap_search($this->inbox,'ALL');		
       
		##
		if ($emails) {			
			rsort($emails);		
			foreach($emails as $number) { 
				$out[] = new gmail_fetch_client_mail($this->inbox,$number,$this->path,$this);
			}			
		}

		##
		return $out;		
	}

    ##
	public function getEmailsSince($since) {

		##
		$out = array();
		
		##
		$emails = imap_search($this->inbox,'SINCE '.$since);

		##
		if ($emails) {
			rsort($emails);
			foreach($emails as $number) {
				$out[] = new gmail_fetch_client_mail($this->inbox,$number,$this->path,$this);
			}
		}

		##
		return $out;
	}


    ##
	public function getAllEmails() {

		##
		$out = array();

		##
		$emails = imap_search($this->inbox, 'ALL');
       
		##
		if ($emails) {
			rsort($emails);
			foreach($emails as $number) {
				$out[] = new gmail_fetch_client_mail($this->inbox,$number,$this->path, $this);
			}
		}

		##
		return $out;
	}

    ##
	public function getSendedEmails() {

		##
		$out = array();

		##
		$emails = imap_search($this->inbox,'ALL');
       
		##
		if ($emails) {
			rsort($emails);
			foreach($emails as $number) {
				$out[] = new gmail_fetch_client_mail($this->inbox,$number,$this->path, $this);
			}
		}

		##
		return $out;
	}
    
    
    /**
     * 
     * @param type $date
     * @return \gmail_fetch_client_mail
     */
	public function getEmailsOnDate($date)
    {
		//
		$out = array();

		//
		$emails = imap_search($this->inbox,"ON {$date}");
       
		//
		if ($emails)
        {
			foreach($emails as $number) {
				$out[] = new gmail_fetch_client_mail($this->inbox,$number,$this->path, $this);
			}
		}

		//
		return $out;
	}
    
    ##
    public function getEmailByMessageId($id, $date)
    {
        
        $date = str_replace(gmail_fetch_client::$labels_fix,gmail_fetch_client::$labels_to, strtolower($date));
        
        /**/
        $since = date('Y-m-d',strtotime('-1 days',strtotime($date)));
        
        $before = date('Y-m-d',strtotime('+1 days',strtotime($date)));
        
        $search = "SINCE {$since} BEFORE {$before}";
        /** /
        $on = date('Y-m-d', strtotime($date));
        
        $search = "ON {$on}";
        /**/
       
        
        ##
		$out = array();

		##
		$emails = imap_search($this->inbox, $search);
       
		##
		if ($emails) {
			rsort($emails);
			foreach($emails as $number) {
                //echo $number."<br/>";
                $email = new gmail_fetch_client_mail($this->inbox,$number,$this->path,$this);
				
                if ($email->testMessageId($id))
                {
                    
                   return $email;
                    
                }

                
			}
		}

        
   
    }

    ##
    public function getFolders() {
        $list = imap_list($this->inbox, "{imap.gmail.com:993/imap/ssl/novalidate-cert}", "*");
        
        var_dump($list);
        die();
    }

    ##
    public function setFolder($folder) 
    {
        ##
        imap_reopen($this->inbox, "{imap.gmail.com:993/imap/ssl/novalidate-cert}{$folder}");

        ##
        $errors = imap_errors();

        ##
        if ($errors) {
            var_dump($errors);
            die();
        }
    }

	##
	public function close()
    {		
        //
        imap_expunge($this->inbox); 
		
        //
        imap_close($this->inbox, CL_EXPUNGE);
	}
}

