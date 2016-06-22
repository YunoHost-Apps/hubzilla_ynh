<?xml version="1.0" encoding="UTF-8"?>
<rsd version="1.0" xmlns="http://archipelago.phrasewise.com/rsd">
	<service>
		<engineName>{{$project}}</engineName>
		<engineLink>{{$baseurl}}</engineLink>
		<apis>
			<api name="Twitter" preferred="true" apiLink="{{$apipath}}" blogID="">
				<settings>
					<docs>http://status.net/wiki/TwitterCompatibleAPI</docs>
					<setting name="OAuth">true</setting>
				</settings>
			</api>
		</apis>
	</service>
</rsd>