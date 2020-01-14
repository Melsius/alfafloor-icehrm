<?php

$moduleName = 'alfa';
$moduleGroup = 'admin';
define('MODULE_PATH',dirname(__FILE__));
include APP_BASE_PATH.'header.php';
include APP_BASE_PATH.'modulejslibs.inc.php';
?>
<div class="span9">

	<ul class="nav nav-tabs" id="modTab" style="margin-bottom:0px;margin-left:5px;border-bottom: none;">
		<li class="active"><a id="tabEmployeeElectricity" href="#tabPageEmployeeElectricity"><?=t('Electricity')?></a></li>
		<li><a id="tabIncentiveTypes" href="#tabPageIncentiveTypes"><?=t('Incentive Types')?></a></li>
		<li><a id="tabEmployeeIncentives" href="#tabPageEmployeeIncentives"><?=t('Employee Incentives')?></a></li>
		<li><a id="tabDeductionTypes" href="#tabPageDeductionTypes"><?=t('Deduction Types')?></a></li>
		<li><a id="tabEmployeeDeductions" href="#tabPageEmployeeDeductions"><?=t('Employee Deductions')?></a></li>
		<li><a id="tabPublicHolidays" href="#tabPagePublicHolidays"><?=t('Public Holidays')?></a></li>
	</ul>

	<div class="tab-content">
		<div class="tab-pane active" id="tabPageEmployeeElectricity">
			<div id="EmployeeElectricity" class="reviewBlock" data-content="List" style="padding-left:5px;">

			</div>
			<div id="EmployeeElectricityForm" class="reviewBlock" data-content="Form" style="padding-left:5px;display:none;">

			</div>
		</div>

		<div class="tab-pane" id="tabPageIncentiveTypes">
			<div id="IncentiveTypes" class="reviewBlock" data-content="List" style="padding-left:5px;">
			</div>
			<div id="IncentiveTypesForm" class="reviewBlock" data-content="Form" style="padding-left:5px;display:none;">
			</div>
        </div>

		<div class="tab-pane" id="tabPageEmployeeIncentives">
			<div id="EmployeeIncentives" class="reviewBlock" data-content="List" style="padding-left:5px;">
			</div>
			<div id="EmployeeIncentivesForm" class="reviewBlock" data-content="Form" style="padding-left:5px;display:none;">
			</div>
        </div>

		<div class="tab-pane" id="tabPageDeductionTypes">
			<div id="DeductionTypes" class="reviewBlock" data-content="List" style="padding-left:5px;">
			</div>
			<div id="DeductionTypesForm" class="reviewBlock" data-content="Form" style="padding-left:5px;display:none;">
			</div>
        </div>

		<div class="tab-pane" id="tabPageEmployeeDeductions">
			<div id="EmployeeDeductions" class="reviewBlock" data-content="List" style="padding-left:5px;">
			</div>
			<div id="EmployeeDeductionsForm" class="reviewBlock" data-content="Form" style="padding-left:5px;display:none;">
			</div>
		</div>

		<div class="tab-pane" id="tabPagePublicHolidays">
			<div id="PublicHoliday" class="reviewBlock" data-content="List" style="padding-left:5px;">
			</div>
			<div id="PublicHolidayForm" class="reviewBlock" data-content="Form" style="padding-left:5px;display:none;">
			</div>
		</div>
	</div>

</div>
<script>
var modJsList = new Array();

modJsList['tabEmployeeElectricity'] = new EmployeeElectricityAdapter('EmployeeElectricity');

<?php if(isset($modulePermissions['perm']['Add Electricity Measurement']) && $modulePermissions['perm']['Add Electricity Measurement'] == "No"){?>
modJsList['tabEmployeeElectricity'].setShowAddNew(false);
<?php }?>
<?php if(isset($modulePermissions['perm']['Electricity Measurement']) && $modulePermissions['perm']['Electricity Measurement'] == "No"){?>
modJsList['tabEmployeeElectricity'].setShowDelete(false);
<?php }?>
<?php if(isset($modulePermissions['perm']['Electricity Measurement']) && $modulePermissions['perm']['Electricity Measurement'] == "No"){?>
modJsList['tabEmployeeElectricity'].setShowEdit(false);
<?php }?>

// incentive types
modJsList['tabIncentiveTypes'] = new IncentiveTypesAdapter('IncentiveTypes');
modJsList['tabIncentiveTypes'].setShowAddNew(false);
modJsList['tabIncentiveTypes'].setShowDelete(false);
modJsList['tabIncentiveTypes'].setShowEdit(false);

// employee incentives
modJsList['tabEmployeeIncentives'] = new EmployeeIncentivesAdapter('EmployeeIncentives');
// TODO: remove edid/delete button for rows with a payroll
modJsList['tabEmployeeIncentives'].setShowAddNew(true);
modJsList['tabEmployeeIncentives'].setShowDelete(true);
modJsList['tabEmployeeIncentives'].setShowEdit(true);

// deduction types
modJsList['tabDeductionTypes'] = new DeductionTypesAdapter('DeductionTypes');
modJsList['tabDeductionTypes'].setShowAddNew(false);
modJsList['tabDeductionTypes'].setShowDelete(false);
modJsList['tabDeductionTypes'].setShowEdit(false);

// employee deductions 
modJsList['tabEmployeeDeductions'] = new EmployeeDeductionsAdapter('EmployeeDeductions');
// TODO: remove edid/delete button for rows with a payroll
modJsList['tabEmployeeDeductions'].setShowAddNew(true);
modJsList['tabEmployeeDeductions'].setShowDelete(true);
modJsList['tabEmployeeDeductions'].setShowEdit(true);

// public holidays
modJsList['tabPublicHolidays'] = new PublicHolidayAdapter('PublicHoliday');
modJsList['tabPublicHolidays'].setShowAddNew(true);
modJsList['tabPublicHolidays'].setShowDelete(true);
modJsList['tabPublicHolidays'].setShowEdit(true);

var modJs = modJsList['tabEmployeeElectricity'];

</script>
<?php include APP_BASE_PATH.'footer.php';?>
