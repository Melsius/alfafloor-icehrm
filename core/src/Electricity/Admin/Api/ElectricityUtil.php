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

    private function getPreviousMeasurementRow($employeeId, $date)
    {
        $rows = $this->eElectricity->Find(
            "employee = ? and date <= ? ORDER BY date DESC LIMIT 0,1",
            array($employeeId, $date)
        );

        if (empty($rows)) {
            return NULL;
        }
        return $rows[0];
    }

    private function getMeasurements($employeeId, $startDate, $endDate)
    {
        $rows = $this->eElectricity->Find(
            "employee = ? and date >= ? and date <= ? ORDER BY date",
            array($employeeId, $startDate, $endDate)
        );

        return $rows;
    }

    public function getElectricityUsage($employeeId, $startDate, $endDate)
    {
        $prevRow = $this->getPreviousMeasurementRow($employeeId, $startDate);

        $measurements = $this->getMeasurements($employeeId, $startDate, $endDate);

        $prevMeasurement = 0;
        if ($prevRow != NULL) {
            $prevMeasurement = $prevRow['measurement'];
        } else if (!empty($measurements)) {
            $prevMeasurement = $measurements[0]['measurement'];
        }

        $usage = 0;
        if (!empty($measurements)) {
            $usage = $measurements[count($measurements) - 1]['measurement'] - $prevMeasurement;
        }

        return $usage;
    }
}
