[table]
[tr][th]Field[/th][th]Description[/th][th]Type[/th][th]Null[/th][th]Key[/th][th]Default[/th][th]Extra
[/th][/tr]
[tr][td]id[/td][td]sequential ID[/td][td]int(10) unsigned[/td][td]NO[/td][td]PRI[/td][td]NULL[/td][td]auto_increment
[/td][/tr]
[tr][td]iid[/td][td]item.id of the referenced item[/td][td]int(11)[/td][td]NO[/td][td]MUL[/td][td]NULL[/td][td]
[/td][/tr]
[tr][td]uid[/td][td]channel.channel_id of the owner of this data[/td][td]int(11)[/td][td]NO[/td][td]MUL[/td][td]NULL[/td][td]
[/td][/tr]
[tr][td]sid[/td][td]an additional identifier to attach or link to the referenced item (often used to store a message_id from another system in order to suppress duplicates)[/td][td]char(255)[/td][td]NO[/td][td]MUL[/td][td]NULL[/td][td]
[/td][/tr]
[tr][td]service[/td][td]the name or description of the service which generated this identifier[/td][td]char(255)[/td][td]NO[/td][td]MUL[/td][td]NULL[/td][td]
[/td][/tr]
[/table]

Return to [zrl=[baseurl]/help/database]database documentation[/zrl]