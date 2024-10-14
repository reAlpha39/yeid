

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
