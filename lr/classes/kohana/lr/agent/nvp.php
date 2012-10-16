<?php defined('SYSPATH') or die('No direct access allowed.'); 

class Kohana_LR_Agent_NVP implements LR_Api
{
    private $root_url = "https://api2.libertyreserve.com/nvp/";
    public $auth;
    
    function __construct(LR_Authentication $a)
    {
        $this->auth = $a;    
    }
   
    public static function generateId()
    {
         return time() . rand(0, 9999);
    } 

    function buildAuthenticationTag($auth)
    {
         return "Id=".$this->generateId()."&Account=".$auth->accountId."&Api=".$auth->apiName."&Token=".$auth->createAuthToken();
    }
    
    function parseNVP($nvpstr)
    {
      $response = array();
      parse_str($nvpstr,$response);
      return $this->array_to_object($response);
    }
    
    function array_to_object($array = array()) {
        if (!empty($array)) {
            $data = new stdClass();
	    $not_empty = false;
            foreach ($array as $akey => $aval) {
                $data -> {$akey} = $aval;
		$not_empty = true;
            }
            return $not_empty ? $data : FALSE;
        }
        return false;
    }
    
    function accountName($accountToRetrieve)
    {
        $request = $this->buildAuthenticationTag($this->auth) . "&search=$accountToRetrieve";
                                                           
        $url = $this->root_url."accountname";
        $response = $this->getResponse($request, $url);
        
        $accountName = $this->parseAccountNameResponse($response);
        
        return $accountName->$accountToRetrieve;
    }
        
    function balance($currency)
    {
        $request = $this->buildAuthenticationTag($this->auth);
        
        $url = $this->root_url."balance";
        $response = $this->getResponse($request, $url);
            
        $balance = $this->parseBalanceResponse($response);
        $currency = strtoupper($currency);
        return $balance->$currency;
    }
    
    function history($dateFrom, $dateTo, $currency="", $direction="", $source="", $anonymous="", $reference="", $relatedAccount="", $amountFrom="", $amountTo="")
    {
        $dateFrom = urlencode($dateFrom);
        $dateTo = urlencode($dateTo); 
        $currency = urlencode($currency); 
        $direction = urlencode($direction); 
        $source = urlencode($source); 
        $anonymous = urlencode($anonymous); 
        $reference = urlencode($reference); 
        $relatedAccount = urlencode($relatedAccount); 
        $amountFrom = urlencode($amountFrom); 
        $amountTo = urlencode($amountTo); 
        
        $url = $this->root_url."history";
        
        $requestParams = $this->buildAuthenticationTag($auth) . "&From=$dateFrom&Till=$dateTo&RelatedAccount=$relatedAccount&Direction=$direction&Currency=$currency&Source=$source&Reference=$reference&Source=$source&Private=$anonymous&AmountFrom=$amountFrom&AmountTo=$amountTo";
        
        $pageNumber = 0;
        $history = array();
        while(true)
        {
            $request = "$requestParams&Page=".($pageNumber++);               
            $response = $this->getResponse($request, $url);

            
           
            $history = array_merge($history, ($this->parseHistoryResponse($response)));
                     
            if((!$this->hasMorePages($response)))
            {            
               return $history;
            } 
            $this->hasMorePages($response);
            
        };
    }
    
    function findTransaction($receiptId)
    {
        $request = $this->buildAuthenticationTag($auth) . "&Batch=$receiptId";
        
        $url = $this->root_url."findtransaction";
        $response = $this->getResponse($request, $url);
        
        $findTransfer = $this->parseHistoryResponse($response);
        return $findTransfer[0];
    }
    
    function transfer($payee, $currency, $amount, $private, $purpose, $reference="", $memo="")
    {
        $request = $this->buildAuthenticationTag($auth)."&Payee=$payee&Currency=$currency&Amount=$amount&Memo=$memo&Private=$private&Purpose=$purpose&Type=transfer&Reference=$reference";
        
        $url = $this->root_url."transfer";
        $response = $this->getResponse($request, $url);   
        
        $history = $this->parseHistoryResponse($response);   
        return $history[0];
    }
    
