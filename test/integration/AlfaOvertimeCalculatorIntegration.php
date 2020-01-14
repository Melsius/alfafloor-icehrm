<?php
namespace Test\Integration;

use Test\Helper\EmployeeTestDataHelper;

use Classes\BaseService;
use Salary\Common\Model\PayrollEmployee;
use Employees\Common\Model\Employee;
use Attendance\Common\Model\Attendance;
use Alfa\Common\Model\PublicHoliday;
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

    private function checkOffsiteBreak($empIndex, $isOffsite)
    {
        $attUtil = new AttendanceUtil();

        // Single punch
        $punch = json_decode(json_encode([
            'employee' => $this->ids[$empIndex],
            'in_time' => '2020-01-06 08:00:00',
            'out_time' => '2020-01-06 17:00:00'
        ]));
        $this->actionMgr->savePunch($punch);

        $sum = $attUtil->getAttendanceSummary($this->ids[$empIndex], '2020-01-06', '2020-01-06');
        if ($isOffsite) {
            $this->assertEquals($sum['t'], 8*60*60);
        } else {
            $this->assertEquals($sum['t'], 9*60*60);
        }

        // Multi-punch
        $punch = json_decode(json_encode([
            'employee' => $this->ids[$empIndex],
            'in_time' => '2020-01-07 08:00:00',
            'out_time' => '2020-01-07 12:00:00'
        ]));
        $this->actionMgr->savePunch($punch);
        $punch = json_decode(json_encode([
            'employee' => $this->ids[$empIndex],
            'in_time' => '2020-01-07 13:00:00',
            'out_time' => '2020-01-07 17:00:00'
        ]));
        $this->actionMgr->savePunch($punch);

        $sum = $attUtil->getAttendanceSummary($this->ids[$empIndex], '2020-01-07', '2020-01-07');
        // Should be the same for both groups
        $this->assertEquals($sum['t'], 8*60*60);
    }

    public function testOffOnGroupsBreak()
    {
        $this->checkOffsiteBreak(self::REGULAR_ON_SITE_INDEX, false);
        $this->checkOffsiteBreak(self::FREELANCE_OFF_SITE_INDEX, true);
        $this->checkOffsiteBreak(self::SALES_INDEX, true);
        $this->checkOffsiteBreak(self::REGULAR_OFF_SITE_INDEX, true);
        $this->checkOffsiteBreak(self::FREELANCE_ON_SITE_INDEX, false);
    }

    private function punchRegularDay($empIndex, $date)
    {
        $punch = json_decode(json_encode([
            'employee' => $this->ids[$empIndex],
            'in_time' => $date.' 08:00:00',
            'out_time' => $date.' 12:00:00'
        ]));
        $this->actionMgr->savePunch($punch);
        $punch = json_decode(json_encode([
            'employee' => $this->ids[$empIndex],
            'in_time' => $date.' 13:00:00',
            'out_time' => $date.' 17:00:00'
        ]));
        $this->actionMgr->savePunch($punch);
    }

    private function punchRegularSaturday($empIndex, $date)
    {
        $punch = json_decode(json_encode([
            'employee' => $this->ids[$empIndex],
            'in_time' => $date.' 08:00:00',
            'out_time' => $date.' 12:00:00'
        ]));
        $this->actionMgr->savePunch($punch);
        $punch = json_decode(json_encode([
            'employee' => $this->ids[$empIndex],
            'in_time' => $date.' 13:00:00',
            'out_time' => $date.' 16:00:00'
        ]));
        $this->actionMgr->savePunch($punch);
    }

    private function checkNoOvertime($empIndex, $isOffsite)
    {
        $attUtil = new AttendanceUtil();

        $this->punchRegularDay($empIndex, '2020-01-01'); // Wednesday
        $this->punchRegularDay($empIndex, '2020-01-02');
        $this->punchRegularDay($empIndex, '2020-01-03');
        $this->punchRegularSaturday($empIndex, '2020-01-04');
        $this->punchRegularDay($empIndex, '2020-01-06');
        $this->punchRegularDay($empIndex, '2020-01-07');
        $this->punchRegularDay($empIndex, '2020-01-08');
        $this->punchRegularDay($empIndex, '2020-01-09');
        $this->punchRegularDay($empIndex, '2020-01-10');
        $this->punchRegularSaturday($empIndex, '2020-01-11');
        $this->punchRegularDay($empIndex, '2020-01-13');
        $this->punchRegularDay($empIndex, '2020-01-14');
        $this->punchRegularDay($empIndex, '2020-01-15');

        $sum = $attUtil->getAttendanceSummary($this->ids[$empIndex], '2020-01-01', '2020-01-15');
        $this->assertEquals($sum['t'], (11*8 + 2*7)*60*60);
        $this->assertEquals($sum['r'], $sum['t']);
        $this->assertEquals($sum['o'], 0);
        $this->assertEquals($sum['d'], 0);
    }

    public function testNoOvertime()
    {
        $this->checkNoOvertime(self::REGULAR_ON_SITE_INDEX, false);
        $this->checkNoOvertime(self::FREELANCE_OFF_SITE_INDEX, true);
        $this->checkNoOvertime(self::SALES_INDEX, true);
        $this->checkNoOvertime(self::REGULAR_OFF_SITE_INDEX, true);
        $this->checkNoOvertime(self::FREELANCE_ON_SITE_INDEX, false);
    }

    private function checkRegularOvertime($empIndex, $isSales)
    {
        $attUtil = new AttendanceUtil();

        $this->punchRegularDay($empIndex, '2020-01-01'); // Wednesday
        $this->punchRegularDay($empIndex, '2020-01-02');
        $this->punchRegularDay($empIndex, '2020-01-03');
        $this->punchRegularSaturday($empIndex, '2020-01-04');
        $this->punchRegularDay($empIndex, '2020-01-06');
        $this->punchRegularDay($empIndex, '2020-01-07');
        $this->punchRegularDay($empIndex, '2020-01-08');
        $this->punchRegularDay($empIndex, '2020-01-09');
        $this->punchRegularDay($empIndex, '2020-01-10');
        $this->punchRegularSaturday($empIndex, '2020-01-11');
        $this->punchRegularDay($empIndex, '2020-01-13');
        $this->punchRegularDay($empIndex, '2020-01-14');
        // Punch 1 hour overtime
        $punch = json_decode(json_encode([
            'employee' => $this->ids[$empIndex],
            'in_time' => '2020-01-15 08:00:00',
            'out_time' => '2020-01-15 12:00:00'
        ]));
        $this->actionMgr->savePunch($punch);
        $punch = json_decode(json_encode([
            'employee' => $this->ids[$empIndex],
            'in_time' => '2020-01-15 13:00:00',
            'out_time' => '2020-01-15 18:00:00'
        ]));
        $this->actionMgr->savePunch($punch);

        $sum = $attUtil->getAttendanceSummary($this->ids[$empIndex], '2020-01-01', '2020-01-15');
        $this->assertEquals($sum['t'], (11*8 + 2*7 + 1)*60*60);
        $this->assertEquals($sum['r'], $sum['t'] - 1*60*60);
        if ($isSales) {
            $this->assertEquals($sum['o'], 0);
        } else {
            $this->assertEquals($sum['o'], 1*60*60);
        }
        $this->assertEquals($sum['d'], 0);
    }

    public function testRegularOvertime()
    {
        $this->checkRegularOvertime(self::REGULAR_ON_SITE_INDEX, false);
        $this->checkRegularOvertime(self::REGULAR_OFF_SITE_INDEX, false);
        $this->checkRegularOvertime(self::SALES_INDEX, true);
    }

    public function testIgnoreEarlyClockIn()
    {
        $empIndex = 0;
        $attUtil = new AttendanceUtil();

        // Punch in early
        $punch = json_decode(json_encode([
            'employee' => $this->ids[$empIndex],
            'in_time' => '2020-01-15 07:00:00',
            'out_time' => '2020-01-15 12:00:00'
        ]));
        $this->actionMgr->savePunch($punch);
        $punch = json_decode(json_encode([
            'employee' => $this->ids[$empIndex],
            'in_time' => '2020-01-15 13:00:00',
            'out_time' => '2020-01-15 17:00:00'
        ]));
        $this->actionMgr->savePunch($punch);

        $sum = $attUtil->getAttendanceSummary($this->ids[$empIndex], '2020-01-15', '2020-01-15');
        $this->assertEquals($sum['t'], 8*60*60);
    }

    private function checkFreelanceOvertime($empIndex)
    {
        $attUtil = new AttendanceUtil();

        $this->punchRegularDay($empIndex, '2020-01-01'); // Wednesday
        // Skip Thursday
        $this->punchRegularDay($empIndex, '2020-01-03');
        $this->punchRegularSaturday($empIndex, '2020-01-04');
        $this->punchRegularDay($empIndex, '2020-01-06');
        $this->punchRegularDay($empIndex, '2020-01-07');
        $this->punchRegularDay($empIndex, '2020-01-08');
        $this->punchRegularDay($empIndex, '2020-01-09');
        $this->punchRegularDay($empIndex, '2020-01-10');
        // Skip Saturday
        $this->punchRegularDay($empIndex, '2020-01-13');
        $this->punchRegularDay($empIndex, '2020-01-14');
        // Punch 1 hour overtime
        $punch = json_decode(json_encode([
            'employee' => $this->ids[$empIndex],
            'in_time' => '2020-01-15 08:00:00',
            'out_time' => '2020-01-15 12:00:00'
        ]));
        $this->actionMgr->savePunch($punch);
        $punch = json_decode(json_encode([
            'employee' => $this->ids[$empIndex],
            'in_time' => '2020-01-15 13:00:00',
            'out_time' => '2020-01-15 18:00:00'
        ]));
        $this->actionMgr->savePunch($punch);

        $sum = $attUtil->getAttendanceSummary($this->ids[$empIndex], '2020-01-01', '2020-01-15');
        $this->assertEquals($sum['t'], (10*8 + 1*7 + 1)*60*60);
        $this->assertEquals($sum['r'], $sum['t'] - 1*60*60);
        $this->assertEquals($sum['o'], 1*60*60);
        $this->assertEquals($sum['d'], 0);
    }
    
    public function testFreelanceOvertime()
    {
        $this->checkFreelanceOvertime(self::FREELANCE_ON_SITE_INDEX, true);
        $this->checkFreelanceOvertime(self::FREELANCE_OFF_SITE_INDEX, true);
    }

    public function testRounding()
    {
        $attUtil = new AttendanceUtil();

        // Punch at corner-cases
        $punch = json_decode(json_encode([
            'employee' => $this->ids[0],
            'in_time' => '2020-01-01 08:00:00',
            'out_time' => '2020-01-01 09:00:00'
        ]));
        $this->actionMgr->savePunch($punch);
        $punch = json_decode(json_encode([
            'employee' => $this->ids[0],
            'in_time' => '2020-01-02 08:14:59',
            'out_time' => '2020-01-02 09:00:00'
        ]));
        $this->actionMgr->savePunch($punch);
        $punch = json_decode(json_encode([
            'employee' => $this->ids[0],
            'in_time' => '2020-01-03 08:15:00',
            'out_time' => '2020-01-03 09:00:00'
        ]));
        $this->actionMgr->savePunch($punch);
        $punch = json_decode(json_encode([
            'employee' => $this->ids[0],
            'in_time' => '2020-01-04 08:15:00',
            'out_time' => '2020-01-04 09:15:00'
        ]));
        $this->actionMgr->savePunch($punch);

        $sum = $attUtil->getAttendanceSummary($this->ids[0], '2020-01-01', '2020-01-01');
        $this->assertEquals($sum['t'], 1*60*60);
        $sum = $attUtil->getAttendanceSummary($this->ids[0], '2020-01-02', '2020-01-02');
        $this->assertEquals($sum['t'], 1*60*60);
        $sum = $attUtil->getAttendanceSummary($this->ids[0], '2020-01-03', '2020-01-03');
        $this->assertEquals($sum['t'], 3*15*60);
        $sum = $attUtil->getAttendanceSummary($this->ids[0], '2020-01-04', '2020-01-04');
        $this->assertEquals($sum['t'], 1*60*60);
    }

    public function testSalesGracePeriod()
    {
        $attUtil = new AttendanceUtil();
        $empIndex = self::SALES_INDEX;

        // Punch at corner-cases
        $punch = json_decode(json_encode([
            'employee' => $this->ids[$empIndex],
            'in_time' => '2020-01-01 08:14:59',
            'out_time' => '2020-01-01 17:00:00'
        ]));
        $this->actionMgr->savePunch($punch);
        $punch = json_decode(json_encode([
            'employee' => $this->ids[$empIndex],
            'in_time' => '2020-01-02 08:29:59',
            'out_time' => '2020-01-02 17:00:00'
        ]));
        $this->actionMgr->savePunch($punch);
        $punch = json_decode(json_encode([
            'employee' => $this->ids[$empIndex],
            'in_time' => '2020-01-03 08:30:00',
            'out_time' => '2020-01-03 17:00:00'
        ]));
        $this->actionMgr->savePunch($punch);

        $sum = $attUtil->getAttendanceSummary($this->ids[$empIndex], '2020-01-01', '2020-01-01');
        $this->assertEquals($sum['t'], 8*60*60);
        $sum = $attUtil->getAttendanceSummary($this->ids[$empIndex], '2020-01-02', '2020-01-02');
        $this->assertEquals($sum['t'], 8*60*60);
        $sum = $attUtil->getAttendanceSummary($this->ids[$empIndex], '2020-01-03', '2020-01-03');
        $this->assertEquals($sum['t'], 8*60*60 - 15*60);
    }

    public function testPublicHolidays()
    {
        $attUtil = new AttendanceUtil();
        $empIndex = 0;
        $this->punchRegularDay($empIndex, '2020-01-01');
        $this->punchRegularDay($empIndex, '2020-01-02');

        // Before adding a public holiday
        $sum = $attUtil->getAttendanceSummary($this->ids[$empIndex], '2020-01-01', '2020-01-02');
        $this->assertEquals($sum['t'], 2*8*60*60);

        // Add a public holiday and verify one day is gone
        $publicHoliday = new PublicHoliday();
        $publicHoliday->date = '2020-01-01';
        $publicHoliday->note = 'New year';
        $publicHoliday->Save();

        $sum = $attUtil->getAttendanceSummary($this->ids[$empIndex], '2020-01-01', '2020-01-02');
        $this->assertEquals($sum['t'], 2*8*60*60);
        $this->assertEquals($sum['r'], 1*8*60*60); // The previously booked day is now overtime
        $this->assertEquals($sum['o'], 1*8*60*60);
    }
}
