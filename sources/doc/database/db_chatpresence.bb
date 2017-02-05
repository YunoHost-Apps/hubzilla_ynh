[table]
[tr][th]Field[/th][th]Description[/th][th]Type[/th][th]Null[/th][th]Key[/th][th]Default[/th][th]Extra
[/th][/tr]
[tr][td]cp_id[/td][td]sequential ID[/td][td]int(10) unsigned[/td][td]NO[/td][td]PRI[/td][td]NULL[/td][td]auto_increment
[/td][/tr]
[tr][td]cp_room[/td][td]chatroom.cr_id of the chatroom[/td][td]int(10) unsigned[/td][td]NO[/td][td]MUL[/td][td]0[/td][td]
[/td][/tr]
[tr][td]cp_xchan[/td][td]xchan_hash of the chatroom participant[/td][td]char(255)[/td][td]NO[/td][td]MUL[/td][td][/td][td]
[/td][/tr]
[tr][td]cp_last[/td][td]datetime last ping[/td][td]datetime[/td][td]NO[/td][td]MUL[/td][td]0000-00-00 00:00:00[/td][td]
[/td][/tr]
[tr][td]cp_status[/td][td]text status description e.g. "online"[/td][td]char(255)[/td][td]NO[/td][td]MUL[/td][td]NULL[/td][td]
[/td][/tr]
[tr][td]cp_client[/td][td]IP address of this client[/td][td]char(128)[/td][td]NO[/td][td][/td][td][/td][td]
[/td][/tr]
[/table]

Return to [zrl=[baseurl]/help/database]database documentation[/zrl]