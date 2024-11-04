
import dashboard from './dashboard'
import inventoryControl from './inventory-control'
import maintenanceDatabaseSystem from './maintenance-database-system'
import master from './master'
import pressShot from './press-shot'

export default [
    ...dashboard,
    ...master,
    ...inventoryControl,
    ...pressShot,
    ...maintenanceDatabaseSystem,
    
    // ...dashboards,
    // ...appsAndPages,
    // ...charts,
    // ...forms,
    // ...others,
    // ...uiElement,
]
