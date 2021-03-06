<?php
namespace Attendance\Common;

use Attendance\Common\Model\Attendance;
use Classes\BaseService;
use Classes\IceResponse;
use Classes\LanguageManager;
use Classes\SubActionManager;
use Utils\LogManager;

class AttendanceHelper extends SubActionManager
{
    public static function SavePunch($req, $baseService, $user)
    {
        $employee = $baseService->getElement('Employee', $req->employee, null, true);
        $inDateTime = $req->in_time;
        $inDateArr = explode(" ", $inDateTime);
        $inDate = $inDateArr[0];
        $outDateTime = $req->out_time;
        $outDate = "";
        $approvedOt = $req->approved_overtime;
        if (empty($approvedOt)) {
            $approvedOt = "0.0";
        }
        if ($approvedOt < 0 || $approvedOt > 24) {
            return new IceResponse(
                IceResponse::ERROR,
                LanguageManager::tran('Approved OT should be between 0 and 24'));
        }
        if (!empty($outDateTime)) {
            $outDateArr = explode(" ", $outDateTime);
            $outDate = $outDateArr[0];
        }

        $note = $req->note;

        //check if dates are different
        if (!empty($outDate) && $inDate != $outDate) {
            return new IceResponse(
                IceResponse::ERROR,
                LanguageManager::tran('Attendance entry should be within a single day')
            );
        }

        //compare dates
        if (!empty($outDateTime) && strtotime($outDateTime) <= strtotime($inDateTime)) {
            return new IceResponse(IceResponse::ERROR, 'Punch-in time should be les than Punch-out time');
        }

        //Find all punches for the day
        $attendance = new Attendance();
        $attendanceList = $attendance->Find(
            "employee = ? and DATE_FORMAT( in_time,  '%Y-%m-%d' ) = ?",
            array($employee->id,$inDate)
        );

        foreach ($attendanceList as $attendance) {
            if (!empty($req->id) && $req->id == $attendance->id) {
                continue;
            }
            if (empty($attendance->out_time) || $attendance->out_time == "0000-00-00 00:00:00") {
                return new IceResponse(
                    IceResponse::ERROR,
                    "There is a non closed attendance entry for today. 
                    Please mark punch-out time of the open entry before adding a new one"
                );
            } elseif (!empty($outDateTime)) {
                if (strtotime($attendance->out_time) >= strtotime($outDateTime)
                    && strtotime($attendance->in_time) <= strtotime($outDateTime)) {
                    //-1---0---1---0 || ---0--1---1---0
                    return new IceResponse(IceResponse::ERROR, "Time entry is overlapping with an existing one: ".$attendance->in_time."-".$attendance->out_time);
                } elseif (strtotime($attendance->out_time) >= strtotime($inDateTime)
                    && strtotime($attendance->in_time) <= strtotime($inDateTime)) {
                    //---0---1---0---1 || ---0--1---1---0
                    return new IceResponse(IceResponse::ERROR, "Time entry is overlapping with an existing one: ".$attendance->in_time."-".$attendance->out_time);
                } elseif (strtotime($attendance->out_time) <= strtotime($outDateTime)
                    && strtotime($attendance->in_time) >= strtotime($inDateTime)) {
                    //--1--0---0--1--
                    return new IceResponse(IceResponse::ERROR, "Time entry is overlapping with an existing one: ".$attendance->in_time."-".$attendance->out_time);
                }
            } else {
                if (strtotime($attendance->out_time) >= strtotime($inDateTime)
                    && strtotime($attendance->in_time) <= strtotime($inDateTime)) {
                    //---0---1---0
                    return new IceResponse(IceResponse::ERROR, "Time entry is overlapping with an existing one: ".$attendance->in_time."-".$attendance->out_time);
                }
            }
        }

        $attendance = new Attendance();
        // Load the existing punch
        if (!empty($req->id)) {
            $attendance->Load("id = ?", array($req->id));
        }
        $attendance->approved_overtime = $approvedOt;
        if (!$attendance->automatic_event || $user->user_level == 'Admin') {
            // If it's and automatic event, only an admin can change the remaining fields
            $attendance->in_time = $inDateTime;
            if (empty($outDateTime)) {
                $attendance->out_time = null;
            } else {
                $attendance->out_time = $outDateTime;
            }

            $attendance->employee = $req->employee;
            $attendance->note = $note;
            $attendance->automatic_event = property_exists($req, "automatic_event") ? $req->automatic_event : 0;
        }
        $ok = $attendance->Save();
        if (!$ok) {
            LogManager::getInstance()->info($attendance->ErrorMsg());
            return new IceResponse(IceResponse::ERROR, "Error occurred while saving attendance");
        }
        return new IceResponse(IceResponse::SUCCESS, $attendance);
    }
}
