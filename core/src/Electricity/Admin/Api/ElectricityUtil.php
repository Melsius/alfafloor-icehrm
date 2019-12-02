<?php

namespace Electricity\Admin\Api;

use Electricity\Common\Model\EmployeeElectricity;
use Classes\SettingsManager;

class ElectricityUtil
{
    public $eElectricity;

    public function __construct($employeeElectricity) {
        $this->eElectricity = $employeeElectricity;
    }

    private function getMeasurements($employeeId, $startDate, $endDate)
    {
        $startTime = $startDate." 00:00:00";
        $endTime = $endDate." 23:59:59";
        $rows = $this->eElectricity->Find(
            "employee = ? and date >= ? and date <= ?",
            array($employeeId, $startTime, $endTime)
        );

        return $rows;
    }

    public function getElectricityUsage($employeeId, $startDate, $endDate)
    {
        $measurement = $this->getMeasurements($employeeId, $startDate, $endDate);
        return $measurement;
    }
}
