import { createMongoAbility } from '@casl/ability'
import { abilitiesPlugin } from '@casl/vue'

export default function (app) {
  const userAbilityRules = JSON.parse(localStorage.getItem('userAbilityRules'))
  const initialAbility = createMongoAbility(userAbilityRules ?? [])

  // Sync initial abilities from cookie
  // syncAbilityWithCookie()

  app.use(abilitiesPlugin, initialAbility, {
    useGlobalProperties: true,
  })
}
