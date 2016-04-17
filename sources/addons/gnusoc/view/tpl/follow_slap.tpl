<?xml version="1.0" encoding="UTF-8" ?>
	<entry xmlns="http://www.w3.org/2005/Atom" xmlns:thr="http://purl.org/syndication/thread/1.0" xmlns:as="http://activitystrea.ms/spec/1.0/" xmlns:georss="http://www.georss.org/georss" xmlns:ostatus="http://ostatus.org/schema/1.0" xmlns:poco="http://portablecontacts.net/spec/1.0" xmlns:media="http://purl.org/syndication/atommedia" xmlns:statusnet="http://status.net/schema/api/1/">
		<author>
			<name>{{$name}}</name>
			<uri>{{$profile_page}}</uri>
			<link rel="photo"  type="image/jpeg" media:width="300" media:height="300" href="{{$thumb}}" />
			<link rel="avatar" type="image/jpeg" media:width="300" media:height="300" href="{{$thumb}}" />
			<poco:preferredUsername>{{$nick}}</poco:preferredUsername>
			<poco:displayName>{{$name}}</poco:displayName>
		</author>

		<id>{{$item_id}}</id>
		<title>{{$title}}</title>
		<published>{{$published}}</published>
		<content type="{{$type}}" >{{$content}}</content>
 		<as:verb>{{$verb}}</as:verb>

		<as:object>
		<as:object-type>http://activitystrea.ms/schema/1.0/person</as:object-type>
		<id>{{$remote_profile}}</id>
		<title>{{$remote_name}}</title>
 		<link rel="avatar" type="image/jpeg" media:width="175" media:height="175" href="{{$remote_photo}}"/>
		<link rel="avatar" type="image/jpeg" media:width="80" media:height="80" href="{{$remote_thumb}}"/>
		<poco:preferredUsername>{{$remote_nick}}</poco:preferredUsername>
		<poco:displayName>{{$remote_name}}</poco:displayName>
		</as:object>
	</entry>
