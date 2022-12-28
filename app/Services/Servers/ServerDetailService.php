<?php

namespace Convoy\Services\Servers;

use Convoy\Data\Server\Eloquent\ServerEloquentData;
use Convoy\Data\Server\Proxmox\ServerProxmoxData;
use Convoy\Enums\Network\AddressType;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxAllocationRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxCloudinitRepository;
use Illuminate\Support\Arr;

class ServerDetailService
{
    public function __construct(protected ProxmoxAllocationRepository $allocationRepository, protected ProxmoxCloudinitRepository $cloudinitRepository, protected AllocationService $allocationService, protected CloudinitService $cloudinitService)
    {
    }

    public function getByEloquent(Server $server): ServerEloquentData
    {
        $server = $server->loadMissing('addresses');

        $addresses = [
            'ipv4' => $server->addresses->where('type', AddressType::IPV4->value)->toArray(),
            'ipv6' => $server->addresses->where('type', AddressType::IPV6->value)->toArray()
        ];

        return ServerEloquentData::from([
            'id' => $server->id,
            'uuid_short' => $server->uuid_short,
            'uuid' => $server->uuid,
            'node_id' => $server->node_id,
            'hostname' => $server->hostname,
            'name' => $server->name,
            'description' => $server->description,
            'status' => $server->status,
            'usages' => [
                'bandwidth' => $server->bandwidth_usage,
            ],
            'limits' => [
                'cpu' => $server->cpu,
                'memory' => $server->memory,
                'disk' => $server->disk,
                'snapshots' => $server->snapshot_limit,
                'backups' => $server->backup_limit,
                'bandwidth' => $server->bandwidth_limit,
                'addresses' => $addresses,
                'mac_address' => Arr::first($addresses['ipv4'], default: null)?->mac_address ?? Arr::first($addresses['ipv6'], default: null)?->mac_address,
            ]
        ]);
    }

    public function getByProxmox(Server $server): ServerProxmoxData
    {
        $server = $server->loadMissing(['addresses', 'node']);

        $addresses = [
            'ipv4' => $server->addresses->where('type', AddressType::IPV4->value)->toArray(),
            'ipv6' => $server->addresses->where('type', AddressType::IPV6->value)->toArray()
        ];

        $resources = $this->allocationRepository->setServer($server)->getResources();
        $config = $this->cloudinitRepository->setServer($server)->getConfig();

        $mac_address = null;
        if (preg_match("/\b[[:xdigit:]]{2}:[[:xdigit:]]{2}:[[:xdigit:]]{2}:[[:xdigit:]]{2}:[[:xdigit:]]{2}:[[:xdigit:]]{2}\b/su", Arr::get($config, 'net0', ''), $matches)) {
            $mac_address = $matches[0];
        }

        return ServerProxmoxData::from([
            'id' => $server->id,
            'uuid_short' => $server->uuid_short,
            'uuid' => $server->uuid,
            'node_id' => $server->node_id,
            'state' => Arr::get($resources, 'status'),
            'locked' => Arr::get($resources, 'lock', false),
            'config' => [
                'mac_address' => $mac_address,
                'boot_order' => $this->allocationService->getBootOrder($server),
                'disks' => $this->allocationService->getDisks($server),
                'addresses' => $this->cloudinitService->getIpConfig($server),
            ]
        ]);
    }
}
