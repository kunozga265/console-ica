<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A member asking a cell's owner/leaders to add them to the cell. */
class CellJoinRequest extends Model
{
    protected $fillable = ['cell_id', 'user_id', 'member_id', 'message', 'status', 'handled_by'];

    public function cell()
    {
        return $this->belongsTo(Cell::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
