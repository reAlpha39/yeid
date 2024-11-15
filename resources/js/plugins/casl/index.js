import { abilitiesPlugin } from '@casl/vue'
import { createMongoAbility } from '@casl/ability'

import { syncAbilityWithCookie, ability } from '@/plugins/casl/ability'

export default function (app) {
  // Sync initial abilities from cookie
  syncAbilityWithCookie()

  app.use(abilitiesPlugin, ability, {
    useGlobalProperties: true,
  })
}
