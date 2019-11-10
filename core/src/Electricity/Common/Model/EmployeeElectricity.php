<?php
namespace Electricity\Common\Model;

use Model\BaseModel;

class EmployeeElectricity extends BaseModel
{
    public $table = 'EmployeeElectricity';

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
