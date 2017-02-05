[h2]Versions and Releases[/h2]

$Projectname currently uses a standard version numbering sequence of $x.$y(.$z), for instance '1.12' or '1.12.1'. The first digit is the major version number. Major versions are released "roughly" once per year; often in December.

The second digit is the minor release number. If this number is odd, it is a development version. If the number is even, it is a released version. Minor versions are released (moved from dev to master) typically once per month when development is 'stable', but this is likely to increase. Going forward minor releases will be made somewhere between one and three months; corresponding to a stable code point and when there is general community consensus that the current code base is stable enough to consider a release.

The final digit is an interface or patch designator. 

The release process involves changing the version number (by definition the minor version number will be odd, and the minor number will be incremented). Once a year for a major release the major version will be incremented, and the minor number reset to 0. 

The release candidate is moved to a new branch; and testing will commence/continue for a period of 1-2 weeks afterward or until any significant issues have been resolved. This branch is usually labelled with RC (release candidate); for instance 1.8RC represents the pending release of version 1.8. At this time, the minor version number on the dev branch is incremented to the next odd number. (For instance 1.9). New development can then take place in the dev branch. 

Bug fixes should always be applied to 'dev' and from there merged forward (typically with git cherry-pick) to the RC branch and if necessary applied to the master or official release branch.

At the time a release candidate is produced, the language strings file is frozen until a release is made. Translation work may continue, but all translations should be submitted to 'dev' and merged forward to RC.


Once RC testing is completed, RC is merged to 'master' and the RC version designator removed; resulting in one final checkin to change the version number. The CHANGELOG file should also be updated at or just prior to this time. If there are merge conflicts during this final merge, the merge will be abandoned; and 'git merge -s ours' applied. This results in a replacement of master with the contents of the RC branch. Conflicts often arise with string updates which were made to master after the last release and cannot easily be resolved without hand editing. Since this is a release of tested code, hand editing is discouraged, and the replacement merge strategy should be used instead. It is assumed that RC now contains the most recent well-tested code. 

Once the release is live and merged to master, the RC branch may be removed. 

Fixes may be made to master after release. Where possible these should be made to dev and 'git cherry-pick' used to merge forward; which preserves the commit info and prevents merge conflicts in the next cycle. Only rarely does a patch only apply to the master branch. If necessary this can be made. If the change is severe, the interface version number should be incremented. This is at the discretion of the community. In any event, a 'git pull' of the master branch should always result in the latest release with any post-release patches applied. 

The interface number (the $z in $x.$y.$z) should be incremented in dev whenever a change is made which changes the interfaces or API in incompatible ways so that any external packages (especially addons and API clients) relying on a the current behaviour can discover and change their own interfaces accordingly at the point that it changed.  
 




    