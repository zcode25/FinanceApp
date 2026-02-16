<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements MustVerifyEmail, FilamentUser
{
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin === true;
    }
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'locale', // Added locale since we have the migration
        'avatar', // Added for profile picture
        'is_premium',
        'subscription_until',
        'auto_setup_usage',
        'is_admin',
        'last_login_at',
        'is_active',
        'has_completed_tour',
    ];

    /**
     * The attributes that should be appended to the model's array form.
     *
     * @var array<string, string>
     */
    protected $appends = [
        'current_plan_id',
        'current_plan_name',
        'days_remaining',
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
            'password' => 'hashed',
            'is_premium' => 'boolean',
            'subscription_until' => 'datetime',
            'is_admin' => 'boolean',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'has_completed_tour' => 'boolean',
        ];
    }

    /**
     * Get the transactions for the user.
     */
    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SubscriptionTransaction::class);
    }

    /**
     * Get the latest subscription transaction.
     */
    public function latestTransaction(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SubscriptionTransaction::class)->latestOfMany();
    }

    /**
     * Get the financial transactions for the user.
     */
    public function financialTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the wallets for the user.
     */
    public function wallets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    /**
     * Get the goals for the user.
     */
    public function goals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Goal::class);
    }

    /**
     * Get the budgets for the user.
     */
    public function budgets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * Get the custom categories for the user.
     */
    public function customCategories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Category::class)->whereNotNull('user_id');
    }

    /**
     * Get days remaining for subscription.
     */
    public function getDaysRemainingAttribute(): int
    {
        if (!$this->is_premium)
            return 0;
        if (!$this->subscription_until)
            return 999999;
        return (int) now()->diffInDays($this->subscription_until, false);
    }

    /**
     * Cache for the latest subscription transaction to avoid duplicate queries.
     */
    protected ?SubscriptionTransaction $latestTransactionCache = null;

    protected function getLatestSubscriptionTransaction(): ?SubscriptionTransaction
    {
        if ($this->latestTransactionCache === null) {
            $this->latestTransactionCache = $this->transactions()
                ->where('status', 'success')
                ->with('plan')
                ->latest()
                ->first() ?: false; // Use false to indicate "empty but checked"
        }

        return $this->latestTransactionCache === false ? null : $this->latestTransactionCache;
    }

    /**
     * Get current plan name.
     */
    public function getCurrentPlanNameAttribute(): string
    {
        if (!$this->is_premium)
            return 'Starter';

        $latest = $this->getLatestSubscriptionTransaction();

        return $latest?->plan?->name ?? 'Premium';
    }

    /**
     * Get current plan ID.
     */
    public function getCurrentPlanIdAttribute(): int
    {
        if (!$this->is_premium)
            return 1;

        $latest = $this->getLatestSubscriptionTransaction();

        return $latest ? (int) $latest->plan_id : 1;
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\QueuedVerifyEmail);
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\QueuedResetPassword($token));
    }
}
