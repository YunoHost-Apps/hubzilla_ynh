<style>
{{if $size == 'hz_large'}}
.hz_card {
/*	-moz-transform: translate(-{{$translate}}%, -{{$translate}}%) scale({{$scale}}, {{$scale}}); 
	transform: translate(-{{$translate}}%, -{{$translate}}%) scale({{$scale}}, {{$scale}}); */
	font-family: sans-serif, arial, freesans;
}
.hz_cover_photo {
	max-width: 100%;
}
.hz_profile_photo {
	position: relative;
	top: -300px;
	left: 30px;
	background-color: white;
	border: 1px solid #ddd;
	border-radius: 5px;
	-moz-border-radius: 5px;
	padding: 10px;
	width: 320px;
	height: 320px;
}

.hz_name {
	position: relative;
	top: -100px;
	left: 400px;
	color: #fff;
	font-size: 48px;
    text-rendering: optimizelegibility;
    text-shadow: 0 0 3px rgba(0, 0, 0, 0.8);
}
.hz_addr {
	position: relative;
	top: -110px;
	left: 400px;
	color: #fff;
	font-size: 24px;
    text-rendering: optimizelegibility;
    text-shadow: 0 0 3px rgba(0, 0, 0, 0.8);
}	
{{elseif $size == 'hz_medium'}}
.hz_card {
/*	-moz-transform: translate(-{{$translate}}%, -{{$translate}}%) scale({{$scale}}, {{$scale}}); 
	transform: translate(-{{$translate}}%, -{{$translate}}%) scale({{$scale}}, {{$scale}}); */
	font-family: sans-serif, arial, freesans;
	width: 100%;
	overflow: hidden; 
	height: 390px; 
}
.hz_cover_photo img {
	width: {{$maxwidth}}px;
/*	max-width: 100%; */
}
.hz_profile_photo {
	position: relative;
	top: -165px;
	left: 30px;

	width: 150px;
	height: 150px;
}
.hz_profile_photo img {
	background-color: white;
	border: 1px solid #ddd;
	border-radius: 5px;
	-moz-border-radius: 5px;
	padding: 5px;
	width: 150px;
	height: 150px;
}

.hz_name {
	position: relative;
	top: -100px;
	left: 210px;
	color: #fff;
	font-size: 32px;
    text-rendering: optimizelegibility;
    text-shadow: 0 0 3px rgba(0, 0, 0, 0.8);
}
.hz_addr {
	position: relative;
	top: -100px;
	left: 210px;
	color: #fff;
	font-size: 18px;
    text-rendering: optimizelegibility;
    text-shadow: 0 0 3px rgba(0, 0, 0, 0.8);
}	


{{else}}
.hz_card {
/*	-moz-transform: translate(-{{$translate}}%, -{{$translate}}%) scale({{$scale}}, {{$scale}}); 
	transform: translate(-{{$translate}}%, -{{$translate}}%) scale({{$scale}}, {{$scale}}); */
	font-family: sans-serif, arial, freesans;
}
.hz_cover_photo {
	max-width: 100%;
}
.hz_profile_photo {
	position: relative;
	top: -75px;
	left: 20px;
	background-color: white;
	border: 1px solid #ddd;
/*	border-radius: 5px;
	-moz-border-radius: 5px; */
	padding: 3px;
	width: 80px;
	height: 80px;
}

.hz_name {
	position: relative;
	top: -40px;
	left: 120px;
	color: #fff;
	font-size: 18px;
    text-rendering: optimizelegibility;
    text-shadow: 0 0 3px rgba(0, 0, 0, 0.8);
}
.hz_addr {
	position: relative;
	top: -40px;
	left: 120px;
	color: #fff;
	font-size: 10px;
    text-rendering: optimizelegibility;
    text-shadow: 0 0 3px rgba(0, 0, 0, 0.8);
}	
{{/if}}

</style>

<div class="hz_card {{$size}}">
	<div class="hz_cover_photo"><img src="{{$cover.href}}" alt="{{$zcard.chan.xchan_name}}" />
		<div class="hz_name">{{$zcard.chan.xchan_name}}</div>
		<div class="hz_addr">{{$zcard.chan.channel_addr}}</div>
	</div>
	<div class="hz_profile_photo"><img src="{{$pphoto.href}}" alt="{{$zcard.chan.xchan_name}}" /></div>
</div>

