<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtStatusAudit extends Model
{
    public $timestamps = false;

    protected $table = 'court_status_audits';

    protected $fillable = [
        'court_id',
        'previous_status',
        'new_status',
        'reason',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
}
