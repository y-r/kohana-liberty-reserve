<?php defined('SYSPATH') or die('No direct script access.');
/**
 * @version SVN: $Id:$
 */

class Controller_LR_Payment extends Kohana_Controller
{
    public function action_status()
    {
	$config = Kohana::$config->load('lr');
        $acc = $config['account'];
	$store = $config['store'];
	$pass = $config['store-security-word'];

	$paidto = isset($_REQUEST['lr_paidto']) ? $_REQUEST['lr_paidto'] : '';
	$paidby = isset($_REQUEST['lr_paidby']) ? $_REQUEST['lr_paidby'] : '';
	$lr_store = isset($_REQUEST['lr_store']) ? stripslashes($_REQUEST['lr_store']) : '';
	$amount = isset($_REQUEST['lr_amnt']) ? $_REQUEST['lr_amnt'] : '';
	$batch = isset($_REQUEST['lr_transfer']) ? $_REQUEST['lr_transfer'] : '';
	$currency = isset($_REQUEST['lr_currency']) ? $_REQUEST['lr_currency'] : '';
	$user_id = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : 0;

	$str2hash = implode(':',array($paidto, $paidby, $lr_store,
				$amount, $batch, $currency, $pass));

	$hash = hash('sha256', $str2hash);

	switch (strtoupper($currency))
	{
	    case 'LRUSD':
		$currency = 'USD';
	    break;
	    case 'LREUR':
		$currency = 'EURO';
	    break;
	    default:
		$currency = 'GOLD';
	    break;
	}

	if (isset($_REQUEST['lr_paidto']) AND
	    strtoupper($_REQUEST['lr_paidto']) == strtoupper($acc) AND
	    isset($_REQUEST['lr_store']) AND
	    strtoupper(stripslashes($_REQUEST['lr_store'])) == strtoupper($store) AND
	    isset($_REQUEST['lr_encrypted']) AND
	    strtoupper($_REQUEST['lr_encrypted']) == strtoupper($hash))
	{
	    $p = ORM::factory('lr_payment');
	    $x = array(
		'paidby' => $paidby,
		'paidto' => $paidto,
		'store' => $lr_store,
		'amount' => $amount,
		'batch' => $batch,
		'currency' => $currency,
	    );
	    $p->values($x);
	    try {
		$p->save();
		$u = ORM::factory('user', $user_id);
		$u->balance += $amount;
		$u->save();
	    } catch (Exception $e) {
# can't do anything
# let admin resolve problem later
	    }
	}else{
# do nothing
	}
    }
}

?>
