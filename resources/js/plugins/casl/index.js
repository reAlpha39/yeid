import { useAuthStore } from '@/stores/auth';
import { createMongoAbility } from '@casl/ability';
import { abilitiesPlugin } from '@casl/vue';

export default function (app) {
  const authStore = useAuthStore();
  const initialAbility = createMongoAbility(authStore.getAbilityRules ?? []);

  app.use(abilitiesPlugin, initialAbility, {
    useGlobalProperties: true,
  })
}
