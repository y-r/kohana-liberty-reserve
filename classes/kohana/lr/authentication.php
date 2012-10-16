<?php defined('SYSPATH') or die('No direct access allowed.');

class Kohana_LR_Authentication
{
    public $accountId;
    public $apiName;
    public $securityWord;

    function __construct($accountId, $apiName, $securityWord)
    {
        $this->accountId    = $accountId;
        $this->apiName      = $apiName;
        $this->securityWord = $securityWord;
    }

    public function createAuthToken()
    {
        $datePart = gmdate("Ymd");
        $timePart = gmdate("H");

        $authString = $this->securityWord . ":" . $datePart . ":" . $timePart;
        $sha256     = hash('sha256', $authString);
        return strtoupper($sha256);
    }
}
