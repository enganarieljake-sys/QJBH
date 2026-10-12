<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\StaffAccess;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public const ROLES = ['admin', 'caretaker', 'maintenance', 'tenant'];

    public const STAFF_ROLES = ['admin', 'caretaker', 'maintenance'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'tenant_approved_at',
        'avatar_path',
        'avatar_disk',
        'preferred_room_id',
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
            'tenant_approved_at' => 'datetime',
            'rules_accepted_at' => 'datetime',
        ];
    }

    public function tenant()
    {
        return $this->hasOne(Tenant::class);
    }

    public function preferredRoom()
    {
        return $this->belongsTo(Room::class, 'preferred_room_id');
    }

    public function scopeWithPresence($query)
    {
        return $query->select('users.*')->addSelect([
            'presence_last_activity' => DB::table('sessions')
                ->selectRaw('MAX(last_activity)')
                ->whereColumn('user_id', 'users.id'),
        ]);
    }

    public function lastSeenAt(): ?Carbon
    {
        return $this->presence_last_activity
            ? Carbon::createFromTimestamp((int) $this->presence_last_activity, config('app.timezone'))
            : null;
    }

    public function isOnline(): bool
    {
        return $this->lastSeenAt()?->greaterThanOrEqualTo(now()->subMinutes(5)) ?? false;
    }

    public function presenceLabel(): string
    {
        if ($this->isOnline()) {
            return 'Online now';
        }

        return $this->lastSeenAt() ? 'Last seen '.$this->lastSeenAt()->diffForHumans() : 'Never logged in';
    }

    public function presenceShortLabel(): string
    {
        if ($this->isOnline()) {
            return 'Online';
        }

        $lastSeen = $this->lastSeenAt();
        if (! $lastSeen) {
            return 'Offline';
        }

        $seconds = (int) $lastSeen->diffInSeconds(now());

        return match (true) {
            $seconds < 60 => 'Just now',
            $seconds < 3600 => intdiv($seconds, 60).' min ago',
            $seconds < 86400 => intdiv($seconds, 3600).' hr ago',
            default => intdiv($seconds, 86400).' d ago',
        };
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTenant(): bool
    {
        return $this->role === 'tenant';
    }

    /**
     * Owner, caretaker, or maintenance worker: anyone who works the admin area.
     */
    public function isStaff(): bool
    {
        return in_array($this->role, self::STAFF_ROLES, true);
    }

    public function roleLabel(): string
    {
        return StaffAccess::LABELS[$this->role] ?? ucfirst((string) $this->role);
    }

    /**
     * Staff who should hear about something, e.g. User::staff(['admin', 'caretaker']).
     */
    public static function staff(array $roles)
    {
        return static::whereIn('role', $roles)->get();
    }

    public function isApprovedTenant(): bool
    {
        return $this->isTenant() && $this->tenant_approved_at !== null;
    }

    public function firstName(): string
    {
        return strtok(trim($this->name), ' ') ?: $this->name;
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('') ?: '?';
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path
            ? route('avatars.show', ['user' => $this, 'v' => $this->updated_at?->timestamp])
            : null;
    }

    public function dashboardColor(): string
    {
        $color = Setting::get('dashboard_color_user_'.$this->getKey(), 'blue');

        return in_array($color, ['orange', 'yellow', 'blue', 'gold', 'black', 'green', 'red', 'purple'], true)
            ? $color
            : 'blue';
    }
}
