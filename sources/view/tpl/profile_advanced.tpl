<div id="profile-content-wrapper" class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<div class="pull-right">
			{{if $profile.like_count}}
			<div class="btn-group">
				<button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown" id="profile-like">{{$profile.like_count}} {{$profile.like_button_label}}</button>
				{{if $profile.likers}}
				<ul class="dropdown-menu dropdown-menu-right" role="menu" aria-labelledby="profile-like">{{foreach $profile.likers as $liker}}<li role="presentation"><a href="{{$liker.url}}"><img class="dropdown-menu-img-xs" src="{{$liker.photo}}" alt="{{$liker.name}}" /> {{$liker.name}}</a></li>{{/foreach}}</ul>
				{{/if}}
			</div>
			{{/if}}
			{{if $profile.canlike}}
			<div class="btn-group">
				<button type="button" class="btn btn-success btn-xs" onclick="doprofilelike('profile/' + '{{$profile.profile_guid}}','like'); return false;" title="{{$profile.likethis}}" >
					<i class="fa fa-thumbs-o-up" title="{{$profile.likethis}}"></i>
				</button>
			</div>
			{{/if}}
			{{if $editmenu.multi}}
			<div class="btn-group">
				<a class="btn btn-primary btn-xs dropdown-toggle" data-toggle="dropdown" href="#" ><i class="fa fa-pencil"></i>&nbsp;{{$editmenu.edit.3}}</a>
				<ul class="dropdown-menu dropdown-menu-right" role="menu">
					{{foreach $editmenu.menu.entries as $e}}
					<li>
						<a href="profiles/{{$e.id}}"><img class="dropdown-menu-img-xs" src='{{$e.photo}}'>{{$e.profile_name}}<div class='clear'></div></a>
					</li>
					{{/foreach}}
					<li><a href="profile_photo" >{{$editmenu.menu.chg_photo}}</a></li>
					{{if $editmenu.menu.cr_new}}<li><a href="profiles/new" id="profile-listing-new-link">{{$editmenu.menu.cr_new}}</a></li>{{/if}}
				</ul>
			</div>
			{{elseif $editmenu}}
			<div class="btn-group">
				<a class="btn btn-primary btn-xs" href="{{$editmenu.edit.0}}" ><i class="fa fa-pencil"></i>&nbsp;{{$editmenu.edit.3}}</a>
			</div>
			{{/if}}
		</div>
		<h2>{{$title}}</h2>
		<div class="clear"></div>
	</div>
	<div class="section-content-wrapper">

	{{foreach $fields as $f}}

		{{if $f == 'name'}}
			<dl id="aprofile-fullname" class="aprofile">
			 <dt>{{$profile.fullname.0}}</dt>
			 <dd>{{$profile.fullname.1}}</dd>
			</dl>
		{{/if}}

		{{if $f == 'fullname'}}
			<dl id="aprofile-fullname" class="aprofile">
			 <dt>{{$profile.fullname.0}}</dt>
			 <dd>{{$profile.fullname.1}}</dd>
			</dl>
		{{/if}}

		{{if $f == 'gender'}}
		{{if $profile.gender}}
		<dl id="aprofile-gender" class="aprofile">
		 <dt>{{$profile.gender.0}}</dt>
		 <dd>{{$profile.gender.1}}</dd>
		</dl>
		{{/if}}
		{{/if}}

		{{if $f == 'birthday'}}
		{{if $profile.birthday}}
		<dl id="aprofile-birthday" class="aprofile">
		 <dt>{{$profile.birthday.0}}</dt>
		 <dd>{{$profile.birthday.1}}</dd>
		</dl>
		{{/if}}
		{{/if}}

		{{if $f == 'age'}}
		{{if $profile.age}}
		<dl id="aprofile-age" class="aprofile">
		 <dt>{{$profile.age.0}}</dt>
		 <dd>{{$profile.age.1}}</dd>
		</dl>
		{{/if}}
		{{/if}}


		{{if $f == 'marital'}}
		{{if $profile.marital}}
		<dl id="aprofile-marital" class="aprofile">
		 <dt><span class="heart"><i class="fa fa-heart"></i>&nbsp;</span>{{$profile.marital.0}}</dt>
		 <dd>{{$profile.marital.1}}{{if in_array('partner',$fields)}}{{if $profile.marital.partner}} ({{$profile.marital.partner}}){{/if}}{{/if}}{{if in_array('howlong',$fields)}}{{if $profile.howlong}} {{$profile.howlong}}{{/if}}{{/if}}</dd>
		</dl>
		{{/if}}
		{{/if}}

		{{if $f == 'sexual'}}
		{{if $profile.sexual}}
		<dl id="aprofile-sexual" class="aprofile">
		 <dt>{{$profile.sexual.0}}</dt>
		 <dd>{{$profile.sexual.1}}</dd>
		</dl>
		{{/if}}
		{{/if}}

		{{if $f == 'keywords'}}
		{{if $profile.keywords}}
		<dl id="aprofile-tags" class="aprofile">
		 <dt>{{$profile.keywords.0}}</dt>
		 <dd>{{$profile.keywords.1}}</dd>
		</dl>
		{{/if}}
		{{/if}}

		{{if $f == 'homepage'}}
		{{if $profile.homepage}}
		<dl id="aprofile-homepage" class="aprofile">
		 <dt>{{$profile.homepage.0}}</dt>
		 <dd>{{$profile.homepage.1}}</dd>
		</dl>
		{{/if}}
		{{/if}}

		{{if $f == 'hometown'}}
		{{if $profile.hometown}}
		<dl id="aprofile-hometown" class="aprofile">
		 <dt>{{$profile.hometown.0}}</dt>
		 <dd>{{$profile.hometown.1}}</dd>
		</dl>
		{{/if}}
		{{/if}}


		{{if $f == 'politic'}}
		{{if $profile.politic}}
		<dl id="aprofile-politic" class="aprofile">
		 <dt>{{$profile.politic.0}}</dt>
		 <dd>{{$profile.politic.1}}</dd>
		</dl>
		{{/if}}
		{{/if}}

		{{if $f == 'religion'}}
		{{if $profile.religion}}
		<dl id="aprofile-religion" class="aprofile">
		 <dt>{{$profile.religion.0}}</dt>
		 <dd>{{$profile.religion.1}}</dd>
		</dl>
		{{/if}}
		{{/if}}

		{{if $f == 'about'}}
		{{if $profile.about}}
		<dl id="aprofile-about" class="aprofile">
		 <dt>{{$profile.about.0}}</dt>
		 <dd>{{$profile.about.1}}</dd>
		</dl>
		{{/if}}
		{{/if}}

		{{if $f == 'interest'}}
		{{if $profile.interest}}
		<dl id="aprofile-interest" class="aprofile">
		 <dt>{{$profile.interest.0}}</dt>
		 <dd>{{$profile.interest.1}}</dd>
		</dl>
		{{/if}}
		{{/if}}

		{{if $f == 'likes'}}
		{{if $profile.likes}}
		<dl id="aprofile-likes" class="aprofile">
		 <dt>{{$profile.likes.0}}</dt>
		 <dd>{{$profile.likes.1}}</dd>
		</dl>
		{{/if}}
		{{/if}}

		{{if $f == 'dislikes'}}
		{{if $profile.dislikes}}
		<dl id="aprofile-dislikes" class="aprofile">
		 <dt>{{$profile.dislikes.0}}</dt>
		 <dd>{{$profile.dislikes.1}}</dd>
		</dl>
		{{/if}}
		{{/if}}

		{{if $f == 'contact'}}
		{{if $profile.contact}}
		<dl id="aprofile-contact" class="aprofile">
		 <dt>{{$profile.contact.0}}</dt>
		 <dd>{{$profile.contact.1}}</dd>
		</dl>
		{{/if}}
		{{/if}}

		{{if $f == 'channels'}}
		{{if $profile.channels}}
		<dl id="aprofile-channels" class="aprofile">
		 <dt>{{$profile.channels.0}}</dt>
		 <dd>{{$profile.channels.1}}</dd>
		</dl>
		{{/if}}
		{{/if}}


		{{if $f == 'music'}}
		{{if $profile.music}}
		<dl id="aprofile-music" class="aprofile">
		 <dt>{{$profile.music.0}}</dt>
		 <dd>{{$profile.music.1}}</dd>
		</dl>
		{{/if}}
		{{/if}}


		{{if $f == 'book'}}
		{{if $profile.book}}
		<dl id="aprofile-book" class="aprofile">
		 <dt>{{$profile.book.0}}</dt>
		 <dd>{{$profile.book.1}}</dd>
		</dl>
		{{/if}}
		{{/if}}


		{{if $f == 'tv'}}
		{{if $profile.tv}}
		<dl id="aprofile-tv" class="aprofile">
		 <dt>{{$profile.tv.0}}</dt>
		 <dd>{{$profile.tv.1}}</dd>
		</dl>
		{{/if}}
		{{/if}}


		{{if $f == 'film'}}
		{{if $profile.film}}
		<dl id="aprofile-film" class="aprofile">
		 <dt>{{$profile.film.0}}</dt>
		 <dd>{{$profile.film.1}}</dd>
		</dl>
		{{/if}}
		{{/if}}


		{{if $f == 'romance'}}
		{{if $profile.romance}}
		<dl id="aprofile-romance" class="aprofile">
		 <dt>{{$profile.romance.0}}</dt>
		 <dd>{{$profile.romance.1}}</dd>
		</dl>
		{{/if}}
		{{/if}}


		{{if $f == 'employment'}}
		{{if $profile.employment}}
		<dl id="aprofile-work" class="aprofile">
		 <dt>{{$profile.employment.0}}</dt>
		 <dd>{{$profile.employment.1}}</dd>
		</dl>
		{{/if}}
		{{/if}}

		{{if $f == 'education'}}
		{{if $profile.education}}
		<dl id="aprofile-education" class="aprofile">
		 <dt>{{$profile.education.0}}</dt>
		 <dd>{{$profile.education.1}}</dd>
		</dl>
		{{/if}}
		{{/if}}

		{{foreach $profile.extra_fields as $fld}}
		{{if $f == $fld}}
		{{if $profile.$fld}}
		<dl id="aprofile-{{$fld}}" class="aprofile">
		 <dt>{{$profile.$fld.0}}</dt>
		 <dd>{{$profile.$fld.1}}</dd>
		</dl>
		{{/if}}
		{{/if}}
		{{/foreach}}
	{{/foreach}}


		{{if $things}}
		{{foreach $things as $key => $items}}
		<b>{{$profile.fullname.1}} {{$key}}</b>
		<ul class="profile-thing-list">
		{{foreach $items as $item}}
		<li>{{if $item.img}}<a href="{{$item.url}}" ><img src="{{$item.img}}" class="profile-thing-img" width="100" height="100" alt="{{$item.term}}" /></a>{{/if}}
		<a href="{{$item.editurl}}" >{{$item.term}}</a>
		{{if $profile.canlike}}<br />
		<button type="button" class="btn btn-default btn-sm" onclick="doprofilelike('thing/' + '{{$item.term_hash}}','like'); return false;" title="{{$likethis}}" >
		<i class="fa fa-thumbs-o-up" title="{{$likethis}}"></i>
		</button>
		{{/if}}
		{{if $item.like_count}}
		<div class="btn-group">
		<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" id="thing-like-{{$item.term_hash}}">{{$item.like_count}} {{$item.like_label}}</button>
		{{if $item.likes}}
		<ul class="dropdown-menu" role="menu" aria-labelledby="thing-like-{{$item.term_hash}}">{{foreach $item.likes as $liker}}<li role="presentation"><a href="{{$liker.xchan_url}}"><img class="dropdown-menu-img-xs" src="{{$liker.xchan_photo_s}}" alt="{{$liker.name}}" /> {{$liker.xchan_name}}</a></li>{{/foreach}}</ul>
		{{/if}}
		</div>
		{{/if}}
		</li>
		{{/foreach}}
		</ul>
		<div class="clear"></div>
		{{/foreach}}
		{{/if}}
	</div>
</div>
