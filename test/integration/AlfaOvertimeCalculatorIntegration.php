<?php
namespace Test\Integration;

use Test\Helper\EmployeeTestDataHelper;

use Classes\BaseService;
use Salary\Common\Model\PayrollEmployee;
use Employees\Common\Model\Employee;
use Attendance\Common\Model\Attendance;
use Attendance\Admin\Api\AttendanceActionManager;
use Attendance\Admin\Api\AttendanceUtil;
use Payroll\Common\Model\DeductionGroup;

class AlfaOvertimeCalculatorIntegration extends \TestTemplate
{
    const REGULAR_ON_SITE_GROUP = 'Regular On-site %';
    const FREELANCE_OFF_SITE_GROUP = 'Freelancers Off-site %';
    const SALES_GROUP = 'Sales';
    const REGULAR_OFF_SITE_GROUP = 'Regular Off-site %';
    const FREELANCE_ON_SITE_GROUP = 'Freelancers On-site %';

    const REGULAR_ON_SITE_INDEX = 0;
    const FREELANCE_OFF_SITE_INDEX = 1;
    const SALES_INDEX = 2;
    const REGULAR_OFF_SITE_INDEX = 3;
    const FREELANCE_ON_SITE_INDEX = 4;

    const GROUPS = [
        self::REGULAR_ON_SITE_GROUP, self::FREELANCE_OFF_SITE_GROUP, self::SALES_GROUP, self::REGULAR_OFF_SITE_GROUP, self::FREELANCE_ON_SITE_GROUP
    ];

    protected function setUp()
    {
        parent::setUp();
        $ids = [];
        for ($i = 0; $i < count(self::GROUPS); $i++) {
            $deductionGroup = new DeductionGroup();
            $deductionGroup->Load('name LIKE ?', self::GROUPS[$i]);

            $id = EmployeeTestDataHelper::insertRandomEmployee();
            $ids[] = $id;
            $payrollEmployee = new PayrollEmployee();
            $payrollEmployee->employee = $id;
            $payrollEmployee->pay_frequency = 1;
            $payrollEmployee->currency = 1;
            $payrollEmployee->deduction_group = $deductionGroup->id;
            $this->assertEquals($payrollEmployee->Save(), true);
        }

        $this->ids = $ids;

        $this->actionMgr = new AttendanceActionManager();
        $this->actionMgr->setBaseService(BaseService::getInstance());
    }

    protected function tearDown()
    {
        parent::tearDown();
        foreach ($this->ids as $id) {
            $employee = new Employee();
            $employee->Load("id = ?", array($id));
            $employee->Delete();
        }
    }

    private function offsiteAndSalesOvertimeTest($empIndex)
    {
        $punch = json_decode(json_encode([
            'employee' => $this->ids[$empIndex],
            'in_time' => '2020-01-06 08:00:00',
            'out_time' => '2020-01-06 17:00:00'
        ]));
        $this->assertEquals($punch->employee, $this->ids[$empIndex]);
        
        $this->actionMgr->savePunch($punch);

        $attUtil = new AttendanceUtil();
        $sum = $attUtil->getAttendanceSummary($this->ids[$empIndex], '2020-01-01', '2020-01-15');

        $this->assertEquals($sum['t'], 8*60*60);
    }

    public function testSalesGroupOvertime()
    {
        $empIndex = self::SALES_INDEX;
        $this->offsiteAndSalesOvertimeTest($empIndex);
    }
    
    public function testOffsiteGroupOvertime()
    {
        $this->offsiteAndSalesOvertimeTest(self::FREELANCE_OFF_SITE_INDEX);
        $this->offsiteAndSalesOvertimeTest(self::REGULAR_OFF_SITE_INDEX);
    }
}
