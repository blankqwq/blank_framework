<?php


namespace App\Provider;
use App\Models\User;
use App\Observer\UserObserve;
use \Blankphp\Kernel\AppServiceProvider as BaseProvider;

class AppServiceProvider  extends BaseProvider
{
    public function boot()
    {
        parent::boot(); // TODO: Change the autogenerated stub
        User::observe(new UserObserve);
    }

}