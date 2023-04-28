<?php

namespace Convoy\Repositories\Eloquent;

use Convoy\Contracts\Repository\ActivityRepositoryInterface;
use Convoy\Models\ActivityLog;
use Convoy\Models\Server;

class ActivityRepository extends EloquentRepository implements ActivityRepositoryInterface
{
    /**
     * @return string
     */
    public function model(): string
    {
        return ActivityLog::class;
    }

    public function getServer(ActivityLog $activity): ?Server
    {
        return $activity->subjects()->firstWhere('subject_type', (new Server)->getMorphClass())?->subject()->first();
    }
}
