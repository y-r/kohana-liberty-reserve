<?php defined('SYSPATH') or die('No direct access allowed.');

class Kohana_LR_Agent_JSON implements LR_Api
{
    private $root_url = 'https://api2.libertyreserve.com/json/';
    public $auth;

    function __construct(LR_Authentication $a)
    {
        $this->auth = $a;
    }

    private static function generateId()
    {
        return time() . rand(0, 9999);
    }

    private function buildAuthenticationTag($auth)
    {
        return "Id=".$this->generateId().
		"&Account=".$auth->accountId.
		"&Api=".$auth->apiName.
		"&Token=".$auth->createAuthToken();
    }

    public function accountName($accountToRetrieve)
    {
        $request = $this->buildAuthenticationTag($this->auth).
		    "&search=$accountToRetrieve";

        $url = $this->root_url."accountname";
        $responce = $this->getResponse($request, $url);

        $account = $this->parseAccountNameResponse($responce);

        return $account->Accounts[0]->Name;
    }
    
    public function balance($currency)
    {
        $request = $this->buildAuthenticationTag($this->auth);
        $url = $this->root_url."balance";
        $response = $this->getResponse($request, $url);
        $balance = $this->parseBalanceResponse($response);
        return $balance->$currency;
    }
    
    public function history($dateFrom, $dateTo, $currency="", $direction="", $source="", $anonymous="", $reference="", $relatedAccount="", $amountFrom="", $amountTo="")
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
                 
        $request = $this->buildAuthenticationTag($auth) . "&From=$dateFrom&Till=$dateTo&Currency=$currency&Direction=$direction&RelatedAccount=$relatedAccount&Reference=$reference&Source=$source&Private=$anonymous&AmountFrom=$amountFrom&AmountTo=$amountTo";
        
        $url = $this->root_url."history";
        
        $requestParams = $this->buildAuthenticationTag($auth) . "&From=$dateFrom&Till=$dateTo&Currency=$currency&Direction=$direction&RelatedAccount=$relatedAccount&Reference=$reference&Source=$source&Private=$anonymous&AmountFrom=$amountFrom&AmountTo=$amountTo";
        
        $pageNumber = 0;
        $history = array();
        while(true)
        {
            $request = "$requestParams&Page=".($pageNumber++);               
            $response = $this->getResponse($request, $url);
            
            $history = array_merge($history, ($this->parseHistoryResponse($response)));
            if(!$this->hasMorePages($response))
               return $history;    
        }
    }
    
    public function findTransaction($receiptId)
    {
        $request = $this->buildAuthenticationTag($auth) . "&Batch=$receiptId";
        
        $url = $this->root_url."findtransaction";
        $response = $this->getResponse($request, $url);
        
        $findTransfer = $this->parseHistoryResponse($response);
        return $findTransfer[0];
    }
    
    public function transfer($payee, $currency, $amount, $private, $purpose, $reference="", $memo="")
    {
        $request = $this->buildAuthenticationTag($auth)."&Payee=$payee&Currency=$currency&Amount=$amount&Memo=$memo&Private=$private&Purpose=$purpose&Type=transfer&Reference=$reference";
        
        $url = $this->root_url."transfer";
        $response = $this->getResponse($request, $url);
        
        $history = $this->parseHistoryResponse($response);   
        return $history[0];
    }
    
    private function getResponse($data, $url)
    {
        if (!function_exists('curl_init'))
	{
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
    
    private function parseAccountNameResponse($response)
    {      
        $response = json_decode($response);       
        $this->checkError($response);
    
        return $response;
    }
    
    private function parseBalanceResponse($response)
    {
        $response = json_decode($response);
        $this->checkError($response);
            
        return $response->Balance;
    }
    
    private function parseHistoryResponse($response)
    {
        $response = json_decode($response);
        $this->checkError($response);
            
        if($response -> Transactions)
          $response = $response -> Transactions;
        else
          $response = $response -> Transaction;
                    
        $count = count($response); 
        if(is_array($response))
        {
            $i = 0;
            $historyArray = array();
            do {       
                $HistoryItem = new LR_History_Item;
                    
                $HistoryItem->Batch = (int)$response[$i]->Batch;
                $HistoryItem->Date = (string)$response[$i]->Date;
                $HistoryItem->Amount = (string)$response[$i]->Amount;
                $HistoryItem->Fee = (string)$response[$i]->Fee;
                $HistoryItem->Balance = (string)$response[$i]->Balance;
                $HistoryItem->Currency = (string)$response[$i]->Currency;
                $HistoryItem->Payer = (string)$response[$i]->Payer;
                $HistoryItem->PayerName = (string)$response[$i]->PayerName;
                $HistoryItem->Payee = (string)$response[$i]->Payee;
                $HistoryItem->PayeeName = (string)$response[$i]->PayeeName;
                $HistoryItem->Memo = (string)$response[$i]->Memo;
                if(empty($response[$i]->Private)) $response[$i]->Private = 2;
                $HistoryItem->Private = (int)$response[$i]->Private; 
                $HistoryItem->Reference = (string)$response[$i]->Reference;
                $HistoryItem->Source = (string)$response[$i]->Source;
                array_push($historyArray, $HistoryItem);   
            } while(++$i < $count);
        }
        else
        {
            $historyArray = array();
            $HistoryItem = new LR_History_Item;
                
            $HistoryItem->Batch = (int)$response->Batch;
            $HistoryItem->Date = (string)$response->Date;
            $HistoryItem->Amount = (string)$response->Amount;
            $HistoryItem->Fee = (string)$response->Fee;
            $HistoryItem->Balance = (string)$response->Balance;
            $HistoryItem->Currency = (string)$response->Currency;
            $HistoryItem->Payer = (string)$response->Payer;
            $HistoryItem->PayerName = (string)$response->PayerName;
            $HistoryItem->Payee = (string)$response->Payee;
            $HistoryItem->PayeeName = (string)$response->PayeeName;
            $HistoryItem->Memo = (string)$response->Memo;
            if(empty($response->Private))
		$response->Private = 2;
            $HistoryItem->Private = (int)$response->Private;
            $HistoryItem->Reference = (string)$response->Reference;
            $HistoryItem->Source = (string)$response->Source;
            array_push($historyArray, $HistoryItem);   
        }  
        return $historyArray;
    }
  
    function hasMorePages($response)
    {  
        $response = json_decode($response);

        return (bool) $this->checkError($response)->HasMore;
    }    
  
    private function checkError($response)
    {
	if (isset($response->Error) AND $response->Error)
	{
            throw new LR_Exception(
		"Error ':code': :text",
		array(	':code' => $response->Error->ErrorCode,
			':text' => $response->Error->ErrorMessage
		)
	    );
	}

        return $response;
    }
}
