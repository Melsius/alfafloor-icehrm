<?php
namespace Electricity\Admin\Api;

use Classes\AbstractModuleManager;

class ElectricityAdminManager extends AbstractModuleManager
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
    }
    
    public function initCalculationHooks()
    {
        $this->addCalculationHook(
            'EmployeeElectricity_getElectricityUsage',
            'Electricity Usage',
            '\\Electricity\\Admin\\Api\\ElectricityUtil',
            'getElectricityUsage'
        );
    }
}
