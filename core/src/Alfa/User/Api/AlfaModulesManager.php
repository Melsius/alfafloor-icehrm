<?php
namespace Alfa\User\Api;

use Classes\AbstractModuleManager;

class AlfaModulesManager extends AbstractModuleManager
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
        $this->addModelClass('IncentiveType');
    }
}
