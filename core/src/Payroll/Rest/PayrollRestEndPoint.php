<?php
namespace Payroll\Rest;

use Salary\Common\Model\PayrollEmployee;
use Classes\BaseService;
use Classes\Data\Query\DataQuery;
use Classes\Data\Query\Filter;
use Classes\IceResponse;
use Classes\LanguageManager;
use Classes\PermissionManager;
use Classes\RestEndPoint;
use Classes\SettingsManager;
use Employees\Common\Model\Employee;
use Users\Common\Model\User;
use Utils\LogManager;
use Utils\NetworkUtils;

class PayrollRestEndPoint extends RestEndPoint
{
    public function listSalaryGroup(User $user, $group)
    {
        if (empty($group)) {
            return new IceResponse(IceResponse::ERROR, "No group provided", 404);
        }

        if ($user->user_level !== 'Admin') {
            return new IceResponse(IceResponse::ERROR, "Permission denied", 403);
        }

        $payrollEmployee = new PayrollEmployee();
        $employeeList = $payrollEmployee->Find('deduction_group = ?', array($group));

        foreach ($employeeList as &$payrollEmployee) {
            $payrollEmployee = $this->cleanObject($payrollEmployee);
        }
        return new IceResponse(IceResponse::SUCCESS, $employeeList);
    }
}