    function getResponse($data, $url)
    {
        if (!function_exists('curl_init')) {
            die("Curl library not installed.");
            return "";
        }
        $agent = "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_5_6; en-us) AppleWebKit/525.27.1 (KHTML, like Gecko) Version/3.2.1 Safari/525.27.1";
        $handler  = curl_init();
        curl_setopt($handler, CURLOPT_URL, $url);
        curl_setopt($handler, CURLOPT_HEADER, 0);
        curl_setopt($handler, CURLOPT_POST, true);
        curl_setopt($handler, CURLOPT_POSTFIELDS, $data);
        // ignore SSL certificate
        curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($handler, CURLOPT_USERAGENT, $agent);
        //curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
        ob_start();
        curl_exec($handler);
        curl_close($handler);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
    
    function parseAccountNameResponse($response)
    {      
        return $this->checkError($this->parseNVP($response));
    }
    
    function parseBalanceResponse($response)
    {
	return $this->checkError($this->parseNVP($response));
    }
    
    function parseHistoryResponse($response)
    {
        try {
            $response = $this->parseNVP($response);
            $this->checkError($response);
            $count = $response->COUNT; 
            
           
            
            if($response->DATE1)
            {
                $i = 1;
                $historyArray = array();
                do {       
                    $HistoryItem = new LR_History_Item;
                    
                    $BATCH = "BATCH".$i;
                    $DATE = "DATE".$i;
                    $AMOUNT = "AMOUNT".$i;
                    $FEE = "FEE".$i;
                    $BALANCE = "BALANCE".$i;
                    $CURRENCY = "CURRENCY".$i;
                    $PAYER = "PAYER".$i;
                    $PAYERNAME = "PAYERNAME".$i;
                    $PAYEE = "PAYEE".$i;   
                    $PAYEENAME = "PAYEENAME".$i;
                    $MEMO = "MEMO".$i;   
                    $PRIVATE = "PRIVATE".$i;   
                    $REFERENCE = "REFERENCE".$i;   
                    $SOURCE = "SOURCE".$i;           
                    $HistoryItem->Batch = (int)$response->$BATCH;
                    $HistoryItem->Date = (string)$response->$DATE;
                    $HistoryItem->Amount = (string)$response->$AMOUNT;
                    $HistoryItem->Fee = (string)$response->$FEE;
                    $HistoryItem->Balance = (string)$response->$BALANCE;
                    $HistoryItem->Currency = (string)$response->$CURRENCY;
                    $HistoryItem->Payer = (string)$response->$PAYER;
                    $HistoryItem->PayerName = (string)$response->$PAYERNAME;
                    $HistoryItem->Payee = (string)$response->$PAYEE;
                    $HistoryItem->PayeeName = (string)$response->$PAYEENAME;
                    $HistoryItem->Memo = (string)$response->$MEMO;
                    $HistoryItem->Private = (string)$response->$PRIVATE;
                    $HistoryItem->Reference = (string)$response->$REFERENCE;
                    $HistoryItem->Source = (string)$response->$SOURCE;
                    array_push($historyArray, $HistoryItem);   
                } while($i++ < $count);
            }
            else
            {
                $historyArray = array();
                $HistoryItem = new LR_History_Item;
                
                $HistoryItem->Batch = (int)$response->BATCH;
                $HistoryItem->Date = (string)$response->DATE;
                $HistoryItem->Amount = (string)$response->AMOUNT;
                $HistoryItem->Fee = (string)$response->FEE;
                $HistoryItem->Balance = (string)$response->BALANCE;
                $HistoryItem->Currency = (string)$response->CURRENCY;
                $HistoryItem->Payer = (string)$response->PAYER;
                $HistoryItem->PayerName = (string)$response->PAYERNAME;
                $HistoryItem->Payee = (string)$response->PAYEE;
                $HistoryItem->PayeeName = (string)$response->PAYEENAME;
                $HistoryItem->Memo = (string)$response->MEMO;
                $HistoryItem->Private = (string)$response->PRIVATE;
                $HistoryItem->Reference = (string)$response->REFERENCE;
                $HistoryItem->Source = (string)$response->SOURCE;
                array_push($historyArray, $HistoryItem);   
                
            }  
            return $historyArray;
        }
        catch (Exception $e) {
            $this->outputError($e);
        }
    }
    
    function hasMorePages($response)
    {  
        try { 
            $response = $this->parseNVP($response);
            $this->checkError($response); 
            
            if($response->HASMORE == NULL || $response->HASMORE == "False")
                $response->HASMORE = false;
            
            return $response->HASMORE;
        }
                
        catch (Exception $e) {
            $this->outputError($e);
        }
    }
    
    function checkError($response)
    {
        if (isset($response) AND !empty($response) AND isset($response->ErrorCode) AND $response->ErrorCode)
            throw new LR_Exception($response->ErrorrMessage . (int) $response->ErrorCode);

        return $response;
    }
    
    function outputError($e)
    {
        echo $e->getCode();
        echo $e->getMessage();
        die;
    }
}
