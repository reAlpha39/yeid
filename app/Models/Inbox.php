<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inbox extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_inbox';

    protected $fillable = [
        'source_type',
        'source_id',
        'user_id',
        'title',
        'message',
        'metadata',
        'category',
        'status',
        'read_at',
        'archived_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'read_at' => 'datetime',
        'archived_at' => 'datetime'
    ];

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(MasUser::class);
    }

    public function markAsRead(): bool
    {
        return $this->update([
            'status' => 'read',
            'read_at' => now()
        ]);
    }

    public function archive(): bool
    {
        return $this->update([
            'status' => 'archived',
            'archived_at' => now()
        ]);
    }
}
