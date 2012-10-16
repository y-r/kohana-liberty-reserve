<?php defined('SYSPATH') or die('No direct script access.');

class Model_LR_Payment extends ORM
{
    protected $_created_column = array('column' => 'ts','format'=>TRUE);

    protected $_rules = array(
	'paidby' => array(
	    array('not_empty'),
	    array('regex', array(':value', "/^(U|X)(\d+)$/")),
	),
	'paidto' => array(
	    array('not_empty'),
	    array('regex', array(':value', "/^(U|X)(\d+)$/")),
	),
	'amount' => array(
	    array('not_empty'),
	    array('regex', array(':value', "/^(\d+)(.\d+)?$/")),
	),
	'batch' => array(
	    array('not_empty'),
	),
	'currency' => array(
	    array('not_empty'),
	    array('regex', array(':value', "/^(USD|EURO|GOLD)$/")),
	),
    );
}
