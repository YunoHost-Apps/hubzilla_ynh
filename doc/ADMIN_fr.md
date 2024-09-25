## Installation
Avant l'installation, lisez les [instructions d'installation de Hubzilla](https://framagit.org/hubzilla/core/blob/master/install/INSTALL.txt) pour obtenir des informations importantes sur :

### Enregistrez un nouveau domaine et ajoutez-le à YunoHost
- Hubzilla nécessite un domaine dédié, alors obtenez-en un et ajoutez-le à l'aide du panneau d'administration YunoHost. **Domaines -> Ajouter un domaine**. Comme Hubzilla utilise le domaine complet et est installé à la racine, vous pouvez créer un sous-domaine tel que hubzilla.domain.tld. N'oubliez pas de mettre à jour vos DNS si vous les gérez manuellement.

## Droits d'utilisateur de l'administrateur Ldap, journaux et échec des mises à jour de la base de données

- **Pour les droits d'administrateur** : lorsque l'installation est terminée, vous devrez visiter la page de votre nouveau hub et vous connecter avec le **nom d'utilisateur du compte administrateur** qui a été saisi au moment du processus d'installation. Vous devriez alors pouvoir créer votre premier canal et disposer des **droits d'administrateur** pour le hub.

- **Pour les utilisateurs YunoHost normaux** : les utilisateurs LDAP normaux peuvent se connecter via l'authentification LDAP et y créer des canaux.

- **Échec de l'obtention des droits d'administrateur** : si l'administrateur ne peut pas accéder aux paramètres d'administration sur `https://hubzilla.example.com/admin`, vous devez **ajouter manuellement 4096** aux **account_roles* * sous **comptes** pour cet utilisateur dans la **base de données via phpMyAdmin**.

- **Pour les logs** : Allez dans **admin->logs** et saisissez le nom du fichier **php.log**.
