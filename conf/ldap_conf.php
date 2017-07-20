App::$config['system']['addon'] = 'ldapauth';

App::$config['ldapauth']['ldap_server'] = 'localhost';
App::$config['ldapauth']['ldap_searchdn'] = 'ou=users,dc=yunohost,dc=org';
App::$config['ldapauth']['ldap_userattr'] = 'uid';
App::$config['ldapauth']['ldap_autocreateaccount_emailattribute'] = 'mail';
App::$config['ldapauth']['create_account'] = '1';
