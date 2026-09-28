## Installation
Avant l'installation, lisez les [instructions d'installation de Hubzilla](https://framagit.org/hubzilla/core/blob/master/install/INSTALL.txt) pour obtenir des informations importantes sur :

### Enregistrez un nouveau domaine et ajoutez-le à YunoHost
- Hubzilla nécessite un domaine dédié, alors obtenez-en un et ajoutez-le à l'aide du panneau d'administration YunoHost. **Domaines -> Ajouter un domaine**. Comme Hubzilla utilise le domaine complet et est installé à la racine, vous pouvez créer un sous-domaine tel que hubzilla.domain.tld. N'oubliez pas de mettre à jour vos DNS si vous les gérez manuellement.

### Droits d'utilisateur de l'administrateur, journaux et échec des mises à jour de la base de données

- **Compte administrateur** : lorsque l'installation est terminée, vous devez vous rendre sur la page d'accueil de votre nouveau site et créer le premier compte en utilisant **l'adresse électronique de l' administrateur** (l'administrateur est l'utilisateur YunoHost qui a été choisi au début du processus d'installation). Vous pouvez ensuite créer votre premier canal et accéder à **l'interface d'administration** de votre site.

- **Échec de l'obtention des droits d'administrateur** : si l'administrateur ne peut pas accéder aux paramètres d'administration sur `https://hubzilla.example.com/admin`, vous devez **ajouter manuellement 4096** aux **account_roles* * sous **comptes** pour cet utilisateur dans la **base de données via phpMyAdmin**.

- **Pour les logs** : Allez dans **admin->logs** et saisissez le nom du fichier **php.log**.

- **Échec de la base de données après la mise à niveau :** Parfois, la mise à niveau de la base de données échoue après la mise à niveau de la version. Vous pouvez aller au hub, par exemple. `https://hubzilla.example.com/admin/dbsync/` et vérifiez le nombre de mises à jour défaillantes. Ces mises à jour devront être exécutées manuellement par **phpMyAdmin**.
