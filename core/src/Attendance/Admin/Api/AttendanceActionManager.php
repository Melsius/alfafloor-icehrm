<?php
/*
 Copyright (c) 2018 [Glacies UG, Berlin, Germany] (http://glacies.de)
 Developer: Thilina Hasantha (http://lk.linkedin.com/in/thilinah | https://github.com/thilinah)
 */
namespace Attendance\Admin\Api;

use Attendance\Common\Model\Attendance;
use Attendance\Common\AttendanceHelper;
use Classes\BaseService;
use Classes\IceResponse;
use Classes\LanguageManager;
use Classes\SubActionManager;
use Utils\LogManager;

class AttendanceActionManager extends SubActionManager
{
    public function savePunch($req)
    {
        return AttendanceHelper::SavePunch($req, $this->baseService, $this->user);
    }

    public function getImages($req)
    {
        $attendance = BaseService::getInstance()->getElement(
            'Attendance',
            $req->id,
            '{"employee":["Employee","id","first_name+last_name"]}'
        );
        return new IceResponse(IceResponse::SUCCESS, $attendance);
    }
}
