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
    
    public static function factory(LR_Authentication $pauth = NULL, $type = LR::JSON)
    {
	$auth = $pauth;
	if ( ! $auth )
	{
	    $auth = LR::auth();
	}

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
    public static function auth($accountId = '', $apiName = '', $securityWord = '')
    {
	if($accountId AND $apiName AND $securityWord)
	    return new LR_Authentication($accountId, $apiName, $securityWord);

	try
	{
	    $conf = Kohana::$config->load('lr');
	} catch (Kohana_Exception $e)
	{
	    throw new LR_Exception('There is no LR auth config');
	}
	$acc = $accountId ? $accountId : $conf['account'];
	$api = $apiName ? $apiName : $conf['api'];
	$pass = $securityWord ? $securityWord : $conf['api-security-word'];

	if ( ! ($acc AND $api AND $pass))
	{
	    throw new LR_Exception('LR config keys are not defined');
	}

	return new LR_Authentication($acc, $api, $pass);
    }
}
