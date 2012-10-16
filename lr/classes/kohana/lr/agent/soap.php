<?php defined('SYSPATH') or die('No direct access allowed.');

class Kohana_LR_Agent_SOAP implements LR_Api
{
    public $root_url = "https://api2.libertyreserve.com/services/apiservice.asmx?WSDL";
    public $auth;
    public $client;
    
    function __construct(LR_Authentication $a)
    {
        $this->auth = $a;    
    }
   
    public function setupCredentials()
    {        
        $this->client = new SoapClient($this->root_url); 
        
        $Authentication = array('AccountNumber' => $this->auth->accountId, 'ApiName' => $this->auth->apiName, 'Token' => $this->auth->createAuthToken());
        $header =  new SoapHeader("https://api2.libertyreserve.com/soap/", "Authentication", $Authentication);
        $this->client->__setSoapHeaders(array($header));
    }
    
    public function accountName($accountToRetrieve)
    {
        $this->setupCredentials();  
                                                         
        $accountName = $this->client->AccountName($accountToRetrieve); 
        return $accountName;
    }
    
    public function balance($currency)
    {
        $this->setupCredentials(); 
        
        $balance = $this->client->Balance(); 
        return $balance->$currency;
    }
    
    public function history($dateFrom, $dateTo, $currency="Any", $direction="Any", $source="Any", $anonymous="Any", $reference="", $relatedAccount="", $amountFrom=0.0, $amountTo=0.0)
    {
        $dateFrom = $this->getSoapDate($dateFrom);
        $dateTo = $this->getSoapDate($dateTo);
        
        $spec = array(
            'From' => $dateFrom, 
            'Till' => $dateTo, 
            'Currency' => $currency, 
            'Direction' => $direction, 
            'RelatedAccount' => $relatedAccount, 
            'Reference' => $reference, 
            'Source' => $source, 
            'Private' => $anonymous, 
            'AmountFrom' => $amountFrom, 
            'AmountTo' => $amountTo 
        );
        
        $this->setupCredentials(); 
        
        $pageNumber = 0;
        $history = array();
        while(true)
        {
            $historyPage = $this->client->History($spec, $pageNumber++, 20);   
            if(is_array($historyPage->Transactions->Transaction))
                $history = array_merge($history, $historyPage->Transactions->Transaction); 
            else
                array_push($history, $historyPage->Transactions->Transaction);
                
            if(!$this->hasMorePages($historyPage))
            { 
                foreach($history AS $item)
                {
                    $item->Date = $this->getFormatDate($item->Date);
                }
                return $history;                 
            }     
        };
    }
    
    public function findTransaction($receiptId)
    {
        $this->setupCredentials(); 
        
        $findTransfer = $this->client->FindTransaction($receiptId); 
        $findTransfer->Date = $this->getFormatDate($findTransfer->Date);
        return $findTransfer;
    }
    
    public function transfer($payee, $currency, $amount, $private, $purpose, $reference="", $memo="")
    {        
        $private=="true"?$private=true:$private=false;
        
        $spec = array(
                'Payee' => $payee,
                'Amount' => $amount,
                'Currency' => $currency,
                'Memo' => $memo,
                'Reference' => $reference,
                'Private' => $private,
                'Purpose' => $purpose
        );
        $this->setupCredentials(); 
        
        $history = $this->client->Transfer($spec); 
        $history->Date = $this->getFormatDate($history->Date);
        return $history;
    }

    private function hasMorePages($response)
    {
        return (bool) $response->HasMore;
    }
    
    private function getSoapDate($inputDate)
    {
	$timeStamp = strtotime($inputDate);
	$soapDate = date("Y-m-d", $timeStamp). "T" . date("H:i:s", $timeStamp);

	return $soapDate;
    }
    
    private function getFormatDate($inputDate)
    {
	$date = str_replace("T", " ", $inputDate);
        $date = str_replace("Z", "", $date);
        return $date;
    }
}
