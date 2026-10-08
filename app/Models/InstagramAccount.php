<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstagramAccount extends Model
{
    protected $fillable = ['name', 'instagram_user_id', 'username', 'facebook_page_id', 'access_token', 'auth_mode', 'is_active'];

    protected function casts(): array
    {
        return ['access_token' => 'encrypted', 'is_active' => 'boolean'];
    }

    public function rules(): HasMany
    {
        return $this->hasMany(AutomationRule::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }
}
