namespace Electricity\Admin\Api

use Electricity\Common\Model\EmployeeElectricity;
use Classes\SettingsManager;

class ElectricityUtil
{
    public function getElectricityUsage($employeeId, $startDate, $endDate)
    {
        $startTime = $startDate." 00:00:00";
        $endTime = $endDate." 23:59:59";
        $eElectricity = new EmployeeElectricity();
        $rows = $eElectricity->Find(
            "employee = ? and date >= ? and date <= ?",
            array($employeeId, $startTime, $endTime)
        );

        return $rows;
    }

    public function getElectricityUsage($employeeId, $startDate, $endDate)
    {
        $measurement = $this->getElectricityUsage($employeeId, $startDate, $endDate);
        return $measurement;
    }
}
