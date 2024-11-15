export const convertPermissions = (userAccess) => {
    try {
        const permissions = typeof userAccess === "string"
            ? JSON.parse(userAccess)
            : userAccess;

        const caslRules = [];

        Object.entries(permissions).forEach(([resource, actions]) => {
            Object.entries(actions).forEach(([action, isAllowed]) => {
                if (isAllowed) {
                    caslRules.push({
                        action,
                        subject: resource,
                    });
                } else {
                    // Explicitly define cannot rules
                    caslRules.push({
                        inverted: true,
                        action,
                        subject: resource,
                    });
                }
            });
        });

        return caslRules;
    } catch (error) {
        console.error("Error converting permissions:", error);
        return [];
    }
};
