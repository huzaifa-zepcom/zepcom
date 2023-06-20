// Import all necessary Storefront plugins
import Config3dPlugin from './config3d-plugin/config3d-plugin.plugin';

// Register your plugin via the existing PluginManager
const PluginManager = window.PluginManager;
PluginManager.register('Config3dPlugin', Config3dPlugin, '[data-config3d-plugin]');