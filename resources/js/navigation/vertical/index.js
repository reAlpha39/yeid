// import appsAndPages from './apps-and-pages'
// import charts from './charts'
// import dashboards from './dashboards'
// import forms from './forms'
// import others from './others'
// import uiElement from './ui-elements'

import dashboard from './dashboard'
import inventoryControl from './inventory-control'
import maintenanceDatabaseSystem from './maintenance-database-system'
import master from './master'

export default [
    ...dashboard,
    ...master,
    ...inventoryControl,
    ...maintenanceDatabaseSystem,
    
    // ...dashboards,
    // ...appsAndPages,
    // ...charts,
    // ...forms,
    // ...others,
    // ...uiElement,
]
