<?php
namespace Reports\Admin\Reports;

use Classes\BaseService;
use Payroll\Common\Model\Payroll;
use Payroll\Common\Model\PayrollColumn;
use Payroll\Common\Model\PayrollData;
use Payroll\Common\Model\PayslipTemplate;
use Reports\Admin\Api\PDFReportBuilder;
use Reports\Admin\Api\PDFReportBuilderInterface;
use Reports\User\Reports\PayslipReport;

class MultiPayslipReport extends PDFReportBuilder implements PDFReportBuilderInterface
{

    public function getData($report, $request)
    {
        $data = $this->getDefaultData();

        $data['payslips'] = array();

        $payroll = new Payroll();
        $payroll->Load("id = ?", array($request['payroll']));

        if (empty($payroll->payslipTemplate)) {
            return null;
        }

        $payslipTemplate = new PayslipTemplate();
        $payslipTemplate->Load("id = ?", array($payroll->payslipTemplate));

        if (empty($payslipTemplate->id)) {
            return null;
        }

        $fields = json_decode($payslipTemplate->data, true);

        $payslipReport = new PayslipReport();

        $payrollData = new PayrollData();
        $payrollData->DB()->SetFetchMode(ADODB_FETCH_ASSOC);
        $query = 'SELECT DISTINCT employee from `PayrollData` WHERE payroll = ?';
        $rs = $payrollData->DB()->Execute($query, array($request['payroll']));
        if (!$rs) {
             \Utils\LogManager::getInstance()->error($payrollData->DB()->ErrorMsg());
             return array("ERROR","Error generating report");
        }
        foreach ($rs as $rowId => $row) {
            $employeeId = $row['employee'];
            $payslipData = array();
            $payslipData['fields'] = $payslipReport->getPayslipData($fields, $request['payroll'], $employeeId);

            $employee = BaseService::getInstance()->getElement(
                'Employee',
                $employeeId,
                null,
                true
            );
            $payslipData['employeeName'] = $employee->first_name.' '.$employee->last_name;
            $data['payslips'][] = $payslipData;
        }
        $data['payroll'] = $payroll;

        return $data;
    }

    public function getTemplate()
    {
        return "multi-payslip.html";
    }
}
