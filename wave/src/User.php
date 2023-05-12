<?php

namespace Wave;

use App\Traits\SubscriptionsTrait;
use Carbon\Carbon;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Lab404\Impersonate\Models\Impersonate;
use TCG\Voyager\Models\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Wave\Announcement;
use Wave\PaddleSubscription;
use Wave\Plan;
use Wave\ApiToken;
use Laravel\Cashier\Billable as StripeBillable;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable, Impersonate;
    use SubscriptionsTrait, StripeBillable {
        SubscriptionsTrait::subscribed insteadof StripeBillable;
        SubscriptionsTrait::subscription insteadof StripeBillable;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', 'email', 'username', 'password', 'verification_code', 'verified', 'trial_ends_at', 'role_id', 'stripe_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'trial_ends_at' => 'datetime',
    ];

    public function keyValues()
    {
        return $this->morphMany('Wave\KeyValue', 'keyvalue');
    }

    public function keyValue($key){
        return $this->morphMany('Wave\KeyValue', 'keyvalue')->where('key', '=', $key)->first();
    }

    public function profile($key){
        $keyValue = $this->keyValue($key);
        return isset($keyValue->value) ? $keyValue->value : '';
    }

    public function onTrial(){
        if( is_null($this->trial_ends_at) ){
            return false;
        }
        return true;
    }

    public function subscriber(){

        if($this->hasRole('admin')) return true;

        $roles = $this->roles->pluck('id')->push( $this->role_id )->unique();
        $plans = Plan::whereIn('role_id', $roles)->count();

        // If the user has a role that belongs to a plan
        if($plans){
            return true;
        }

        return false;
    }

    public function getCurrentSubscriptionName()
    {
        return $this->role->display_name;
    }

    /**
     * @return bool
     */
    public function canImpersonate()
    {
        // If user is admin they can impersonate
        return $this->hasRole('admin');
    }

    /**
     * @return bool
     */
    public function canBeImpersonated()
    {
        // Any user that is not an admin can be impersonated
        return !$this->hasRole('admin');
    }

    public function hasAnnouncements(){
        // Get the latest Announcement
        $latest_announcement = Announcement::orderBy('created_at', 'DESC')->first();

        if(!$latest_announcement) return false;
        return !$this->announcements->contains($latest_announcement->id);
    }

    public function announcements(){
        return $this->belongsToMany('Wave\Announcement');
    }

    public function createApiKey($name){
        return ApiKey::create(['user_id' => $this->id, 'name' => $name, 'key' => Str::random(60)]);
    }

    public function apiKeys(){
        return $this->hasMany('Wave\ApiKey')->orderBy('created_at', 'DESC');
    }

    public function daysLeftOnTrial(){
        if($this->trial_ends_at && $this->trial_ends_at >= now()){
            $trial_ends = Carbon::parse($this->trial_ends_at)->addDay();
            return $trial_ends->diffInDays(now());
        }
        return 0;
    }

    public function avatar(){
        return Storage::url($this->avatar);
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
