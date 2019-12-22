<?php
namespace Alfa\Common\Model;

use Model\BaseModel;

class DeductionTypes extends BaseModel
{
    public $table = 'DeductionTypes';

    public function getAdminAccess()
    {
        return array("get","element","save","delete");
    }

    public function getManagerAccess()
    {
        return array();
    }

    public function getUserAccess()
    {
        return array();
    }

    public function getUserOnlyMeAccess()
    {
        return array("get", "element");
    }

    public function getUserOnlyMeSwitchedAccess()
    {
        return array();
    }
}
