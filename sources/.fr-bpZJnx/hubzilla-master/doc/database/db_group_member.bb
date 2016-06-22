[table]
[tr][th]Field[/th][th]Description[/th][th]Type[/th][th]Null[/th][th]Key[/th][th]Default[/th][th]Extra
[/th][/tr]
[tr][td]id[/td][td]sequential ID[/td][td]int(10) unsigned[/td][td]NO[/td][td]PRI[/td][td]NULL[/td][td]auto_increment
[/td][/tr]
[tr][td]uid[/td][td]channel.channel_id of the owner of this data[/td][td]int(10) unsigned[/td][td]NO[/td][td]MUL[/td][td]NULL[/td][td]
[/td][/tr]
[tr][td]gid[/td][td]groups.id of the associated group[/td][td]int(10) unsigned[/td][td]NO[/td][td]MUL[/td][td]NULL[/td][td]
[/td][/tr]
[tr][td]xchan[/td][td]xchan.xchan_hash of the member assigned to the associated group[/td][td]char(255)[/td][td]NO[/td][td]MUL[/td][td][/td][td]
[/td][/tr]
[/table]

Return to [zrl=[baseurl]/help/database]database documentation[/zrl]