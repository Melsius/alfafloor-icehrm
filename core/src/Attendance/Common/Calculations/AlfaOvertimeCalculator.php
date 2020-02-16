<?php
namespace Attendance\Common\Calculations;

use Classes\SettingsManager;
use Attendance\Common\Calculations\BasicOvertimeCalculator;
use Attendance\Admin\Api\AttendanceUtil;
use Salary\Common\Model\PayrollEmployee;
use Payroll\Common\Model\DeductionGroup;

class AlfaOvertimeCalculator extends BasicOvertimeCalculator 
{
    const ROUNDTOSECONDS = 15*60;
    const BREAKSECONDS = 60*60;
    const BREAKTIMESTART = 12*60*60;
    const BREAKTIMEEND = 13*60*60;

    private $totalTimeInPeriod = 0;
    private $offsiteEmployee = false;
    private $salesEmployee = false;
    private $freelanceEmployee = false;
    private $attendanceUtil;

    function __construct($employeeId, $startDateStr, $endDateStr)
    {
        parent::__construct($employeeId, $startDateStr, $endDateStr);
        $date = strtotime($startDateStr);
        $endDate = strtotime($endDateStr);

        $payrollEmployee = new PayrollEmployee();
        $payrollEmployee->Load('employee = ?', array($employeeId));

        $salaryGroup = new DeductionGroup();
        $salaryGroup->Load('id = ?', $payrollEmployee->deduction_group);
        if (strpos(strtolower($salaryGroup->name), "sales") !== false) {
            $this->offsiteEmployee = true;
            $this->salesEmployee = true;
        } elseif (strpos(strtolower($salaryGroup->name), "off-site") !== false) {
            $this->offsiteEmployee = true;
        }
        if (strpos(strtolower($salaryGroup->name), "freelance") !== false) {
            $this->freelanceEmployee = true;
        }

        $this->attendanceUtil = new AttendanceUtil();
        if (!$this->freelanceEmployee) {
            while ($date <= $endDate) {
                $this->totalTimeInPeriod += $this->attendanceUtil->getExpectedTimeSeconds($date);
                $date = strtotime("+1 day", $date); 
            }
        }
    }

    private function roundTimeStr($timeStr)
    {
        $time = strtotime($timeStr);
        $time -= $time % self::ROUNDTOSECONDS;
        return $time;
    }

    private function calcSecondsSinceToday($time)
    {
        return date('H', $time) * 60*60 + date('i', $time) * 60 + date('s', $time);
    }

    private function roundFirstInTimeStr($timeStr)
    {
        $time = strtotime($timeStr);
        $ssinceToday = $this->calcSecondsSinceToday($time);

        if ($ssinceToday < 8*60*60) {
            $time += (8*60*60 - $ssinceToday);
        }
        if ($this->salesEmployee) {
            // Add 15 minute grace period if more than 15 minutes late
            if ($ssinceToday >= 8*60*60 + 15*60) {
                $time -= 15*60;
            }
        }
        $time -= $time % self::ROUNDTOSECONDS;

        return $time;
    }

    private function calcOffsiteTimeWorked($inTimeStr, $outTimeStr)
    {
        // Calculate time for curDate
        $roundedInTime = $this->roundFirstInTimeStr($inTimeStr);
        $roundedOutTime = $this->roundTimeStr($outTimeStr);
        $time = $roundedOutTime - $roundedInTime;

        if ($time <= 0) {
            \Utils\LogManager::getInstance()->error(
                "Negative time calculated for ".
                $curDate.": ".($time/3600)." from ".$firstAtEntry->in_time." to ".$prevAtEntry->out_time);
        }

        $inTime = strtotime($inTimeStr);
        $outTime = strtotime($outTimeStr);
        if ($this->calcSecondsSinceToday($inTime) <= self::BREAKTIMESTART &&
            $this->calcSecondsSinceToday($outTime) >= self::BREAKTIMEEND) {
            // Time period contains a break
            $time = $time - self::BREAKSECONDS;
        }

        if ($time < 0) {
            return 0;
        }
        return $time;
    }

