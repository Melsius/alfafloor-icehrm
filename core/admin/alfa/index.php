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
// employee incentives

var modJs = modJsList['tabEmployeeElectricity'];

</script>
<?php include APP_BASE_PATH.'footer.php';?>
