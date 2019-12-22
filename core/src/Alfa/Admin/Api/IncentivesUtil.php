<?php

namespace Alfa\Admin\Api;

use Alfa\Common\Model\EmployeeIncentives;
use Classes\SettingsManager;
use Utils\LogManager;

\ADOdb_Active_Record::TableBelongsTo('EmployeeIncentives', 'IncentiveTypes', 'incentive_type', 'id');

class IncentivesUtil
{
    private static $OutOfTownId = 1;
    private static $ForkliftContainerId = 2;
    private static $SecondDeliveryTripId = 3;

    public $eIncentives;

    public function __construct($employeeIncentives = NULL) {
        if ($employeeIncentives == NULL) {
            $employeeIncentives = new EmployeeIncentives();
        }
        $this->eIncentives = $employeeIncentives;
    }

    private function getIncentives($employeeId, $type, $startDate, $endDate)
    {
        $rows = $this->eIncentives->Find(
            "EmployeeIncentives.employee = ? and EmployeeIncentives.incentive_type = ? and EmployeeIncentives.date >= ? and EmployeeIncentives.date <= ? ORDER BY EmployeeIncentives.date",
            array($employeeId, $type, $startDate, $endDate)
        );
        
        if (!is_array($rows) && !is_object($rows)) {
            LogManager::getInstance()->warning("No valid array/object from EmployeeIncentives");
            return [];
        }
        return $rows;
    }

    public function getOutOfTownTotal($employeeId, $startDate, $endDate)
    {
        // TODO: inner join with incentiveTypes
        $incentives = $this->getIncentives($employeeId, self::$OutOfTownId, $startDate, $endDate);

        $sum = 0;
        foreach ($incentives as &$incentive) {
            $sum += $incentive->amount;
        }
        return $sum;
    }

    public function getForkliftContainerTotal($employeeId, $startDate, $endDate)
    {
        // TODO: inner join with incentiveTypes
        $incentives = $this->getIncentives($employeeId, self::$ForkliftContainerId, $startDate, $endDate);

        $sum = 0;
        foreach ($incentives as &$incentive) {
            $sum += $incentive->amount;
        }
        return $sum;
    }

    public function getSecondDeliveryTripTotal($employeeId, $startDate, $endDate)
    {
        // TODO: inner join with incentiveTypes
        $incentives = $this->getIncentives($employeeId, self::$SecondDeliveryTripId, $startDate, $endDate);

        $sum = 0;
        foreach ($incentives as &$incentive) {
            $sum += $incentive->amount;
        }
        return $sum;
    }

    public function getPrePaidTotal($employeeId, $startDate, $endDate)
    {
        $rows = $this->eIncentives->Find(
            "EmployeeIncentives.employee = ? and EmployeeIncentives.pre_paid = 1 and EmployeeIncentives.date >= ? and EmployeeIncentives.date <= ? ORDER BY EmployeeIncentives.date",
            array($employeeId, $startDate, $endDate)
        );
        
        if (!is_array($rows) && !is_object($rows)) {
            LogManager::getInstance()->warning("No valid array/object from EmployeeIncentives");
            return 0;
        }
        
        $sum = 0;
        foreach ($rows as &$incentive) {
            $sum += $incentive->amount;
        }
        return $sum;
    }
}
