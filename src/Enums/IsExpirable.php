<?php

namespace Javaabu\Cms\Enums;

use Carbon\Carbon;
use Javaabu\Cms\Enums\Languages;

trait IsExpirable
{
    /**
     * Convert dates to Carbon
     *
     * @param $date
     */
    public function setExpireAtAttribute($date)
    {
        $this->attributes['expire_at'] = $date ? Carbon::parse($date) : null;
    }

    /**
     * Checks if expired
     *
     * @return bool
     */
    public function getIsExpiredAttribute()
    {
        if ($this->expire_at) {
            return $this->expire_at < Carbon::now();
        }

        return false;
    }

    /**
     * Checks if never expire
     *
     * @return boolean
     */
    public function getNeverExpireAttribute()
    {
        return empty($this->expire_at);
    }

    public function expiredAtDiff()
    {
        if ($this->expire_at) {
            return $this->expire_at->diffForHumans();
        }

        return __("Does not expire");
    }

    /**
     * Expired scope
     *
     * @param $query
     * @param $status
     * @return mixed
     */
    public function scopeHasStatus($query, $status)
    {
        if ($status == 'expired') {
            return $query->expired();
        } elseif ($status == 'not_expired') {
            return $query->notExpired();
        } else {
            return $query->where($this->getTable() . '.status', $status);
        }
    }

    /**
     * Expired scope
     *
     * @param $query
     * @return mixed
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($query) {
            $query->whereNotNull($this->getTable() . '.expire_at')
                  ->where($this->getTable() . '.expire_at', '<', Carbon::now());
        });
    }

    /**
     * Not expired scope
     *
     * @param $query
     * @return mixed
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($query) {
            $query->whereNull($this->getTable() . '.expire_at')
                  ->orWhere($this->getTable() . '.expire_at', '>=', Carbon::now());
        });
    }

    public function scopeIsActive($query, $active = True)
    {
        $operator = $active ? '>=' : '<=';

        if (! $active) {
            return $query->whereDate('expire_at', $operator, Carbon::now());
        }

        return $query->whereDate('expire_at', $operator, Carbon::now())
                     ->orWhereNull('expire_at');
    }


    public function getFormattedExpireAtAttribute()
    {
        if ($this->is_expired) {
            if (app()->getLocale() == Languages::DV->value) {
                return __('ދުވަސް ހަމަވެފައި');
            }

            return __('Expired');
        }

        if (app()->getLocale() == Languages::DV->value) {
            $days = $this->expire_at->diffInDays();

            if ($days < 1) {
                return __('މުއްދަތު ހަމަވާން :days ގަޑިއިރު', ['days' => $this->expire_at->diffInHours()]);
            }

            return __('މުއްދަތު ހަމަވާން :days ދުވަސް', ['days' => $days]);
        }

        return $this->expire_at->diffForHumans();
    }
}
