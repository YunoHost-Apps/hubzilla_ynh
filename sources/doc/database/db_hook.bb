[table]
[tr][th]Field[/th][th]Description[/th][th]Type[/th][th]Null[/th][th]Key[/th][th]Default[/th][th]Extra
[/th][/tr]
[tr][td]id[/td][td]sequential ID[/td][td]int(11)[/td][td]NO[/td][td]PRI[/td][td]NULL[/td][td]auto_increment
[/td][/tr]
[tr][td]hook[/td][td]name of hook[/td][td]char(255)[/td][td]NO[/td][td]MUL[/td][td]NULL[/td][td]
[/td][/tr]
[tr][td]file[/td][td]relative filename of hook handler[/td][td]char(255)[/td][td]NO[/td][td][/td][td]NULL[/td][td]
[/td][/tr]
[tr][td]fn[/td][td]function name of hook handler[/td][td]char(255)[/td][td]NO[/td][td][/td][td]NULL[/td][td]
[/td][/tr]
[tr][td]priority[/td][td]can be used to sort conflicts in hook handling by calling handlers in priority order[/td][td]int(11) unsigned[/td][td]NO[/td][td][/td][td]0[/td][td]
[/td][/tr]
[tr][td]hook_version[/td][td]version 0 hooks must have two arguments, the App and the hook data. version 1 hooks have 1 argument - the hook data[/td][td]int(11) unsigned[/td][td]NO[/td][td][/td][td]0[/td][td]
[/td][/tr]
[/table]

Return to [zrl=[baseurl]/help/database]database documentation[/zrl]