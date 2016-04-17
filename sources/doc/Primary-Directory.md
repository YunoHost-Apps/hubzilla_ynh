#Primary Directory#

By default, $Projectname will use available Directories on the web, which show you channels available around the world.

There are certain scenarios where you might want your own directory-server that you can connect multiple hubs to. This will limit the channels that appear in all of your hubs to only channels on hubs connected to your directory-server.



##Instuctions on how to set up one hub as the Primary Directory for a series of private hubs.##
***


*   On the hub that will be the Directory Server, open the .htconfig.php file and set:

    `App::$config['system']['directory_mode'] = DIRECTORY_MODE_PRIMARY;`


    By default it should already be set as **DIRECTORY_MODE_NORMAL**, so just edit that line to say **DIRECTORY_MODE_PRIMARY**

*   Next, for each hub (including the Directory Server), from a terminal,  cd into the folder where it is installed and run this :

    `util/config system directory_realm YOURREALMNAME`

    (**YOURREALMNAME** can be whatever you want your realm-name to be)

    then:

    `util/config system realm_token THEPASSWORD`
    
    (**THEPASSWORD** is whatever password you want for your realm)

    **NOTE:** Use the same realm-name and password for each hub

*   Lastly, for each "client" hub, (from a terminal) run:

    `util/config system directory_server https://theaddressofyourdirectoryserver.com`

***
Now when you view the directory of each hub, it should only show the channels that exist on the hubs in your realm. I have tested with two hubs so far, and it seems to be working fine.
Channels created in each hub are reflected in the Primary Directory, and subsequently in the directory of all client hubs

##Issues##
***

When I created the first hub,it was up and running for an hour or so before I changed it to PRIMARY_MODE, and after changing it, there were a few channels from across the matrix still present in the directory. I deleted them from the xchan table and that seems to have fixed the issue. 


