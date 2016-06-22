xign - holds xchan information for channels that have been ignored in 'friend suggestions' 
[table]
[tr][th]Field[/th][th]Description[/th][th]Type[/th][th]Null[/th][th]Key[/th][th]Default[/th][th]Extra
[/th][/tr]
[tr][td]id[/td][td]sequential ID[/td][td]int(10) unsigned[/td][td]NO[/td][td]PRI[/td][td]NULL[/td][td]auto_increment
[/td][/tr]
[tr][td]uid[/td][td]local channel.channel_id[/td][td]int(11)[/td][td]NO[/td][td]MUL[/td][td]0[/td][td]
[/td][/tr]
[tr][td]xchan[/td][td]xchan.xchan_hash of ignored channel[/td][td]char(255)[/td][td]NO[/td][td]MUL[/td][td][/td][td]
[/td][/tr]
[/table]

Return to [zrl=[baseurl]/help/database]database documentation[/zrl]