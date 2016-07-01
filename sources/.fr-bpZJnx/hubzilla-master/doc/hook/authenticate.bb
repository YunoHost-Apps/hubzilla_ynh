[h2]authenticate[/h2]

Invoked when a POST request is made with non-null $_POST['auth-params'] such as from the login form.
If the hook handler does not set the 'authenticated' parameter of the passed array, normal login functions continue;

The 'user_record' is in fact an account DB record. To provide automatic provisioning of accounts from other authentication realms, this record should be generated and stored during the verification phase.  


[code]
 $addon_auth = array(
            'username' => trim($_POST['username']),
            'password' => trim($_POST['password']),
            'authenticated' => 0,
            'user_record' => null
        );

        /**
         *
         * A plugin indicates successful login by setting 'authenticated' to non-zero value and returning a user record
         * Plugins should never set 'authenticated' except to indicate success - as hooks may be chained
         * and later plugins should not interfere with an earlier one that succeeded.
         *
         */

        call_hooks('authenticate', $addon_auth);
[/code]


See include/auth.php
