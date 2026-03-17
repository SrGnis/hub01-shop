<?php

namespace App\Models;

use App\Enums\CollectionSystemType;
use App\Enums\CollectionVisibility;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperCollection
 */
class Collection extends Model
{
    use HasFactory;
    use HasUlids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'collection';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'uid';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'visibility',
        'system_type',
        'hidden_share_token',
    ];

    protected $casts = [
        'visibility' => CollectionVisibility::class,
        'system_type' => CollectionSystemType::class,
    ];

    /**
     * Get the user that owns the collection.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get entries for the collection.
     */
    public function entries(): HasMany
    {
        return $this->hasMany(CollectionEntry::class, 'collection_uid', 'uid')
            ->orderBy('sort_order')
            ->orderBy('uid');
    }

    /**
     * Get projects linked to this collection.
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(
            Project::class,
            'collection_entry',
            'collection_uid',
            'project_id',
            'uid',
            'id'
        )
            ->withPivot(['note', 'sort_order'])
            ->withTimestamps();
    }

    /**
     * Scope query to discoverable collections.
     */
    #[Scope]
    protected function discoverable(Builder $query): void
    {
        $query
            ->where('visibility', CollectionVisibility::PUBLIC)
            ->where(function (Builder $query) {
                $query
                    ->whereNull('system_type')
                    ->orWhere('system_type', '!=', CollectionSystemType::FAVORITES);
            });
    }

    /**
     * Scope query to owner-visible collections.
     */
    #[Scope]
    protected function ownerVisible(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /**
     * Scope query to hidden-token lookup.
     */
    #[Scope]
    protected function hiddenToken(Builder $query, string $token): void
    {
        $query
            ->where('visibility', CollectionVisibility::HIDDEN)
            ->where('hidden_share_token', $token);
    }

    /**
     * Check if collection is public.
     */
    public function isPublic(): bool
    {
        return $this->visibility === CollectionVisibility::PUBLIC;
    }

    /**
     * Check if collection is private.
     */
    public function isPrivate(): bool
    {
        return $this->visibility === CollectionVisibility::PRIVATE;
    }

    /**
     * Check if collection is hidden.
     */
    public function isHidden(): bool
    {
        return $this->visibility === CollectionVisibility::HIDDEN;
    }

    /**
     * Check if collection is a system collection.
     */
    public function isSystem(): bool
    {
        return $this->system_type !== null;
    }

    /**
     * Check if collection is the favorites system collection.
     */
    public function isFavoritesSystemCollection(): bool
    {
        return $this->system_type === CollectionSystemType::FAVORITES;
    }
}
