<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\AccountType;
use App\Enums\PostStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens;

    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'account_type',
        'address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'account_type'      => AccountType::class,
        ];
    }

    /**
     * Vérifier si c'est un compte professionnel
     */
    public function isProfessional(): bool
    {
        return $this->account_type === AccountType::PROFESSIONAL;
    }

    /**
     * Vérifier si c'est un compte privé
     */
    public function isPrivate(): bool
    {
        return $this->account_type === AccountType::PRIVATE;
    }

    /**
     * Relations
     */
    public function professionalProfile(): HasOne
    {
        return $this->hasOne(ProfessionalProfile::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Scopes
     */
    public function scopeProfessional(Builder $query): Builder
    {
        return $query->where('account_type', AccountType::PROFESSIONAL);
    }

    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('account_type', AccountType::PRIVATE);
    }

    /**
     * Get user's published posts
     */
    public function publishedPosts(): HasMany
    {
        return $this->hasMany(Post::class)
            ->where('status', PostStatus::PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Get user's verified companies
     */
    public function verifiedCompanies(): HasMany
    {
        return $this->companies()->where('is_verified', true);
    }
}
