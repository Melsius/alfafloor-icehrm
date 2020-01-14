<?php
namespace Alfa\Admin\Api;

use Classes\AbstractModuleManager;

class AlfaAdminManager extends AbstractModuleManager
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
        $this->addModelClass('EmployeeElectricity');
        $this->addModelClass('IncentiveTypes');
        $this->addModelClass('EmployeeIncentives');
        $this->addModelClass('DeductionTypes');
        $this->addModelClass('EmployeeDeductions');
        $this->addModelClass('PublicHoliday');
    }
 
    public function initCalculationHooks()
    {
        $this->addCalculationHook(
            'EmployeeElectricity_getElectricityUsage',
            'Electricity Usage',
            '\\Alfa\\Admin\\Api\\ElectricityUtil',
            'getElectricityUsage'
        );
        $this->addCalculationHook(
            'EmployeeIncentives_getOutOfTownTotal',
            'Incentives total: out-of-town',
            '\\Alfa\\Admin\\Api\\IncentivesUtil',
            'getOutOfTownTotal'
        );
        $this->addCalculationHook(
            'EmployeeIncentives_getForkliftContainerTotal',
            'Incentives total: forklift container unload',
            '\\Alfa\\Admin\\Api\\IncentivesUtil',
            'getForkliftContainerTotal'
        );
        $this->addCalculationHook(
            'EmployeeIncentives_getSecondTripTotal',
            'Incentives total: second delivery trip',
            '\\Alfa\\Admin\\Api\\IncentivesUtil',
            'getSecondDeliveryTripTotal'
        );
        $this->addCalculationHook(
            'EmployeeIncentives_getPrePaidTotal',
            'Incentives total: pre-paid total',
            '\\Alfa\\Admin\\Api\\IncentivesUtil',
            'getPrePaidTotal'
        );
        $this->addCalculationHook(
            'EmployeeDeductions_getAdvancesTotal',
            'Deductions: Advances total',
            '\\Alfa\\Admin\\Api\\DeductionsUtil',
            'getAdvancesTotal'
        );
        $this->addCalculationHook(
            'EmployeeDeductions_getGuaranteeTotal',
            'Deductions: Guarantee total',
            '\\Alfa\\Admin\\Api\\DeductionsUtil',
            'getGuaranteeTotal'
        );
    }
}
