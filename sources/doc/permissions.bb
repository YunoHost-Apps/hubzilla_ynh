[h1]Permissions[/h1]
Permissions in $Projectname are more complete than you may be used to. This allows us to define more fine graded relationships than the black and white &quot;this person is my friend, so they can do everything&quot; or &quot;this person is not my friend, so they can't do anything&quot; permissions you may find elsewhere.

[b]Permission Roles[/b]

When you create a channel we allow you to select different 'roles' for that channel. These create an entire family of permissions and privacy settings that are appropriate for that role. Typical roles are "Social - mostly public", "Social - mostly private", "Forum - public" and many others. These bring a level of simplicity to managing permissions. Just choose a role and appropriate permissions are automatically applied. You can also choose 'Custom/Expert mode' and change any individual permission setting in any way you desire. 


[b]Default Permission Limits[/b]

There are a large number of individual permissions. These control everything from the ability to view your stream to the ability to chat with you. Every permission has a limit. The scope of these permissions varies from &quot;Only me&quot; to &quot;Everybody on the internet&quot; - though some scopes may not be available for some permissions. The limit applies to any published thing you create which has no privacy or access control. For example if you publish a photo and didn't select a specific audience with permission to view it, we apply the limit. These limits apply to everything within that permission rule, so you cannot apply a limit to one photo. The limit applies to all your photos. If all your photos are visible to everybody on the internet and you reduce the limit only to friends, [b]all[/b] of your photos will now be visible only to friends.

[b]Access Control[/b]
 
Access Control is the preferred method of managing privacy in [i]most[/i] cases, rather than using permission limits. This creates lists of either connections or privacy groups (or both) and uses the access list to decide if a permission is allowed. An access list is attached to everything you publish. Unlike permission limits, if you change the access control list on a single photo, it doesn't affect any of your other photos. You can use privacy groups and a "default access control list" to create and automate the management of access control lists to provide any level of privacy you desire on anything you publish. 

We highly recommend that you use the "typical social network" settings when you create your first channel, as it allows others to communicate with you and help you out if you have difficulty. You will find that these settings allow you as much privacy as you desire - when you desire it; but also allow you to communicate in public if you choose to. You are free to use much more private settings once you have learned your way around.


[dl terms="l"]
[*= The scopes of permissions are:]
[dl terms="i"]
  [*= Nobody Except Yourself ] This is self explanatory. Only you will be allowed access.
  
  [*= Only those you specifically allow ] By default, people you are not connected to, and all new contacts will   have this permission denied. You will be able to make exceptions for individual channels on their contact edit   screen.
  
  [*= Anybody in your address book ] Anybody you do not know will have this permission denied, but anybody you   accept as a contact will have this permission approved. This is the way most legacy platforms handle   permissions.
  
  [*= Anybody On This Hub ] Anybody with a channel on the same hub/website as you will have permission approved. Anybody who is registered at a different hub will have this permission denied.
  
  [*= Anybody in this network ] Anybody in $Projectname will have this permission approved. Even complete   strangers. However, anybody not logged in/authenticated will have this permission denied.
  
  [*= Anybody authenticated ] This is similar to "anybody in this network" except that it can include anybody   who can authenticate by any means - and therefore [i]may[/i] include visitors from other networks.
  
  [*=Guest Access Token] This allows you to share a file, folder, photo, album, or channel with a specific person or group of people. They don't need to be Hubzilla members. You can set an expiration for the Access Token.

  [*= Anybody on the internet ] Completely public. This permission will be approved for anybody at all.
[/dl]
[*= The individual permissions are:]
[dl terms="i"]
  [*= Can view my &quot;public&quot; stream and posts. ] This permision determines who can view your channel &quot;stream&quot; that is, the non-private posts that appear on the &quot;home&quot; tab when you're logged in.

  [*= Can view my &quot;public&quot; channel profile. ] This permission determines who can view your channel's profile. This refers to the &quot;about&quot; tab

  [*= Can view my &quot;public&quot; photo albums. ] This permission determines who can view your photo albums. Individual photographs may still be posted to a more private audience.

  [*= Can view my &quot;public&quot; address book. ] This permission determines who can view your contacts. These are the connections displayed in the &quot;View connections&quot; section.

  [*= Can view my &quot;public&quot; file storage. ] This permission determines who can view your public files stored in your cloud.

  [*= Can view my &quot;public&quot; pages. ] This permission determines who can view your public web pages. 

  [*= Can send me their channel stream and posts. ] This permission determines whose posts you will view. If your channel is a personal channel (ie, you as a person), you would probably want to set this to &quot;anyone in my address book&quot; at a minimum. A personal notes channel would probably want to choose &quot;nobody except myself&quot;. Setting this to &quot;Anybody in the network&quot; will show you posts from complete strangers, which is a good form of discovery.

  [*= Can post on my channel page (&quot;wall&quot;). ] This permission determines who can write to your wall when clicking through to your channel.

  [*= Can comment on my posts. ] This permission determines who can comment on posts you create. Normally, you would want this to match your &quot;can view my public stream and posts&quot; permission

  [*= Can send me private mail messages. ] This determines who can send you private messages (zotmail).

  [*= Can post photos to my photo albums. ] This determines who can post photographs in your albums. This is very useful for forum-like channels where connections may not be connected to each other.

  [*= Can forward to all my channel contacts via post tags. ] Using @- mentions will reproduce a copy of your post on the profile specified, as though you posted on the channel wall. This determines if people can post to your channel in this way.

  [*= Can chat with me (when available). ] This determines who can join the public chat rooms created by your channel.

  [*= Can write to my &quot;public&quot; file storage. ] This determines who can upload files to your public file storage, or 'cloud'.

  [*= Can edit my &quot;public&quot; pages. ] This determines who can edit your webpages. This is useful for wikis or sites with multiple editors.

  [*= Can administer my channel resources. ] This determines who can have full control of your channel. This should normally be set to &quot;nobody except myself&quot;.
[/dl][/dl]
[i]Note:[/i]
Plugins/addons may provide special permission settings, so you may be offered additional permission settings beyond what is described here.

If you have set any of these permissions to &quot;only those I specifically allow&quot;, you may specify indivudal permissions on the connnection edit screen.

[b]Affinity[/b]

The connection edit screen offers a slider to select a degree of friendship with the connnection (this tool is enabled through the &quot;Extra Features&quot; tab of your Settings page). Think of this as a measure of how much you like or dislike them. 1 is for people you like, whose posts you want to see all the time. 99 is for people you don't care for, and whose posts you might only wish to look at occasionally. Once you've assigned a value here, you can use the affinity tool on the matrix page to filter content based on this number.

The slider on the matrix page has both a minimum and maximum value. Posts will only be shown from people who fall between this range. Affinity has no relation to permissions, and is only useful in conjunction with the affinity tool feature.

#include doc/macros/main_footer.bb;
