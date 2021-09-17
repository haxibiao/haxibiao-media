<?php
namespace Haxibiao\Media;

use App\Movie;
use App\User;
use Haxibiao\Breeze\Model;
use Haxibiao\Breeze\Traits\HasFactory;
use Haxibiao\Media\Traits\MovieRoomRepo;
use Haxibiao\Media\Traits\MovieRoomResolvers;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MovieRoom extends Model
{
    use HasFactory;
    use SoftDeletes;
    use MovieRoomResolvers;
    use MovieRoomRepo;

    protected $guarded = [];

    protected $casts = [
        'uids' => 'array',
    ];

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
