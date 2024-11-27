export function useActivityLog() {
    const logActivity = async (page, action, description) => {
        try {
            await $api('/log-activity', {
                page,
                action,
                description
            })
        } catch (error) {
            console.error('Failed to log activity:', error)
        }
    }

    return {
        logActivity
    }
}
