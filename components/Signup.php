<?php namespace Utopigs\MailRelay\Components;

use Validator;
use ValidationException;
use ApplicationException;
use Cms\Classes\ComponentBase;
use Utopigs\MailRelay\Models\Settings;
use Utopigs\MailRelay\Api\MailRelay;

class Signup extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Signup Form',
            'description' => 'Sign up a new person to a mailing list.'
        ];
    }

    public function defineProperties()
    {
        return [
            'list' => [
                'title'       => 'MailRelay List ID',
                'description' => 'In MailRelay account, select List > Tools and look for a List ID.',
                'type'        => 'string'
            ],
            'confirm' => [
                'title'       => 'Double Opt-in',
                'description' => 'Enable confirmation to MailRelay list subscription.',
                'type'        => 'checkbox'
            ],
        ];
    }

    public function onSignup()
    {
        $settings = Settings::instance();
        
        if (!$settings->mailrelay_account_name) {
            throw new ApplicationException('MailRelay account name is not configured.');
        }

        if (!$settings->mailrelay_api_key) {
            throw new ApplicationException('MailRelay API key is not configured.');
        }

        /*
         * Validate input
         */
        $data = post();

        $rules = [
            'email' => 'required|email|min:2|max:64',
        ];

        $validation = Validator::make($data, $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        /*
         * Sign up to MailRelay via the API
         */

        $MailRelay = new MailRelay($settings->mailrelay_account_name, $settings->mailrelay_api_key);

        $this->page['error'] = null;

        $subscriptionData = [
            'email' => post('email'),
            'status' => 'active',
        ];

        if (isset($data['merge']) && is_array($data['merge']) && count($data['merge'])) {
            $subscriptionData['merge_fields'] = $data['merge'];
        }

        $MailRelay->post('subscribers', $subscriptionData);

        if (!$MailRelay->success()) {
            $this->page['error'] = $MailRelay->getLastError();
        }
    }
}
