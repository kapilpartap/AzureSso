<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Aacotroneo\Saml2\Events\Saml2LoginEvent;

class SAML2ServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Event::listen('Aacotroneo\Saml2\Events\Saml2LoginEvent', function (Saml2LoginEvent $event) {
            $messageId = $event->getSaml2Auth()->getLastMessageId();
            // Add your own code preventing reuse of a $messageId to stop replay attacks

            $user = $event->getSaml2User();
            $userData = [
                'id' => $user->getUserId(),
                'attributes' => $user->getAttributes(),
                'assertion' => $user->getRawSamlAssertion()
            ];

            $inputs = [
                'sso_user_id'  => $user->getUserId(),
                'username'     => self::getValue($user->getAttribute('http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name')),
                'email'        => self::getValue($user->getAttribute('http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name')),
                'first_name'   => self::getValue($user->getAttribute('http://schemas.microsoft.com/identity/claims/displayname')),
                'last_name'    => self::getValue($user->getAttribute('http://schemas.microsoft.com/identity/claims/displayname')),
                'password'     => Hash::make('anything'),
             ];

            $user = User::where('sso_user_id', $inputs['sso_user_id'])->where('email', $inputs['email'])->first();
            if(!$user){
                $res = PortalUser::store($inputs);
                if($res['status'] == 'success'){
                    $user  = $res['data'];
                    Auth::guard('web')->login($user);
                }else{
                    Log::info('SAML USER Error '.$res['messages']);
                }
            }else{
                Auth::guard('web')->login($user);
            }

        });
    }
}
