<?php namespace Utopigs\MailRelay;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'utopigs.mailrelay::lang.plugin.name',
            'description' => 'utopigs.mailrelay::lang.plugin.description',
            'author'      => 'Utopig Studio',
            'icon'        => 'icon-envelope',
            'homepage'    => 'http://utopigstudio.com'
        ];
    }

    public function registerComponents()
    {
        return [
            'Utopigs\MailRelay\Components\Signup' => 'mailSignup'
        ];
    }

    /**
     * Registers administrator permissions for this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'utopigs.mailrelay.configure' => [
                'tab'   => 'utopigs.mailrelay::lang.plugin.name',
                'label' => 'utopigs.mailrelay::lang.permissions.configure',
            ],
        ];
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'utopigs.mailrelay::lang.plugin.name',
                'icon'        => 'icon-envelope',
                'description' => 'utopigs.mailrelay::lang.settings.description',
                'class'       => 'Utopigs\MailRelay\Models\Settings',
                'order'       => 600,
                'permissions' => ['utopigs.mailrelay.configure']
            ]
        ];
    }
}
