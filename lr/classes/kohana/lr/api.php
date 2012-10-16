<?php defined('SYSPATH') or die('No direct access allowed.');

interface Kohana_LR_Api
{
    function balance($currency);
    function accountName($accountToRetrieve);
}
