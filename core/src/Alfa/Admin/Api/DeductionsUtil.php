<?php

namespace Alfa\Admin\Api;

use Alfa\Common\Model\EmployeeDeductions;
use Classes\SettingsManager;
use Utils\LogManager;

class DeductionsUtil
{
    private static $EarlyWithdrawalId = 1;
    private static $GuaranteeId = 2;
    private static $ElectricityId = 3;

    public $eDeductions;

    public function __construct($employeeDeductions = NULL) {
        if ($employeeDeductions == NULL) {
            $employeeDeductions = new EmployeeDeductions();
        }
        $this->eDeductions = $employeeDeductions;
    }

    private function getDeductions($employeeId, $type, $startDate, $endDate)
    {
        $rows = $this->eDeductions->Find(
            "EmployeeDeductions.employee = ? and EmployeeDeductions.deduction_type = ? and EmployeeDeductions.date >= ? and EmployeeDeductions.date <= ? ORDER BY EmployeeDeductions.date",
            array($employeeId, $type, $startDate, $endDate)
        );
        
        if (!is_array($rows) && !is_object($rows)) {
            LogManager::getInstance()->warning("No valid array/object from EmployeeDeductions");
            return [];
        }
        return $rows;
    }

    public function getAdvancesTotal($employeeId, $startDate, $endDate)
    {
        $advances = $this->getDeductions($employeeId, self::$EarlyWithdrawalId, $startDate, $endDate);

        $sum = 0;
        foreach ($advances as &$advance) {
            $sum += $advance->amount;
        }
        return $sum;
    }

    public function getGuaranteeTotal($employeeId, $startDate, $endDate)
    {
        $deductions = $this->getDeductions($employeeId, self::$GuaranteeId, $startDate, $endDate);

        $sum = 0;
        foreach ($deductions as &$deduction) {
            $sum += $deduction->amount;
        }
        return $sum;
    }

    public function getElectricityTotal($employeeId, $startDate, $endDate)
    {
        $deductions = $this->getDeductions($employeeId, self::$ElectricityId, $startDate, $endDate);

        $sum = 0;
        foreach ($deductions as &$deduction) {
            $sum += $deduction->amount;
        }
        return $sum;
    }
}
