<?php
/**
 * Created by PhpStorm.
 * User: Thilina
 * Date: 8/13/17
 * Time: 8:07 AM
 */

namespace Attendance\Admin\Api;

use Attendance\Common\Model\Attendance;
use Alfa\Common\Model\PublicHoliday;
use Classes\SettingsManager;

class AttendanceUtil
{
    const HOURSBYDAY = [
        0, 8, 8, 8, 8, 8, 7
    ];

    // Expects $date as Unix time in seconds
    public function getExpectedTimeSeconds($date)
    {
        $dateStr = date('Y-m-d', $date);
        $publicHoliday = new PublicHoliday();
        $publicHoliday->Load('date = ?', $dateStr);
        if ($publicHoliday->date == $dateStr) {
            return 0;
        }
        // Date not in public holidays
        return self::HOURSBYDAY[date('w', $date)] * 3600;
    }

    public function getAttendanceSummary($employeeId, $startDate, $endDate)
    {
        $startTime = $startDate." 00:00:00";
        $endTime = $endDate." 23:59:59";
        $attendance = new Attendance();
        $atts = $attendance->Find(
            "employee = ? and in_time >= ? and out_time <= ?",
            array($employeeId, $startTime, $endTime)
        );

        $atCalClassName = SettingsManager::getInstance()->getSetting('Attendance: Overtime Calculation Class');
        $atCalClassName = '\\Attendance\\Common\\Calculations\\'.$atCalClassName;
        $atCal = new $atCalClassName($employeeId, $startDate, $endDate);
        $atSum = $atCal->getDataSeconds($atts, $startDate, true);

        return $atSum;
    }

    public function getTimeWorkedHours($employeeId, $startDate, $endDate)
    {
        $atSum = $this->getAttendanceSummary($employeeId, $startDate, $endDate);
        return round(($atSum['t']/60)/60, 2);
    }

    public function getRegularWorkedHours($employeeId, $startDate, $endDate)
    {
        $atSum = $this->getAttendanceSummary($employeeId, $startDate, $endDate);
        return round(($atSum['r']/60)/60, 2);
    }

    public function getOverTimeWorkedHours($employeeId, $startDate, $endDate)
    {
        $atSum = $this->getAttendanceSummary($employeeId, $startDate, $endDate);
        if ($atSum['o'] < 0) {
            return 0.00;
        }
        return round(($atSum['o']/60)/60, 2);
    }

    public function getUnderTimeHours($employeeId, $startDate, $endDate)
    {
        $atSum = $this->getAttendanceSummary($employeeId, $startDate, $endDate);
        if ($atSum['o'] > 0) {
            return 0.00;
        }
        return round(($atSum['o']/60)/60, 2);
    }

    public function getWeeklyBasedRegularHours($employeeId, $startDate, $endDate)
    {
        $atSum = $this->getWeeklyBasedOvertimeSummary($employeeId, $startDate, $endDate);
        return round(($atSum['r']/60)/60, 2);
    }

    public function getWeeklyBasedOvertimeHours($employeeId, $startDate, $endDate)
    {
        $atSum = $this->getWeeklyBasedOvertimeSummary($employeeId, $startDate, $endDate);
        return round(($atSum['o']/60)/60, 2);
    }

    public function getWorkedDays($employeeId, $startDate, $endDate)
    {
        $startTime = $startDate." 00:00:00";
        $endTime = $endDate." 23:59:59";
        $attendance = new Attendance();
        $atts = $attendance->Find(
            "employee = ? and in_time >= ? and out_time <= ?",
            array($employeeId, $startTime, $endTime)
        );

        $curDay = '';
        $daysCount = 0;
        $objDump = print_r($atts, true);
        foreach ($atts as &$att) {
            $time = strtotime($att->in_time);
            $dateStr = date('Y-m-d',$time);
            $dateTime = new \DateTime($date);
            if ($this->getExpectedTimeSeconds($datetime->format('U') == 0)) {
                /* Must've been a Sunday or public holiday. 
                 * Only count actual working days */
                continue;
            }
            if ($curDay != $dateStr) {
                $curDay = $dateStr;
                $daysCount++;
            }
        }

        return $daysCount;
    }

    public function getWeeklyBasedOvertimeSummary($employeeId, $startDate, $endDate)
    {

        $attendance = new Attendance();
        $atTimeByWeek = array();

        //Find weeks starting from sunday and ending from saturday in day period

        $weeks = $this->getWeeklyDays($startDate, $endDate);
        foreach ($weeks as $k => $week) {
            $startTime = $week[0]." 00:00:00";
            $endTime = $week[count($week) - 1]." 23:59:59";
            $atts = $attendance->Find(
                "employee = ? and in_time >= ? and out_time <= ?",
                array($employeeId, $startTime, $endTime)
            );
            foreach ($atts as $atEntry) {
                if ($atEntry->out_time == "0000-00-00 00:00:00" || empty($atEntry->out_time)) {
                    continue;
                }
                if (!isset($atTimeByWeek[$k])) {
                    $atTimeByWeek[$k]   = 0;
                }

                $diff = strtotime($atEntry->out_time) - strtotime($atEntry->in_time);
                if ($diff < 0) {
                    $diff = 0;
                }

                $atTimeByWeek[$k] += $diff;
            }
        }

        $overtimeStarts = SettingsManager::getInstance()->getSetting('Attendance: Overtime Start Hour');
        $overtimeStarts = (is_numeric($overtimeStarts))?floatval($overtimeStarts) * 60 * 60 * 5 : 0;
        $regTime = 0;
        $overTime = 0;
        foreach ($atTimeByWeek as $value) {
            if ($value > $overtimeStarts) {
                $regTime += $overtimeStarts;
                $overTime = $value - $overtimeStarts;
            } else {
                $regTime += $value;
            }
        }

        return array('r'=>$regTime,'o'=>$overTime);
    }

    private function getWeeklyDays($startDate, $endDate)
    {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate.' 23:59');
        $interval = new \DateInterval('P1D');
        $dateRange = new \DatePeriod($start, $interval, $end);

        $weekNumber = 1;
        $weeks = array();
        /* @var \DateTime $date */
        foreach ($dateRange as $date) {
            $weeks[$weekNumber][] = $date->format('Y-m-d');
            if ($date->format('w') == 6) {
                $weekNumber++;
            }
        }

        return $weeks;
    }
}
