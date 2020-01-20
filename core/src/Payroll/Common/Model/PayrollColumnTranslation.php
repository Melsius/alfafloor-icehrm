<?php
namespace Payroll\Common\Model;

use Model\BaseModel;

class PayrollColumnTranslation extends BaseModel
{
    public $table = 'PayrollColumnTranslations';
    public function getAdminAccess()
    {
        return array("get","element","save","delete");
    }

    public function getManagerAccess()
    {
        return array("get","element","save","delete");
    }

    public function getUserAccess()
    {
        return array("get","element");
    }
}
