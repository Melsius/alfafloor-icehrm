import AdapterBase from '../../../api/AdapterBase';

/*
 * EmployeeElectricityAdapter
 */

class EmployeeElectricityAdapter extends AdapterBase {
  getDataMapping() {
    return [
      'id',
      'employee',
      'date',
      'measurement',
      'details',
      'payroll'
    ];
  }

  getHeaders() {
    return [
      { sTitle: 'ID', bVisible: false },
      { sTitle: 'Employee' },
      { sTitle: 'Date' },
      { sTitle: 'Measurement (kWh)' },
      { sTitle: 'Details' },
      { sTitle: 'Payroll' },
    ];
  }

  getFormFields() {
    return [
      ['id', { label: 'ID', type: 'hidden' }],
      ['employee', { label: 'Employee', type: 'select2', 'remote-source': ['Employee', 'id', 'first_name+last_name'] }],
      ['date', { label: 'Date', type: 'date', validation: ''}],
      ['measurement', { label: 'Measurement (kWh)', type: 'text', validation: 'float' }],
      ['details', { label: 'Details', type: 'textarea', validation: 'none' }],
    ];
  }

  getFilters() {
    return [
      ['employee', { label: 'Employee', type: 'select2', 'remote-source': ['Employee', 'id', 'first_name+last_name'] }],
    ];
  }
}

/*
 * IncentiveTypesAdapter
 */

class IncentiveTypesAdapter extends AdapterBase {
  getDataMapping() {
    return [
      'id',
      'name',
      'description'
    ];
  }

  getHeaders() {
    return [
      { sTitle: 'ID', bVisible: false },
      { sTitle: 'Name' },
      { sTitle: 'Description' }
    ];
  }

  getFormFields() {
    return [
      ['id', { label: 'ID', type: 'hidden' }],
      ['name', { label: 'Name', type: 'text', validation: ''}],
      ['description', { label: 'Description', type: 'textarea', validation: 'none' }]
    ];
  }
}

/*
 * EmployeeIncentivesAdapter
 */

class EmployeeIncentivesAdapter extends AdapterBase {
  getDataMapping() {
    return [
      'id',
      'employee',
      'date',
      'incentive_type',
      'amount',
      'pre_paid',
      'details',
      'payroll'
    ];
  }

  getHeaders() {
    return [
      { sTitle: 'ID', bVisible: false },
      { sTitle: 'Employee' },
      { sTitle: 'Date' },
      { sTitle: 'Incentive Type' },
      { sTitle: 'Amount' },
      { sTitle: 'Pre-paid' },
      { sTitle: 'Details' },
      { sTitle: 'Payroll' },
    ];
  }

  getFormFields() {
    return [
      ['id', { label: 'ID', type: 'hidden' }],
      ['employee', { label: 'Employee', type: 'select2', 'remote-source': ['Employee', 'id', 'first_name+last_name'] }],
      ['date', { label: 'Date', type: 'date', validation: ''}],
      ['incentive_type', { label: 'Incentive Type', type: 'select2', 'remote-source': ['IncentiveTypes', 'id', 'name'] }],
      ['amount', { label: 'Amount', type: 'text', validation: 'float' }],
      ['pre_paid', { label: 'Pre-paid', type: 'select', source: [[0, 'No'], [1, 'Yes']], validation: '' }],
      ['details', { label: 'Details', type: 'textarea', validation: 'none' }],
    ];
  }

  getFilters() {
    return [
      ['employee', { label: 'Employee', type: 'select2', 'remote-source': ['Employee', 'id', 'first_name+last_name'] }],
    ];
  }
}
module.exports = {
  EmployeeElectricityAdapter,
  IncentiveTypesAdapter,
  EmployeeIncentivesAdapter
};
