[h1]Theme Management[/h1]
$Projectname allows hub admins to easily add and update themes hosted in common git repositories.
[h2]Add new theme repo to your hub[/h2]
1. Navigate to your hub web root 
[code]root@hub:~# cd /var/www[/code] 
2. Add the theme repo and give it a name 
[code][nobb]root@hub:/var/www# util/add_theme_repo https://github.com/username/theme-repo.git UniqueThemeRepoName[/nobb][/code] 
[h2]Update existing theme repo[/h2]
Update the repo by using 
[code]root@hub:/var/www# util/update_theme_repo UniqueThemeRepoName[/code]
