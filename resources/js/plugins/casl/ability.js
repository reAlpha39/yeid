import { createMongoAbility } from '@casl/ability'

export const ability = createMongoAbility()

// Plugin to sync ability with your cookie store
export const syncAbilityWithCookie = () => {
    const userAbilityRules = JSON.parse(localStorage.getItem('userAbilityRules'))
    if (userAbilityRules) {
        ability.update(userAbilityRules)
    }
}
