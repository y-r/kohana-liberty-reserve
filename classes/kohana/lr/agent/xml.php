<?php defined('SYSPATH') or die('No direct access allowed.');

class Kohana_LR_Agent_XML implements LR_Api
{
    private $root_url = "https://api2.libertyreserve.com/xml/";
    public $auth;
    
    function __construct(LR_Authentication $a)
    {
        $this->auth = $a;    
    }
   
    private static function generateId()
    {
        return time() . rand(0, 9999);
    }   
   
    private function buildAuthenticationTag()
    {
        return "<Auth>
                <AccountId>". $this->auth->accountId ."</AccountId>
                <ApiName>". $this->auth->apiName ."</ApiName>
                <Token>" . $this->auth->createAuthToken() . "</Token>
              </Auth>";
    }
    
    public function accountName($accountToRetrieve)
    {
        $request = "
        <AccountNameRequest id='" . $this->generateId() . "'> " . $this->buildAuthenticationTag($this->auth) . "<AccountName>
            <AccountToRetrieve>$accountToRetrieve</AccountToRetrieve>
          </AccountName>
        </AccountNameRequest>";
                                                           
        $url = $this->root_url."accountname";
        $response = $this->getResponse($request, $url);

        $accountName = $this->parseAccountNameResponse($response);
        return $accountName;
    }
    
    public function balance($currency)
    {
        $currency = strtolower($currency);
        $request = "
          <BalanceRequest id='" . $this->generateId() . "'> " . $this->buildAuthenticationTag($this->auth) . "<Balance>
            <CurrencyId>$currency</CurrencyId>
          </Balance>
        </BalanceRequest>";
        
        $url = $this->root_url."balance";
        $response = $this->getResponse($request, $url);
        $balance = $this->parseBalanceResponse($response);
        return $balance;
    }
    
    public function history($dateFrom, $dateTo, $currency="", $direction="", $source="", $anonymous="", $reference="", $relatedAccount="", $amountFrom="", $amountTo="")
    {
        
        $currency = strtolower($currency);
        $direction = strtolower($direction);
        $source = strtolower($source);
        $anonymous = strtolower($anonymous);
        
      
        $requestParams = "
            <From>$dateFrom</From>
            <Till>$dateTo</Till>
            <CurrencyId>$currency</CurrencyId>
            <Direction>$direction</Direction>
            <Source>$source</Source>
            <Anonymous>$anonymous</Anonymous>
            <TransferId>$reference</TransferId>
            <CorrespondingAccountId>$relatedAccount</CorrespondingAccountId>
            <AmountFrom>$amountFrom</AmountFrom><AmountTo>$amountTo</AmountTo>";
        
        $url = $this->root_url."history";
        $pageNumber = 0;
        $history = array();
        while(true)
        {
          $request = "
            <HistoryRequest id='" . $this->generateId() . "'> " . $this->buildAuthenticationTag($auth) . 
            "<History>$requestParams<Pager><PageNumber>".$pageNumber++."</PageNumber></Pager></History>
            </HistoryRequest>";
            $response = $this->getResponse($request, $url);
            
            $history = array_merge($history, ($this->parseHistoryResponse($response)));
            if(!$this->hasMorePages($response))
            { 
                return $history;                 
            } 
        };
    }
    
    public function findTransaction($receiptId)
    {
        $request = "
        <FindTransactionRequest id='" . $this->generateId() . "'> " . $this->buildAuthenticationTag($auth) . "<FindTransaction>
            <ReceiptId>$receiptId</ReceiptId>
          </FindTransaction>
        </FindTransactionRequest>";
        
        $url = $this->root_url."findtransaction";
        $response = $this->getResponse($request, $url);
        $findTransfer = $this->parseHistoryResponse($response);
        return $findTransfer[0];
    }
    
    public function transfer($payee, $currency, $amount, $private, $purpose, $reference="", $memo="")
    {
        $currency = strtolower($currency);
        $private = strtolower($private);
        $purpose = strtolower($purpose);
        
        $request = "
        <TransferRequest id='" . $this->generateId() . "'> " . $this->buildAuthenticationTag($auth) . "<Transfer>
            <TransferType>transfer</TransferType>
            <Payee>$payee</Payee>
            <CurrencyId>$currency</CurrencyId>
            <Amount>$amount</Amount>
            <PaymentPurpose>$purpose</PaymentPurpose>
            <Anonymous>$private</Anonymous>
            <TransferId>$reference</TransferId>
            <Memo>$memo</Memo>
          </Transfer>
        </TransferRequest>";
        
        $url = $this->root_url."transfer";
        $response = $this->getResponse($request, $url);
        $history = $this->parseHistoryResponse($response);
        return $history[0];
    }
    
    private function getResponse($data, $url)
    {
        $data = "req=".urlencode($data);     
        
        if (!function_exists('curl_init')) {
            die("Curl library not installed.");
            return "";                                                                                                             
        }
        $agents[] = "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_5_6; en-us) AppleWebKit/525.27.1 (KHTML, like Gecko) Version/3.2.1 Safari/525.27.1";
        $handler  = curl_init();
        curl_setopt($handler, CURLOPT_URL, $url);
        curl_setopt($handler, CURLOPT_HEADER, 0);
        curl_setopt($handler, CURLOPT_POST, true);
        curl_setopt($handler, CURLOPT_POSTFIELDS, $data);
        // ignore SSL certificate
        curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($handler, CURLOPT_USERAGENT, $agents[rand(0, (count($agents) - 1))]);
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
        $response = simplexml_load_string($response);
        $this->checkError($response);
        $response = $response->AccountName;
        return $response->Name;
    }
    
    private function parseBalanceResponse($response)
    {
        $response = simplexml_load_string($response);
        $this->checkError($response);
        $response = $response->Balance;
        return $response->Value;
    }
    
    private function parseHistoryResponse($response)
    {
        $response = simplexml_load_string($response);
        $this->checkError($response);
            
        $response = $response->Receipt;
            
        $historyArray = array();
        foreach ($response as $item) {       
            $HistoryItem = new LR_History_Item;
            $HistoryItem->Batch = (int)$item->ReceiptId;
            $HistoryItem->Date = (string)$item->Date;
            $HistoryItem->Amount = (string)$item->Amount;
            $HistoryItem->Fee = (string)$item->Fee;
            $HistoryItem->Balance = (string)$item->ClosingBalance;
            $HistoryItem->Currency = (string)$item->CurrencyId;
            $HistoryItem->Payer = (string)$item->Payer;
            $HistoryItem->PayerName = (string)$item->PayerName;
            $HistoryItem->Payee = (string)$item->Payee;
            $HistoryItem->PayeeName = (string)$item->PayeeName;     
            $HistoryItem->Memo = (string)$item->Memo;     
            $HistoryItem->Private = (string)$item->Anonymous;     
            $HistoryItem->Reference = (string)$item->TransferId;     
            $HistoryItem->Source = (string)$item->Source;        
            
            array_push($historyArray, $HistoryItem);
        }
        return $historyArray;
    }

    private function hasMorePages($response)
    {
        $response = simplexml_load_string($response);
        $this->checkError($response);    
        return (bool) $response->Pager->HasMore;
    }
        
    private function checkError($xml)
    {
        if ($xml->Error)
            throw new LR_Exception('Error '.(int) $error->Code.': '.$error->Text. ": ".$error->Description);
        return $xml;
    }
}
