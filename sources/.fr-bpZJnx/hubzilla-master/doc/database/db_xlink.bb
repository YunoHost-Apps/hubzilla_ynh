xlink - used to store social graph and channel ratings
[table]
[tr][th]Field[/th][th]Description[/th][th]Type[/th][th]Null[/th][th]Key[/th][th]Default[/th][th]Extra
[/th][/tr]
[tr][td]xlink_id[/td][td]sequential ID[/td][td]int(10) unsigned[/td][td]NO[/td][td]PRI[/td][td]NULL[/td][td]auto_increment
[/td][/tr]
[tr][td]xlink_xchan[/td][td]xchan.xchan_hash of controlling channel[/td][td]char(255)[/td][td]NO[/td][td]MUL[/td][td][/td][td]
[/td][/tr]
[tr][td]xlink_link[/td][td]xchan.xchan_hash of link target (connection or rating)[/td][td]char(255)[/td][td]NO[/td][td]MUL[/td][td][/td][td]
[/td][/tr]
[tr][td]xlink_rating[/td][td]int rating[/td][td]int(11)[/td][td]NO[/td][td]MUL[/td][td]0[/td][td]
[/td][/tr]
[tr][td]xlink_rating_txt[/td][td]rating text[/td][td]mediumtext[/td][td]NO[/td][td]MUL[/td][td]0[/td][td]
[/td][/tr]
[tr][td]xlink_updated[/td][td]timestamp of update in GMT[/td][td]datetime[/td][td]NO[/td][td]MUL[/td][td]0000-00-00 00:00:00[/td][td]
[tr][td]xlink_static[/td][td]0 for social graph, 1 for ratings[/td][td]int(11)[/td][td]NO[/td][td]MUL[/td][td]0[/td][td]
[/td][/tr]
[tr][td]xlink_sig[/td][td]base64url encoded signature of rating information[/td][td]int(11)[/td][td]NO[/td][td]MUL[/td][td]0[/td][td]
[/td][/tr]
[/table]

Return to [zrl=[baseurl]/help/database]database documentation[/zrl]