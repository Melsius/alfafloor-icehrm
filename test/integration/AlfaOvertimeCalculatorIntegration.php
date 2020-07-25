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
            'out_time' => '2020-01-06 16:00:00'
        ]));
        $this->actionMgr->savePunch($punch);

        $sum = $attUtil->getAttendanceSummary($this->ids[$empIndex], '2020-01-06', '2020-01-06');
        if ($isOffsite) {
            $this->assertEquals($sum['r']/3600, 7);
        } else {
            $this->assertEquals($sum['r']/3600, 8);
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
        // One day before 12 only (start of break)
        $this->actionMgr->savePunch($punch);
        $punch = json_decode(json_encode([
            'employee' => $this->ids[$empIndex],
            'in_time' => '2020-01-08 08:00:00',
            'out_time' => '2020-01-08 12:00:00'
        ]));
        $this->actionMgr->savePunch($punch);
        // One day after 13 only (end of break)
        $punch = json_decode(json_encode([
            'employee' => $this->ids[$empIndex],
            'in_time' => '2020-01-09 13:00:00',
            'out_time' => '2020-01-09 17:00:00'
        ]));
        $this->actionMgr->savePunch($punch);
        // One day less than the breaktime (1 hour)
        $punch = json_decode(json_encode([
            'employee' => $this->ids[$empIndex],
            'in_time' => '2020-01-10 13:00:00',
            'out_time' => '2020-01-10 14:00:00'
        ]));
        $this->actionMgr->savePunch($punch);

        $sum = $attUtil->getAttendanceSummary($this->ids[$empIndex], '2020-01-07', '2020-01-10');
        if ($empIndex == self::SALES_INDEX) {
            // Reduce by morning grace period
            $this->assertEquals($sum['r']/3600, 17 + 0.25*2);
            $sum = $attUtil->getAttendanceSummary($this->ids[$empIndex], '2020-01-08', '2020-01-08');
            $this->assertEquals($sum['r']/3600, 4);
            $sum = $attUtil->getAttendanceSummary($this->ids[$empIndex], '2020-01-09', '2020-01-09');
            $this->assertEquals($sum['r']/3600, 4.25);
            $sum = $attUtil->getAttendanceSummary($this->ids[$empIndex], '2020-01-10', '2020-01-10');
            $this->assertEquals($sum['r']/3600, 1.25);
        } else {
            // Should be the same for both groups
            $this->assertEquals($sum['t']/3600, 17 - 4*2 - 7);
            $this->assertEquals($sum['r']/3600, 17);
        }
    }

    public function testOffOnGroupsBreak()
    {
        $this->checkOffsiteBreak(self::REGULAR_ON_SITE_INDEX, false);
        $this->checkOffsiteBreak(self::FREELANCE_OFF_SITE_INDEX, true);
        $this->checkOffsiteBreak(self::SALES_INDEX, true);
        $this->checkOffsiteBreak(self::REGULAR_OFF_SITE_INDEX, true);
        $this->checkOffsiteBreak(self::FREELANCE_ON_SITE_INDEX, false);
    }

    private function addPunch($empIndex, $in_time, $out_time, $approved_overtime='0')
    {
        $punch = json_decode(json_encode([
            'employee' => $this->ids[$empIndex],
            'in_time' => $in_time,
            'out_time' => $out_time,
            'approved_overtime' => $approved_overtime
        ]));
        $this->actionMgr->savePunch($punch);
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

    private function checkNoOvertime($empIndex)
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
        $this->checkNoOvertime(self::REGULAR_ON_SITE_INDEX);
        $this->checkNoOvertime(self::FREELANCE_OFF_SITE_INDEX);
        $this->checkNoOvertime(self::SALES_INDEX);
        $this->checkNoOvertime(self::REGULAR_OFF_SITE_INDEX);
        $this->checkNoOvertime(self::FREELANCE_ON_SITE_INDEX);
    }

    private function checkRegularOvertime($empIndex)
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
            'out_time' => '2020-01-15 18:00:00',
            'approved_overtime' => '1'
        ]));
        $this->actionMgr->savePunch($punch);

        $sum = $attUtil->getAttendanceSummary($this->ids[$empIndex], '2020-01-01', '2020-01-15');
        $this->assertEquals($sum['t'], (11*8 + 2*7 + 1)*60*60);
        $this->assertEquals($sum['r'], $sum['t'] - 1*60*60);
        $this->assertEquals($sum['o'], 1*60*60);
        $this->assertEquals($sum['d'], 0);
    }

    public function testRegularOvertime()
    {
        $this->checkRegularOvertime(self::REGULAR_ON_SITE_INDEX);
        $this->checkRegularOvertime(self::REGULAR_OFF_SITE_INDEX);
        $this->checkRegularOvertime(self::SALES_INDEX);
    }

    private function checkUndertime($empIndex)
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
        // Punch 1 hour undertime
        $punch = json_decode(json_encode([
            'employee' => $this->ids[$empIndex],
            'in_time' => '2020-01-15 08:00:00',
            'out_time' => '2020-01-15 12:00:00'
        ]));
        $this->actionMgr->savePunch($punch);
        $punch = json_decode(json_encode([
            'employee' => $this->ids[$empIndex],
            'in_time' => '2020-01-15 13:00:00',
            'out_time' => '2020-01-15 16:00:00'
        ]));
        $this->actionMgr->savePunch($punch);

        $sum = $attUtil->getAttendanceSummary($this->ids[$empIndex], '2020-01-01', '2020-01-15');
        $this->assertEquals($sum['r'], (11*8 + 2*7 - 1)*60*60);
        $this->assertEquals($sum['o'], -1*60*60);
        $this->assertEquals($sum['t'], $sum['r'] + $sum['o']);
        $this->assertEquals($sum['d'], 0);
    }

    public function testUndertime()
    {
        $this->checkUndertime(self::REGULAR_ON_SITE_INDEX);
        $this->checkUndertime(self::FREELANCE_OFF_SITE_INDEX);
        $this->checkUndertime(self::SALES_INDEX);
        $this->checkUndertime(self::REGULAR_OFF_SITE_INDEX);
        $this->checkUndertime(self::FREELANCE_ON_SITE_INDEX);
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
            'out_time' => '2020-01-15 18:00:00',
            'approved_overtime' => '1'
        ]));
        $this->actionMgr->savePunch($punch);

        $sum = $attUtil->getAttendanceSummary($this->ids[$empIndex], '2020-01-01', '2020-01-15');
        $this->assertEquals($sum['r'], (10*8 + 1*7)*60*60);
        $this->assertEquals($sum['o'], 1*60*60);
        $this->assertEquals($sum['t'], $sum['r'] + $sum['o']);
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
        $this->assertEquals($sum['r'], 1*60*60);
        $sum = $attUtil->getAttendanceSummary($this->ids[0], '2020-01-02', '2020-01-02');
        $this->assertEquals($sum['r'], 1*60*60);
        $sum = $attUtil->getAttendanceSummary($this->ids[0], '2020-01-03', '2020-01-03');
        $this->assertEquals($sum['r'], 3*15*60);
        $sum = $attUtil->getAttendanceSummary($this->ids[0], '2020-01-04', '2020-01-04');
        $this->assertEquals($sum['r'], 1*60*60);
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
        $this->assertEquals($sum['r'], 8*60*60);
        $sum = $attUtil->getAttendanceSummary($this->ids[$empIndex], '2020-01-02', '2020-01-02');
        $this->assertEquals($sum['r'], 8*60*60);
        $sum = $attUtil->getAttendanceSummary($this->ids[$empIndex], '2020-01-03', '2020-01-03');
        $this->assertEquals($sum['r'], 8*60*60 - 15*60);
    }

    public function testPublicHolidays()
    {
        $attUtil = new AttendanceUtil();
        $empIndex = 0;
        $this->punchRegularDay($empIndex, '2020-01-01');
        $this->punchRegularDay($empIndex, '2020-01-02');

        // Before adding a public holiday
        $sum = $attUtil->getAttendanceSummary($this->ids[$empIndex], '2020-01-01', '2020-01-02');
        $this->assertEquals($sum['r'], 2*8*60*60);
        $this->assertEquals($sum['t'], $sum['r']);
        $this->assertEquals($sum['o'], 0);

        // Add a public holiday and verify one day is gone
        $publicHoliday = new PublicHoliday();
        $publicHoliday->date = '2020-01-01';
        $publicHoliday->note = 'New year';
        $publicHoliday->Save();

        $sum = $attUtil->getAttendanceSummary($this->ids[$empIndex], '2020-01-01', '2020-01-02');
        $this->assertEquals($sum['r'], 1*8*60*60);
        $this->assertEquals($sum['o'], 0); // No overtime granted
        $this->assertEquals($sum['t'], $sum['r']);
    }

    public function testRealWorld()
    {
        $attUtil = new AttendanceUtil();
        $empIndex = self::FREELANCE_OFF_SITE_INDEX;

        $this->addPunch($empIndex, '2020-01-30 07:24', '2020-01-30 17:00');
        $this->addPunch($empIndex, '2020-01-31 07:33', '2020-01-31 17:05');
        $this->addPunch($empIndex, '2020-02-01 07:41', '2020-02-01 16:00');
        $this->addPunch($empIndex, '2020-02-03 07:46', '2020-02-03 17:04');
        $this->addPunch($empIndex, '2020-02-04 07:37', '2020-02-04 17:04');
        $this->addPunch($empIndex, '2020-02-05 06:02', '2020-02-05 17:04', '2');
        //$this->addPunch($empIndex, '2020-02-05 17:05', '2020-02-05 19:05', '2');
        $this->addPunch($empIndex, '2020-02-06 06:01', '2020-02-06 17:03', '2');
        //$this->addPunch($empIndex, '2020-02-06 17:04', '2020-02-06 19:04', '2');
        $this->addPunch($empIndex, '2020-02-07 05:48', '2020-02-07 17:05', '2');
        //$this->addPunch($empIndex, '2020-02-07 17:06', '2020-02-07 19:06', '2');
        $this->addPunch($empIndex, '2020-02-09 13:00', '2020-02-09 15:00', '2'); // Sunday
        $this->addPunch($empIndex, '2020-02-10 07:34', '2020-02-10 17:40', '0.5');
        //$this->addPunch($empIndex, '2020-02-10 17:41', '2020-02-10 17:42');
        $this->addPunch($empIndex, '2020-02-11 07:38', '2020-02-11 17:03');
        $this->addPunch($empIndex, '2020-02-12 07:39', '2020-02-12 17:05');
        $this->addPunch($empIndex, '2020-02-13 06:24', '2020-02-13 17:03', '1.5');
        //$this->addPunch($empIndex, '2020-02-13 17:04', '2020-02-13 18:30', '1.5');

        $sum = $attUtil->getAttendanceSummary($this->ids[$empIndex], '2020-01-30', '2020-02-13');
        $this->assertEquals($sum['r']/3600, 95);
        $this->assertEquals($sum['o']/3600, 10);
        $this->assertEquals($sum['t'], $sum['r'] + $sum['o']);
    }
}
