
import dashboard from './dashboard'
import inventoryControl from './inventory-control'
import maintenanceDatabaseSystem from './maintenance-database-system'
import master from './master'
import pressShot from './press-shot'

// import appsAndPages from './apps-and-pages'
// import charts from './charts'
// import forms from './forms'
// import others from './others'
// import uiElements from './ui-elements'

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
    // ...uiElements,
]