    public function createAttendanceSummary($atts)
    {
        $atTimeByDay = array();

        if ($this->offsiteEmployee) {
            $curDate = '';
            $inTime = NULL;
            $prevAtEntry = NULL;
            foreach ($atts as $atEntry) {
                if ($atEntry->out_time == "0000-00-00 00:00:00" || empty($atEntry->out_time)) {
                    continue;
                }

                $atDate = date("Y-m-d", strtotime($atEntry->in_time));

                if ($curDate != $atDate) {
                    if ($curDate != '') {
                        $atTimeByDay[$curDate] = $this->calcOffsiteTimeWorked($firstAtEntry->in_time, $prevAtEntry->out_time);
                    }
                    // Update loop variables
                    $curDate = $atDate;
                    $firstAtEntry = $atEntry;
                }

                $prevAtEntry = $atEntry;
            }
            if ($curDate != '') {
                $atTimeByDay[$curDate] = $this->calcOffsiteTimeWorked($firstAtEntry->in_time, $prevAtEntry->out_time);
            }
        } else {
            foreach ($atts as $atEntry) {
                if ($atEntry->out_time == "0000-00-00 00:00:00" || empty($atEntry->out_time)) {
                    continue;
                }

                $atDate = date("Y-m-d", strtotime($atEntry->in_time));

                $inTime = 0;
                if (!isset($atTimeByDay[$atDate])) {
                    $atTimeByDay[$atDate] = 0;
                    $inTime = $this->roundFirstInTimeStr($atEntry->in_time);
                } else {
                    $inTime = $this->roundTimeStr($atEntry->in_time);
                }

                $diff = $this->roundTimeStr($atEntry->out_time) - $inTime;
                if ($diff < 0) {
                    $diff = 0;
                }

                $atTimeByDay[$atDate] += $diff;
            }
        }

        return $atTimeByDay;
    }

    protected function createTimeSummary($atTimeByDay)
    {
        $result = array(
            't' => 0,  // total worked time
            'r' => 0,  // regular worked time
            'o' => 0,  // overtime / undertime
            'd' => 0); // double time -- always 0

        $totalTimeInPeriod = $this->totalTimeInPeriod;
        if ($this->freelanceEmployee) {
            $totalTimeInPeriod = 0;
        }
        foreach ($atTimeByDay as $date => $time) {
            $result['t'] += $time;
            if ($this->freelanceEmployee) {
                $dateTime = new \DateTime($date);
                $totalTimeInPeriod += $this->attendanceUtil->getExpectedTimeSeconds($dateTime->format('U'));
            }
        }

        $result['o'] = $result['t'] - $totalTimeInPeriod;
        if ($result['o'] > 0) {
            $result['r'] = $result['t'] - $result['o'];
            if ($this->salesEmployee) {
                // Overtime is not applicable
                $result['o'] = 0;
            }
        } else {
            $result['r'] = $result['t'];
        }

        return $result;
    }

    protected function removeAdditionalDays($atSummary, $actualStartDate)
    {
        $newAtSummary = array();
        foreach ($atSummary as $k => $v) {
            if (strtotime($k) >= strtotime($actualStartDate)) {
                $newAtSummary[$k] = $v;
            }
        }

        return $newAtSummary;
    }

    public function getData($atts, $actualStartDate, $aggregate = false)
    {
        $timeSummary = $this->getDataSeconds($atts, $actualStartDate, false);
        if ($aggregate) {
            return $this->convertToHoursAggregated($timeSummary);
        } else {
            // TODO: throw exception?
            return $this->convertToHours($timeSummary);
        }
    }

    public function getDataSeconds($atts, $actualStartDate, $aggregate = false)
    {
        $atSummary = $this->createAttendanceSummary($atts);
        $timeSummary = $this->createTimeSummary($this->removeAdditionalDays($atSummary, $actualStartDate));
        if ($aggregate) {
            return $timeSummary;
        } else {
            // TODO: throw exception?
            return $overtime;
        }
    }

    public function convertToHours($overtime)
    {
        foreach ($overtime as $k => $v) {
            $overtime[$k]['t'] =  $this->convertToHoursAndMinutes($overtime[$k]['t']);
            $overtime[$k]['r'] =  $this->convertToHoursAndMinutes($overtime[$k]['r']);
            $overtime[$k]['o'] =  $this->convertToHoursAndMinutes($overtime[$k]['o']);
            $overtime[$k]['d'] =  $this->convertToHoursAndMinutes($overtime[$k]['d']);
        }

        return $overtime;
    }

    public function convertToHoursAggregated($overtime)
    {
        $overtime['t'] =  $this->convertToHoursAndMinutes($overtime['t']);
        $overtime['r'] =  $this->convertToHoursAndMinutes($overtime['r']);
        $overtime['o'] =  $this->convertToHoursAndMinutes($overtime['o']);
        $overtime['d'] =  $this->convertToHoursAndMinutes($overtime['d']);

        return $overtime;
    }

    protected function aggregateData($overtime)
    {
        $ag = array("t"=>0,"r"=>0,"o"=>0,"d"=>0);
        foreach ($overtime as $k => $v) {
            $ag['t'] += $v['t'];
            $ag['r'] += $v['r'];
            $ag['o'] += $v['o'];
            $ag['d'] += $v['d'];
        }

        return $ag;
    }

    public function convertToHoursAndMinutes($val)
    {
        $sec = $val % 60;
        $minutesTot = ($val - $sec)/60;

        $minutes = $minutesTot % 60;
        $hours = ($minutesTot - $minutes)/60;

        if ($hours < 10) {
            $hours = "0".$hours;
        }
        if ($minutes < 10) {
            $minutes = "0".$minutes;
        }

        return $hours.":".$minutes;
    }
}
