<?php
/**
 * Created by PhpStorm.
 * User: Thilina
 * Date: 8/19/17
 * Time: 3:08 PM
 */

namespace Metadata\Common\Model;

use Classes\BaseService;
use Model\BaseModel;

class CalculationHook
{
    public $table = 'CalculationHooks';
    public $name = '';
    public $code = '';

    public function getAdminAccess()
    {
        return array("get","element","save","delete");
    }

    public function getUserAccess()
    {
        return array();
    }

    public function getUserOnlyMeAccess()
    {
        return array("get","element");
    }

    public function getUserOnlyMeSwitchedAccess()
    {
        return $this->getUserOnlyMeAccess();
    }

    public function getUserOnlyMeAccessField()
    {
        return "employee";
    }

    public function getUserOnlyMeAccessRequestField()
    {
        return "employee";
    }

    // @codingStandardsIgnoreStart
    function Find($whereOrderBy, $bindarr = false, $pkeysArr = false, $extra = array())
    {
        return BaseService::getInstance()->getCalculationHooks();
    }

    function Load($where = null, $bindarr = false)
    {
        return BaseService::getInstance()->getCalculationHook($bindarr[0]);
    }

    static function SetDatabaseAdapter($arg)
    {

    }
    // @codingStandardsIgnoreEnd
}
