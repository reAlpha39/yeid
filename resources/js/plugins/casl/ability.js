import { createMongoAbility } from '@casl/ability'

export const ability = createMongoAbility()

// Plugin to sync ability with your cookie store
export const syncAbilityWithCookie = () => {
    const userAbilityRules = useCookie('userAbilityRules')
    if (userAbilityRules.value) {
        ability.update(userAbilityRules.value)
    }
}
