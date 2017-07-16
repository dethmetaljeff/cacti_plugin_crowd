# Crowd Auth

This plugin allows you to authenticate against and Atlassian Crowd server.

# Features

Integrates with an Atlassian Crowd server to enable SSO for cacti.

# Installation

To install the plugin, copy the plugin_crowd  directory to Cacti's plugins directory and rename it to 'crowd'.  Once this is complete, go to Cacti's Plugin Management section and Install and Enable the plugin.  Once this is done, navigate over to the Authentication tab in settings and configure your Crowd server's Hostname, Application Username, Application User Password and SSO Domain.  After confirming the connection, select "Crowd" from the Authentication Dropdown and you're done.

# Requirements

This plugin requires you have already installed and configured an Atlassian Crowd server.  It also requires the Services_Atlassian_Crowd pear module be installed on your cacti server.  This module can be found here:  http://pear.php.net/package/Services_Atlassian_Crowd

# Bugs?

Nothing I'm aware of.  Let me know if you find anything.

# Future Changes

Got any ideas or complaints, please e-mail me!

# Changelog

	--- 0.1 ---
	Initial release


