<?php
namespace Payroll\Admin\Api;

use Payroll\Rest\PayrollRestEndPoint;
use Classes\AbstractModuleManager;

class PayrollAdminManager extends AbstractModuleManager
{

    public function initializeUserClasses()
    {
    }

    public function initializeFieldMappings()
    {
    }

    public function initializeDatabaseErrorMappings()
    {
    }

    public function setupModuleClassDefinitions()
    {

        $this->addModelClass('Payroll');
        $this->addModelClass('PayrollColumn');
        $this->addModelClass('PayrollColumnTranslation');
        $this->addModelClass('PayrollData');
        $this->addModelClass('PayFrequency');
        $this->addModelClass('PayrollColumnTemplate');
        $this->addModelClass('Deduction');
        $this->addModelClass('DeductionGroup');
        $this->addModelClass('PayslipTemplate');
        $this->addModelClass('PayrollCalculations');
    }
    
    public function setupRestEndPoints()
    {
        \Classes\Macaw::get(REST_API_PATH.'payroll/salary_group/(:any)', function ($group) {
            $restEndPoint = new PayrollRestEndPoint();
            $restEndPoint->process('listSalaryGroupByName', $group);
        });
    }
}
