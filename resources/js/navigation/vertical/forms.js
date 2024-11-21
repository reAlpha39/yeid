export default [
  { heading: 'Forms & Tables' },
  {
    title: 'Form Elements',
    icon: { icon: 'tabler-checkbox' },

    children: [
      {
        title: 'Autocomplete', to: 'forms-autocomplete', action: 'view',
        subject: 'user',
      },
      {
        title: 'Checkbox', to: 'forms-checkbox', action: 'view',
        subject: 'user',
      },
      {
        title: 'Combobox', to: 'forms-combobox', action: 'view',
        subject: 'user',
      },
      {
        title: 'Date Time Picker', to: 'forms-date-time-picker', action: 'view',
        subject: 'user',
      },
      {
        title: 'Editors', to: 'forms-editors', action: 'view',
        subject: 'user',
      },
      {
        title: 'File Input', to: 'forms-file-input', action: 'view',
        subject: 'user',
      },
      {
        title: 'Radio', to: 'forms-radio', action: 'view',
        subject: 'user',
      },
      {
        title: 'Custom Input', to: 'forms-custom-input', action: 'view',
        subject: 'user',
      },
      {
        title: 'Range Slider', to: 'forms-range-slider', action: 'view',
        subject: 'user',
      },
      {
        title: 'Rating', to: 'forms-rating', action: 'view',
        subject: 'user',
      },
      {
        title: 'Select', to: 'forms-select', action: 'view',
        subject: 'user',
      },
      {
        title: 'Slider', to: 'forms-slider', action: 'view',
        subject: 'user',
      },
      {
        title: 'Switch', to: 'forms-switch', action: 'view',
        subject: 'user',
      },
      {
        title: 'Textarea', to: 'forms-textarea', action: 'view',
        subject: 'user',
      },
      {
        title: 'Textfield', to: 'forms-textfield', action: 'view',
        subject: 'user',
      },
    ],
  },
  {
    title: 'Form Layouts',
    icon: { icon: 'tabler-layout' },
    to: 'forms-form-layouts',
    action: 'view',
    subject: 'user',
  },
  {
    title: 'Form Wizard',
    icon: { icon: 'tabler-git-merge' },
    children: [
      {
        title: 'Numbered', to: 'forms-form-wizard-numbered', action: 'view',
        subject: 'user',
      },
      {
        title: 'Icons', to: 'forms-form-wizard-icons', action: 'view',
        subject: 'user',
      },
    ],
  },
  {
    title: 'Form Validation',
    icon: { icon: 'tabler-checkup-list' },
    to: 'forms-form-validation',
    action: 'view',
    subject: 'user',
  },
  {
    title: 'Tables',
    icon: { icon: 'tabler-table' },
    children: [
      {
        title: 'Simple Table', to: 'tables-simple-table', action: 'view',
        subject: 'user',
      },
      {
        title: 'Data Table', to: 'tables-data-table', action: 'view',
        subject: 'user',
      },
    ],
  },
]
