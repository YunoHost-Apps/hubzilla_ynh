[b]Hubzilla on OpenShift[/b]
You will notice a new .openshift folder when you fetch from upstream, i.e. from [url=https://github.com/redmatrix/hubzilla.git]https://github.com/redmatrix/hubzilla.git[/url] , which contains a deploy script to set up Hubzilla on OpenShift with plugins and extra themes.

As of this writing, 2015-10-28, you do not have to pay for OpenShift on the Free plan, which gives you three gears at no cost. The Bronze plan gives you three gears at no cost too, but you can expand to 16 gears by paying, and this requires you to register your payment card. The three gears can give three instances of Hubzilla with one gear each, or you can combine two gears into one high-availability Hubzilla instance and one extra gear. The main difference to be aware of is this: gears on the Free plan will go into hibernation if left idle for too long, this does not happen on the Bronze plan. 

Create an account on OpenShift, then use the registration e-mail and password to create your first Hubzilla instance. Install git and RedHat's command line tools - rhc - if you have not already done so. See for example https://developers.openshift.com/en/getting-started-debian-ubuntu.html on how to do this on Debian GNU/Linux, or in the menu on that page for other GNU/Linux distributions or other operating systems. 

[code]rhc app-create your_app_name php-5.4 mysql-5.5 cron phpmyadmin --namespace your_domain --from-code https://github.com/redmatrix/hubzilla.git -l your@email.address -p your_account_password
[/code]

Make a note of the database username and password OpenShift creates for your instance, and use these at [url=https://your_app_name-your_domain.rhcloud.com/]https://your_app_name-your_domain.rhcloud.com/[/url] to complete the setup. You MUST change server address from 127.0.0.1 to localhost.

NOTE: PostgreSQL is NOT supported by the deploy script yet, see [zrl=https://zot-mor.rhcloud.com/display/3c7035f2a6febf87057d84ea0ae511223e9b38dc27913177bc0df053edecac7c@zot-mor.rhcloud.com?zid=haakon%40zot-mor.rhcloud.com]this thread[/zrl].

[b]Update[/b]
To update, consider your own workflow first. I have forked Hubzilla code into my GitHub account to be able to try things out, this remote repo is called origin. Here is how I fetch new code from upstream, merge into my local repo, then push the updated code both into origin and the remote repo called openshift.

[code]git fetch upstream;git checkout master;git merge upstream/master;git push origin;git push openshift HEAD
[/code]

[b]Administration[/b]
Symptoms of need for MySQL database administration are:
[list]
[*] you can visit your domain and see the Hubzilla frontpage, but trying to login throws you back to login. This can mean your session table is marked as crashed.
[*] you can login, but your channel posts are not visible. This can mean your item table is marked as crashed.
[*] you can login and you can see your channel posts, but apparently nobody is getting your posts, comments, likes and so on. This can mean your outq table is marked as crashed.
[/list]

You can check your OpenShift logs by doing

[code]
rhc tail -a your_app_name -n your_domain -l your@email.address -p your_account_password
[/code]

and you might be able to confirm the above suspicions about crashed tables, or other problems you need to fix.

[b]How to fix crashed tables in MySQL[/b]
Using MySQL and the MyISAM database engine can result in table indexes coming out of sync, and you have at least two options for fixing tables marked as crashed.
[list]
[*] Use the database username and password OpenShift creates for your instance at [url=https://your_app_name-your_domain.rhcloud.com/phpmyadmin/]https://your_app_name-your_domain.rhcloud.com/phpmyadmin/[/url] to login via the web into your phpMyAdmin web interface, click your database in the left column, in the right column scroll down to the bottom of the list of tables and click the checkbox for marking all tables, then select Check tables from the drop down menu. This will check the tables for problems, and you can then checkmark only those tables with problems, and select Repair table from the same drop down menu at the bottom.
[*] You can port-forward the MySQL database service to your own machine and use the MySQL client called mysqlcheck to check, repair and optimize your database or individual database tables without stopping the MySQL service on OpenShift. Run the following in two separate console windows. 

To port-forward do

[code]rhc port-forward -a your_app_name -n your_domain -l your@email.address -p your_password[/code]

in one console window, then do either -o for optimize, -c for check or -r for repair, like this

[code]mysqlcheck -h 127.0.0.1 -r your_app_name -u your_app_admin_name -p[/code]

and give the app's password at the prompt. If all goes well you should see a number of table names with an OK behind them. 

You can now
[code]Press CTRL-C to terminate port forwarding[/code]
[*] You can do

[code]rhc cartridge stop mysql-5.5 -a your_app_name[/code]

to stop the MySQL service running in your app on OpenShift before running myisamchk - which should only be run when MySQL is stopped, and then
login to your instance with SSH - see OpenShift for details - and do 

[code]cd mysql/data/your_database
myisamchk -r *.MYI[/code]

or if you get

[code]Can't create new tempfile[/code]

check your OpenShift's gear quota with

[code]quota -gus[/code]

and if you are short on space, then locally (not SSH) do

[code]rhc app-tidy your_app_name -l your_login -p your_password[/code]

to have rhc delete temporary files and OpenShift logs to free space first, then check the size of your local repo dir and execute

[code]git gc[/code]

against it and check the size again, and then to minimize your remote repo connect via SSH to your application gear and execute the same command against it by changing to the remote repo directory - your repo should be in

[code]~/git/your_app_name.git[/code]

(if not, do find -size +1M to find it), then do

[code]
cd
cd mysql/data/yourdatabase
myisamchk -r -v -f*.MYI[/code]

and hopefully your database tables are now okay.
You can now start the MySQL service on OpenShift by locally doing

[code]rhc cartridge start mysql-5.5 -a your_app_name[/code]
[/list]

[b]Notes[/b]
[list]
[*] definitely DO turn off feeds and discovery by default and limit delivery reports from 30 days to 3 days if you are on the Free or Bronze plan on OpenShift with a single 1Gb gear by visiting [observer.baseurl]/admin/site when logged in as administrator of your Hubzilla site. 
[*] The above defaults have been added into the deploy script.
[*] DO add git gc to the deploy script
[*] MAYBE DO add myisamchk - only checking? to the end of the deploy script.
[*] mysqlcheck is similar in function to myisamchk, but works differently. The main operational difference is that mysqlcheck must be used when the mysqld server is running, whereas myisamchk should be used when it is not. The benefit of using mysqlcheck is that you do not have to stop the server to perform table maintenance - this means this documenation should be fixed.  
[/list]
