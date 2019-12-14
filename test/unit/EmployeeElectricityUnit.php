<?php
namespace unit;

use Electricity\Admin\Api\ElectricityUtil;
use Electricity\Common\Model\EmployeeElectricity;
use Classes\BaseService;

class EmployeeElectricityUnit extends \TestTemplate
{

    protected $eUtil;
    protected function setUp()
    {
        parent::setUp();

    }

    public function testNoResults()
    {
        $stub = $this->createMock(EmployeeElectricity::class);
        $stub->method('Find')
            ->willReturn([]);

        echo get_class($stub);
        $this->eUtil = new ElectricityUtil($stub);
        $this->assertSame(0, $this->eUtil->getElectricityUsage(0, "", ""));
    }

    public function testNoPrevMeasurement()
    {
        $stub = $this->createMock(EmployeeElectricity::class);
        $stub->method('Find')
            ->will($this->onConsecutiveCalls([],
				[
					['id' => 1, 'employee' => 1, 'measurement' => 100, 'date' => '2010-01-01', 'is_paid' => 0, 'details' => ''],
					['id' => 1, 'employee' => 1, 'measurement' => 200, 'date' => '2010-01-01', 'is_paid' => 0, 'details' => '']
			]));

        echo get_class($stub);
        $this->eUtil = new ElectricityUtil($stub);
        $this->assertSame(100, $this->eUtil->getElectricityUsage(0, "", ""));
    }

    public function testPrevMeasurementSame()
    {
        $stub = $this->createMock(EmployeeElectricity::class);
        $stub->method('Find')
            ->will($this->onConsecutiveCalls(
				[
					['id' => 1, 'employee' => 1, 'measurement' => 100, 'date' => '2010-01-01', 'is_paid' => 0, 'details' => ''],
				],
				[
					['id' => 1, 'employee' => 1, 'measurement' => 100, 'date' => '2010-01-01', 'is_paid' => 0, 'details' => ''],
					['id' => 1, 'employee' => 1, 'measurement' => 200, 'date' => '2010-01-01', 'is_paid' => 0, 'details' => '']
			]));

        echo get_class($stub);
        $this->eUtil = new ElectricityUtil($stub);
        $this->assertSame(100, $this->eUtil->getElectricityUsage(0, "", ""));
    }

    public function testPrevMeasurementLower()
    {
        $stub = $this->createMock(EmployeeElectricity::class);
        $stub->method('Find')
            ->will($this->onConsecutiveCalls(
				[
					['id' => 1, 'employee' => 1, 'measurement' => 99, 'date' => '2010-01-01', 'is_paid' => 0, 'details' => ''],
				],
				[
					['id' => 1, 'employee' => 1, 'measurement' => 100, 'date' => '2010-01-01', 'is_paid' => 0, 'details' => ''],
					['id' => 1, 'employee' => 1, 'measurement' => 200, 'date' => '2010-01-01', 'is_paid' => 0, 'details' => ''],
					['id' => 1, 'employee' => 1, 'measurement' => 201, 'date' => '2010-01-01', 'is_paid' => 0, 'details' => '']
			]));

        echo get_class($stub);
        $this->eUtil = new ElectricityUtil($stub);
        $this->assertSame(102, $this->eUtil->getElectricityUsage(0, "", ""));
    }

    public function testOnlyPrevMeasurement()
    {
        $stub = $this->createMock(EmployeeElectricity::class);
        $stub->method('Find')
            ->will($this->onConsecutiveCalls(
				[
					['id' => 1, 'employee' => 1, 'measurement' => 99, 'date' => '2010-01-01', 'is_paid' => 0, 'details' => ''],
				],
				[]));

        echo get_class($stub);
        $this->eUtil = new ElectricityUtil($stub);
        $this->assertSame(0, $this->eUtil->getElectricityUsage(0, "", ""));
    }

    public function testOneMeasurement()
    {
        $stub = $this->createMock(EmployeeElectricity::class);
        $stub->method('Find')
            ->will($this->onConsecutiveCalls(
				[
					['id' => 1, 'employee' => 1, 'measurement' => 99, 'date' => '2010-01-01', 'is_paid' => 0, 'details' => ''],
				],
				[
					['id' => 1, 'employee' => 1, 'measurement' => 100, 'date' => '2010-01-01', 'is_paid' => 0, 'details' => ''],
				]));

        echo get_class($stub);
        $this->eUtil = new ElectricityUtil($stub);
        $this->assertSame(1, $this->eUtil->getElectricityUsage(0, "", ""));
    }
}
