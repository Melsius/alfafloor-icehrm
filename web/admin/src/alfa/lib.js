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
    ];
  }

  getHeaders() {
    return [
      { sTitle: 'ID', bVisible: false },
      { sTitle: 'Employee' },
      { sTitle: 'Date' },
      { sTitle: 'Measurement (kWh)' },
      { sTitle: 'Details' },
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
module.exports = {
  EmployeeElectricityAdapter,
  IncentiveTypesAdapter
};
