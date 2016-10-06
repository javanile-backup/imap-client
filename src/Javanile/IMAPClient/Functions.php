<?php

namespace Javanile\IMAPClient;

class Functions 
{
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
     * 
     */
    public static function getEmailProvider($email)
    {
        //
        $x = null;
        
        //
        $regex = '/@((([^.]+)\.)+)([a-zA-Z]{3,}|[a-zA-Z.]{5,})/';
        
        //
        if (preg_match($regex, $email, $x))
        {
            return $x[0];
        }
    }
    
}
