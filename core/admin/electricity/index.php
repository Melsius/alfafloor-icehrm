<?php

$moduleName = 'electricity';
$moduleGroup = 'admin';
define('MODULE_PATH',dirname(__FILE__));
include APP_BASE_PATH.'header.php';
include APP_BASE_PATH.'modulejslibs.inc.php';
?>
<div class="span9">

	<ul class="nav nav-tabs" id="modTab" style="margin-bottom:0px;margin-left:5px;border-bottom: none;">
		<li class="active"><a id="tabElectricityDashboard" href="#tabPageElectricityDashboard"><?=t('Dashboard')?></a></li>
	</ul>

	<div class="tab-content">
		<div class="tab-pane active" id="tabPageElectricityDashboard">
			<div id="EmployeeElectricity" class="reviewBlock" data-content="List" style="padding-left:5px;">

			</div>
			<div id="EmployeeElectricityForm" class="reviewBlock" data-content="Form" style="padding-left:5px;display:none;">

			</div>
		</div>

	</div>

</div>
<script>
var modJsList = new Array();

modJsList['tabElectricityDashboard'] = new ElectricityDashboardAdapter('EmployeeElectricity');

<?php if(isset($modulePermissions['perm']['Add Electricity Measurement']) && $modulePermissions['perm']['Add Electricity Measurement'] == "No"){?>
modJsList['tabElectricityDashboard'].setShowAddNew(false);
<?php }?>
<?php if(isset($modulePermissions['perm']['Electricity Measurement']) && $modulePermissions['perm']['Electricity Measurement'] == "No"){?>
modJsList['tabElectricityDashboard'].setShowDelete(false);
<?php }?>
<?php if(isset($modulePermissions['perm']['Electricity Measurement']) && $modulePermissions['perm']['Electricity Measurement'] == "No"){?>
modJsList['tabElectricityDashboard'].setShowEdit(false);
<?php }?>

var modJs = modJsList['tabElectricityDashboard'];

</script>
<?php include APP_BASE_PATH.'footer.php';?>
