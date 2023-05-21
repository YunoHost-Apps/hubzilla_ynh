## Droits d'utilisateur de l'administrateur Ldap, journaux et échec des mises à jour de la base de données

- **Pour les droits d'administrateur** : lorsque l'installation est terminée, vous devrez visiter la page de votre nouveau hub et vous connecter avec le **nom d'utilisateur du compte administrateur** qui a été saisi au moment du processus d'installation. Vous devriez alors pouvoir créer votre premier canal et disposer des **droits d'administrateur** pour le hub.

- **Pour les utilisateurs YunoHost normaux** : les utilisateurs LDAP normaux peuvent se connecter via l'authentification LDAP et y créer des canaux.

- **Échec de l'obtention des droits d'administrateur** : si l'administrateur ne peut pas accéder aux paramètres d'administration sur `https://hubzilla.example.com/admin`, vous devez **ajouter manuellement 4096** aux **account_roles* * sous **comptes** pour cet utilisateur dans la **base de données via phpMyAdmin**.

- **Pour les logs** : Allez dans **admin->logs** et saisissez le nom du fichier **php.log**.

- **Échec de la base de données après la mise à niveau :** Parfois, la mise à niveau de la base de données échoue après la mise à niveau de la version. Vous pouvez aller au hub, par exemple. `https://hubzilla.example.com/admin/dbsync/` et vérifiez le nombre de mises à jour défaillantes. Ces mises à jour devront être exécutées manuellement par **phpMyAdmin**.
