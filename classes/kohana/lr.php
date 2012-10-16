<?php defined('SYSPATH') or die('No direct access allowed.');

class Kohana_LR
{
    const USD = 'Usd';
    const EURO = 'Euro';
    const GOLD = 'Gold';

    const XML = 'xml';
    const NVP = 'nvp';
    const JSON = 'json';
    const SOAP = 'soap';
    
    public static function factory(LR_Authentication $auth, $type = LR::JSON)
    {
	switch($type)
        {
           case "xml": 
              return new LR_Agent_XML($auth); break;
           case "nvp":
              return new LR_Agent_Nvp($auth); break;
           case "json": 
              return new LR_Agent_Json($auth); break;       
           case "soap": 
              return new LR_Agent_Soap($auth); break;          
           default:      
              return new LR_Agent_Json($auth);
              break;
        }     
    }
    public static function auth($accountId, $apiName, $securityWord)
    {
	if($accountId AND $apiName AND $securityWord)
	    return new LR_Authentication($accountId, $apiName, $securityWord);

	$conf = Kohana::$config->load('lr');
	$acc = $accountId ? $accountId : $conf['account'];
	$api = $apiName ? $apiName : $conf['api'];
	$pass = $securityWord ? $securityWord : $cinf['pass'];

	return new LR_Authentication($acc, $api, $pass);
    }
}
