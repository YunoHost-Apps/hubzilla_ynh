[table]
[tr][th]Field[/th][th]Description[/th][th]Type[/th][th]Null[/th][th]Key[/th][th]Default[/th][th]Extra
[/th][/tr]
[tr][td]id[/td][td]sequential ID[/td][td]int(10) unsigned[/td][td]NO[/td][td]PRI[/td][td]NULL[/td][td]auto_increment
[/td][/tr]
[tr][td]hash[/td][td]unique hash representing this group with the group name appended[/td][td]char(255)[/td][td]NO[/td][td]MUL[/td][td][/td][td]
[/td][/tr]
[tr][td]uid[/td][td]channel.channel_id owning this data[/td][td]int(10) unsigned[/td][td]NO[/td][td]MUL[/td][td]NULL[/td][td]
[/td][/tr]
[tr][td]visible[/td][td]1 indicates the member list is not private[/td][td]tinyint(1)[/td][td]NO[/td][td]MUL[/td][td]0[/td][td]
[/td][/tr]
[tr][td]deleted[/td][td]1 indicates the group has been deleted[/td][td]tinyint(1)[/td][td]NO[/td][td]MUL[/td][td]0[/td][td]
[/td][/tr]
[tr][td]gname[/td][td]human readable name of group[/td][td]char(255)[/td][td]NO[/td][td][/td][td]NULL[/td][td]
[/td][/tr]
[/table]

Return to [zrl=[baseurl]/help/database]database documentation[/zrl]