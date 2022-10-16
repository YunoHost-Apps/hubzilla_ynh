## Installation
Avant l'installation, lisez les [instructions d'installation de Hubzilla](https://framagit.org/hubzilla/core/blob/master/install/INSTALL.txt) pour obtenir des informations importantes sur�:

### Enregistrez un nouveau domaine et ajoutez-le � YunoHost
- Hubzilla n�cessite un domaine d�di�, alors obtenez-en un et ajoutez-le � l'aide du panneau d'administration YunoHost. **Domaines -> Ajouter un domaine**. Comme Hubzilla utilise le domaine complet et est install� � la racine, vous pouvez cr�er un sous-domaine tel que hubzilla.domain.tld. N'oubliez pas de mettre � jour vos DNS si vous les g�rez manuellement.

##�Droits d'utilisateur de l'administrateur Ldap, journaux et �chec des mises � jour de la base de donn�es

- **Pour les droits d'administrateur**�: lorsque l'installation est termin�e, vous devrez visiter la page de votre nouveau hub et vous connecter avec le **nom d'utilisateur du compte administrateur** qui a �t� saisi au moment du processus d'installation. Vous devriez alors pouvoir cr�er votre premier canal et disposer des **droits d'administrateur** pour le hub.

- **Pour les utilisateurs YunoHost normaux**�: les utilisateurs LDAP normaux peuvent se connecter via l'authentification LDAP et y cr�er des canaux.

- **�chec de l'obtention des droits d'administrateur**�: si l'administrateur ne peut pas acc�der aux param�tres d'administration sur `https://hubzilla.example.com/admin`, vous devez **ajouter manuellement 4096** aux **account_roles* * sous **comptes** pour cet utilisateur dans la **base de donn�es via phpMyAdmin**.

- **Pour les logs**�: Allez dans **admin->logs** et saisissez le nom du fichier **php.log**.

- **�chec de la base de donn�es apr�s la mise � niveau�:** Parfois, la mise � niveau de la base de donn�es �choue apr�s la mise � niveau de la version. Vous pouvez aller au hub, par exemple. `https://hubzilla.example.com/admin/dbsync/` et v�rifiez le nombre de mises � jour d�faillantes. Ces mises � jour devront �tre ex�cut�es manuellement par **phpMyAdmin**.
