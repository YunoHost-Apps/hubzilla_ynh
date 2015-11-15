[h2]statuses/update[/h2]
Parameters

    title: Title of the status
    status: Status in text [or bbcode] format
    htmlstatus: Status in HTML format
    in_reply_to_status_id
    lat: latitude
    long: longitude
    media: image data
    source: Application name
    group_allow
    contact_allow
    group_deny
    contact_deny


Example

[code]
curl -u theUsername:thePassword http://mywebsite/api/statuses/update.xml -d status='Hello world'
[/code]

