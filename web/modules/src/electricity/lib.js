import AdapterBase from '../../../api/AdapterBase';

/*
 * ElectricityDashboardAdapter
 */

class ElectricityDashboardAdapter extends AdapterBase {
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
      { sTitle: 'Measurement (KWh)' },
      { sTitle: 'Details' },
    ];
  }

  getFormFields() {
    return [
      ['id', { label: 'ID', type: 'hidden' }],
      ['employee', { label: 'Employee', type: 'select2', 'remote-source': ['Employee', 'id', 'first_name+last_name'] }],
      ['date', { label: 'Date', type: 'date', validation: ''}],
      ['measurement', { label: 'Measurement (KWh)', type: 'text', validation: 'float' }],
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
  ElectricityDashboardAdapter
};
