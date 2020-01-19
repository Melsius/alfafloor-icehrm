<?php
/**
 * Created by PhpStorm.
 * User: Thilina
 * Date: 8/18/17
 * Time: 5:05 AM
 */

namespace Attendance\User\Api;

use Attendance\Common\Model\Attendance;
use Attendance\Common\AttendanceHelper;
use AttendanceSheets\Common\Model\EmployeeAttendanceSheet;
use Classes\BaseService;
use Classes\IceConstants;
use Classes\IceResponse;
use Classes\SettingsManager;
use Classes\SubActionManager;
use TimeSheets\Common\Model\EmployeeTimeSheet;
use Utils\LogManager;
use Utils\NetworkUtils;

class AttendanceActionManager extends SubActionManager
{

    public function getPunch($req)
    {
        return AttendanceHelper::SavePunch($req, $this->baseService, $this->user);
    }

    public function createPreviousAttendnaceSheet($req)
    {
        $employee = $this->baseService->getElement('Employee', $this->getCurrentProfileId(), null, true);

        $timeSheet = new EmployeeAttendanceSheet();
        $timeSheet->Load("id = ?", array($req->id));
        if ($timeSheet->id != $req->id) {
            return new IceResponse(IceResponse::ERROR, "Attendance Sheet not found");
        }

        if ($timeSheet->employee != $employee->id) {
            return new IceResponse(IceResponse::ERROR, "You don't have permissions to add this Attendance Sheet");
        }

        $end = date("Y-m-d", strtotime("last Saturday", strtotime($timeSheet->date_start)));
        $start = date("Y-m-d", strtotime("last Sunday", strtotime($end)));

        $tempTimeSheet = new EmployeeTimeSheet();
        $tempTimeSheet->Load("employee = ? and date_start = ?", array($employee->id, $start));
        if ($employee->id == $tempTimeSheet->employee) {
            return new IceResponse(IceResponse::ERROR, "Attendance Sheet already exists");
        }

        $newTimeSheet = new EmployeeTimeSheet();
        $newTimeSheet->employee = $employee->id;
        $newTimeSheet->date_start = $start;
        $newTimeSheet->date_end = $end;
        $newTimeSheet->status = "Pending";
        $ok = $newTimeSheet->Save();
        if (!$ok) {
            LogManager::getInstance()->info("Error creating time sheet : ".$newTimeSheet->ErrorMsg());
            return new IceResponse(IceResponse::ERROR, "Error creating Attendance Sheet");
        }

        return new IceResponse(IceResponse::SUCCESS, "");
    }
}
