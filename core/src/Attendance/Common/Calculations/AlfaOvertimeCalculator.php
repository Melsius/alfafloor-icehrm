<?php
namespace Attendance\Common\Calculations;

use Classes\SettingsManager;
use Attendance\Common\Calculations\BasicOvertimeCalculator;
use Salary\Common\Model\PayrollEmployee;
use Payroll\Common\Model\DeductionGroup;

class AlfaOvertimeCalculator extends BasicOvertimeCalculator 
{
    const ROUNDTOSECONDS = 15*60;
    const HOURSBYDAY = [
        8, 8, 8, 8, 8, 7, 0
    ];
    const BREAKSECONDS = 60*60;

    private $totalTimeInPeriod = 0;
    private $offsiteEmployee = false;

    function __construct($employeeId, $startDateStr, $endDateStr)
    {
        parent::__construct($employeeId, $startDateStr, $endDateStr);
        $date = strtotime($startDateStr);
        $endDate = strtotime($endDateStr);

        while ($date <= $endDate) {
            $this->totalTimeInPeriod += self::HOURSBYDAY[date('w', $date)] * 3600;
            $date = strtotime("+1 day", $date); 
        }
        $payrollEmployee = new PayrollEmployee();
        $payrollEmployee->Load('employee = ?', array($employeeId));

        $salaryGroup = new DeductionGroup();
        $salaryGroup->Load('id = ?', $payrollEmployee->deduction_group);
        if ($salaryGroup->name == 'Sales' || strpos(strtolower($salaryGroup->name), "off-site") !== false) {
            $this->offsiteEmployee = true;
        }
    }

    private function roundTimeStr($timeStr)
    {
        $time = strtotime($timeStr);
        $time -= $time % self::ROUNDTOSECONDS;
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
                        // Calculate time for curDate
                        $diff = $this->roundTimeStr($prevAtEntry->out_time) - $this->roundTimeStr($firstAtEntry->in_time);
                        $atTimeByDay[$curDate] = $diff - self::BREAKSECONDS;
                    }
                    // Update loop variables
                    $curDate = $atDate;
                    $firstAtEntry = $atEntry;
                }

                $prevAtEntry = $atEntry;
            }
            if ($curDate != '') {
                // Calculate time for the remaining date
                $diff = $this->roundTimeStr($prevAtEntry->out_time) - $this->roundTimeStr($firstAtEntry->in_time);
                $atTimeByDay[$curDate] = $diff - self::BREAKSECONDS;
            }

        } else {
            foreach ($atts as $atEntry) {
                if ($atEntry->out_time == "0000-00-00 00:00:00" || empty($atEntry->out_time)) {
                    continue;
                }

                $atDate = date("Y-m-d", strtotime($atEntry->in_time));

                if (!isset($atTimeByDay[$atDate])) {
                    $atTimeByDay[$atDate] = 0;
                }

                $diff = $this->roundTimeStr($atEntry->out_time) - $this->roundTimeStr($atEntry->in_time);
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
            't' => 0, // total time
            'r' => 0, // regular time
            'o' => 0, // overtime
            'd' => 0); // double time -- always 0

        foreach ($atTimeByDay as $date => $time) {
            $result['t'] += $time;
        }
        if ($this->totalTimeInPeriod <= $result['t']) {
            if (!$this->offsiteEmployee) {
                $result['o'] = $result['t'] - $this->totalTimeInPeriod;
                $result['r'] = $result['t'] - $result['o'];
            } else {
                // Overtime is not applicable
                $result['r'] = $this->totalTimeInPeriod;
            }
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
