import http from '@/api/http'
import { rawDataToAdminServer } from '@/api/admin/servers/getServer'

interface UpdateServerBuildParameters {
    cpu: number
    memory: number
    disk: number
    snapshotLimit: number | null
    backupLimit: number | null
    bandwidthLimit: number | null
    bandwidthUsage: number
}

const updateBuild = async (
    serverUuid: string,
    { snapshotLimit, backupLimit, bandwidthLimit, bandwidthUsage, ...params }: UpdateServerBuildParameters
) => {
    const {
        data: { data },
    } = await http.patch(`/api/admin/servers/${serverUuid}/settings/build`, {
        snapshot_limit: snapshotLimit,
        backup_limit: backupLimit,
        bandwidth_limit: bandwidthLimit,
        bandwidth_usage: bandwidthUsage,
        ...params,
    })

    return rawDataToAdminServer(data)
}

export default updateBuild