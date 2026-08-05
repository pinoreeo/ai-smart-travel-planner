<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'trp_user_roles',
            'user_id',
            'role_id'
        )->withTimestamps();
    }

    public function createdPlaces(): HasMany
    {
        return $this->hasMany(
            Place::class,
            'created_by'
        );
    }

    public function updatedPlaces(): HasMany
    {
        return $this->hasMany(
            Place::class,
            'updated_by'
        );
    }

    public function uploadedPlaceImages(): HasMany
    {
        return $this->hasMany(
            PlaceImage::class,
            'uploaded_by'
        );
    }

    public function itineraries(): HasMany
    {
        return $this->hasMany(
            Itinerary::class,
            'user_id'
        );
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(
            Favorite::class,
            'user_id'
        );
    }

    public function aiRequests(): HasMany
    {
        return $this->hasMany(
            AiRequest::class,
            'user_id'
        );
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()
            ->where('slug', $role)
            ->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()
            ->whereIn('slug', $roles)
            ->exists();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
