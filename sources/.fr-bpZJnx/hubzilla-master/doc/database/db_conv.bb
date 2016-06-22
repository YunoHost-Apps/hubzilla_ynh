
Used in Diaspora private mails

[table]
[tr][th]Field[/th][th]Description[/th][th]Type[/th][th]Null[/th][th]Key[/th][th]Default[/th][th]Extra
[/th][/tr]
[tr][td]id[/td][td]sequential ID[/td][td]int(10) unsigned[/td][td]NO[/td][td]PRI[/td][td]NULL[/td][td]auto_increment
[/td][/tr]
[tr][td]guid[/td][td]A unique identifier for this conversation[/td][td]char(255)[/td][td]NO[/td][td][/td][td]NULL[/td][td]
[/td][/tr]
[tr][td]recips[/td][td]sender_handle;recipient_handle[/td][td]mediumtext[/td][td]NO[/td][td][/td][td]NULL[/td][td]
[/td][/tr]
[tr][td]uid[/td][td]channel.channel_id of the owner of this data[/td][td]int(11)[/td][td]NO[/td][td][/td][td]NULL[/td][td]
[/td][/tr]
[tr][td]creator[/td][td]handle of creator[/td][td]char(255)[/td][td]NO[/td][td][/td][td]NULL[/td][td]
[/td][/tr]
[tr][td]created[/td][td]creation timestamp[/td][td]datetime[/td][td]NO[/td][td]MUL[/td][td]0000-00-00 00:00:00[/td][td]
[/td][/tr]
[tr][td]updated[/td][td]edited timestamp[/td][td]datetime[/td][td]NO[/td][td]MUL[/td][td]0000-00-00 00:00:00[/td][td]
[/td][/tr]
[tr][td]subject[/td][td]subject of initial message (obscured for privacy)[/td][td]mediumtext[/td][td]NO[/td][td][/td][td]NULL[/td][td]
[/td][/tr]
[/table]

Return to [zrl=[baseurl]/help/database]database documentation[/zrl]