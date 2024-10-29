<?php

/**
 * Plugin Name: Automatic Updates Enabled
 * Plugin URI:
 * Description: Enables WordPress automatic updates by default for newly installed and activated plugins
 * Version: 1.0
 * Author: Piotr Moćko
 * Author URI:
 * License: GNU/GPL https://www.gnu.org/licenses/gpl-3.0.html
 */

// No direct access
function_exists('add_action') or die;

class AutomaticUpdatesEnabled
{
    /**
     * The option name with the list of installed plugins
     * @var string
     */
    protected $option_name = 'installed_plugins';

    public function __construct()
    {
        /** @since WordPress 2.9.0 */
        add_action('activate_plugin', array($this, 'activate_plugin_hook'));
    }

    /**
     * Fires before a plugin is activated.
     *
     * If a plugin is silently activated (such as during an update),
     * this hook does not fire.
     *
     * @param string $plugin       Path to the plugin file relative to the plugins directory.
     * @param bool   $network_wide Whether to enable the plugin for all sites in the network
     *                             or just the current site. Multisite only. Default false.
     */
    public function activate_plugin_hook($plugin = '', $network_wide = false)
    {
        if (plugin_basename(__FILE__) === $plugin) {
            $this->init_list_of_installed_plugins();
            $this->enable_plugin_auto_updates($plugin);
            return;
        }

        if (!$plugin || !$this->is_new_plugin($plugin)) {
            return;
        }

        $this->add_plugin_to_list_of_installed_plugins($plugin);
        $this->enable_plugin_auto_updates($plugin);
    }

    /**
     * Initialise the list of installed plugins
     *
     * @return bool True on success, false otherwise.
     */
    protected function init_list_of_installed_plugins()
    {
        $all_plugins = array_keys(apply_filters('all_plugins', get_plugins()));

        return update_site_option($this->option_name, $all_plugins);
    }

    /**
     * Check if the plugin is on the list of installed plugins
     *
     * @param string $plugin Path to the plugin file relative to the plugins directory.
     *
     * @return bool True if the plugin is new, false otherwise.
     */
    protected function is_new_plugin($plugin)
    {
        $installed_plugins = (array) get_site_option($this->option_name, array());

        return !in_array($plugin, $installed_plugins);
    }

    /**
     * Add the plugin to the list of installed plugins
     *
     * @param string $plugin Path to the plugin file relative to the plugins directory.
     *
     * @return bool True on success, false otherwise.
     */
    protected function add_plugin_to_list_of_installed_plugins($plugin)
    {
        $installed_plugins = (array) get_site_option($this->option_name, array());

        $installed_plugins[] = $plugin;
        $installed_plugins   = array_unique($installed_plugins);

        $all_plugins = apply_filters('all_plugins', get_plugins());

        // Remove plugins that don't exist or have been deleted since the option was last updated.
        $installed_plugins = array_intersect($installed_plugins, array_keys($all_plugins));

        return update_site_option($this->option_name, $installed_plugins);
    }

    /**
     * Enable WordPress Automatic Updates for the plugin
     *
     * @param string $plugin Path to the plugin file relative to the plugins directory.
     *
     * @return bool True on success, false otherwise.
     */
    public function enable_plugin_auto_updates($plugin)
    {
        $auto_updates = (array) get_site_option('auto_update_plugins', array());

        $auto_updates[] = $plugin;
        $auto_updates   = array_unique($auto_updates);

        /** This filter is documented in wp-admin/includes/class-wp-plugins-list-table.php */
        $all_plugins = apply_filters('all_plugins', get_plugins());

        // Remove plugins that don't exist or have been deleted since the option was last updated.
        $auto_updates = array_intersect($auto_updates, array_keys($all_plugins));

        return update_site_option('auto_update_plugins', $auto_updates);
    }
}

new AutomaticUpdatesEnabled();
